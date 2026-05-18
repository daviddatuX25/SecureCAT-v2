<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class DocxToPdfService
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function isAvailable(): bool
    {
        $path = config('docx.libreoffice_path');

        if ($path === '' || $path === null) {
            return false;
        }

        return PHP_OS_FAMILY === 'Windows' ? is_file($path) : is_executable($path);
    }

    public function convert(string $docxPath): string
    {
        if (! file_exists($docxPath)) {
            throw new \InvalidArgumentException("DOCX file not found: {$docxPath}");
        }

        $outputDir = $this->createTempDir();
        $profileDir = $this->createTempDir('lo_profile_');

        try {
            $profileUri = 'file:///'.str_replace('\\', '/', $profileDir);

            $command = [
                config('docx.libreoffice_path'),
                '--headless',
                '--norestore',
                '-env:UserInstallation='.$profileUri,
                '--convert-to', 'pdf',
                '--outdir', $outputDir,
                $docxPath,
            ];

            $timeout = config('docx.conversion_timeout', 120);

            $process = new Process($command);
            $process->setTimeout($timeout);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->logger->error('LibreOffice conversion failed', [
                    'command' => $process->getCommandLine(),
                    'exitCode' => $process->getExitCode(),
                    'stdout' => $process->getOutput(),
                    'stderr' => $process->getErrorOutput(),
                ]);

                throw new \RuntimeException(
                    'LibreOffice conversion failed: '.$process->getErrorOutput()
                );
            }

            $basename = pathinfo($docxPath, PATHINFO_FILENAME);
            $pdfPath = $outputDir.DIRECTORY_SEPARATOR.$basename.'.pdf';

            if (! file_exists($pdfPath)) {
                throw new \RuntimeException(
                    "Expected PDF output not found: {$pdfPath}"
                );
            }

            return file_get_contents($pdfPath);
        } finally {
            $this->removeDir($profileDir);
            $this->removeDir($outputDir);
        }
    }

    /**
     * @param  string[]  $docxPaths
     */
    public function convertBatch(array $docxPaths, int $copies = 1): string
    {
        if (empty($docxPaths)) {
            throw new \InvalidArgumentException('docxPaths must not be empty.');
        }

        if ($copies < 1) {
            throw new \InvalidArgumentException('copies must be at least 1.');
        }

        foreach ($docxPaths as $docxPath) {
            if (! file_exists($docxPath)) {
                throw new \InvalidArgumentException("DOCX file not found: {$docxPath}");
            }
        }

        $pdfs = [];

        foreach ($docxPaths as $docxPath) {
            $pdfs[] = $this->convert($docxPath);
        }

        $allPdfs = [];
        for ($i = 0; $i < $copies; $i++) {
            foreach ($pdfs as $pdf) {
                $allPdfs[] = $pdf;
            }
        }

        if (count($allPdfs) === 1) {
            return $allPdfs[0];
        }

        $pdfunitePath = config('docx.pdfunite_path');

        if ($pdfunitePath && (PHP_OS_FAMILY === 'Windows' ? is_file($pdfunitePath) : is_executable($pdfunitePath))) {
            return $this->mergeWithPdfunite($allPdfs, $pdfunitePath);
        }

        return $this->mergeFallback($allPdfs);
    }

    /**
     * @param  string[]  $pdfContents
     */
    protected function mergeWithPdfunite(array $pdfContents, string $pdfunitePath): string
    {
        $tempDir = $this->createTempDir('pdfunite_');
        $inputFiles = [];

        try {
            foreach ($pdfContents as $i => $content) {
                $inputPath = $tempDir.DIRECTORY_SEPARATOR."input_{$i}.pdf";
                file_put_contents($inputPath, $content);
                $inputFiles[] = $inputPath;
            }

            $outputPath = $tempDir.DIRECTORY_SEPARATOR.'merged.pdf';

            $command = array_merge([$pdfunitePath], $inputFiles, [$outputPath]);

            $process = new Process($command);
            $process->setTimeout(config('docx.conversion_timeout', 120));
            $process->run();

            if (! $process->isSuccessful()) {
                $this->logger->error('pdfunite merge failed', [
                    'command' => $process->getCommandLine(),
                    'exitCode' => $process->getExitCode(),
                    'stdout' => $process->getOutput(),
                    'stderr' => $process->getErrorOutput(),
                ]);

                throw new \RuntimeException('pdfunite merge failed: '.$process->getErrorOutput());
            }

            return file_get_contents($outputPath);
        } finally {
            $this->removeDir($tempDir);
        }
    }

    /**
     * @param  string[]  $pdfContents
     */
    protected function mergeFallback(array $pdfContents): string
    {
        if (count($pdfContents) === 1) {
            return $pdfContents[0];
        }

        throw new \RuntimeException('pdfunite is required for multi-file PDF merge. Install poppler-utils.');
    }

    protected function createTempDir(string $prefix = 'lo_'): string
    {
        $baseDir = config('docx.temp_dir');

        if (! is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $path = $baseDir.DIRECTORY_SEPARATOR.uniqid($prefix, true);

        mkdir($path, 0755, true);

        return $path;
    }

    protected function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
