<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public function __construct(
        private string $baseUrl = ''
    ) {
        $this->baseUrl = $baseUrl ?: rtrim(config('app.url', ''), '/');
    }

    /**
     * Generate data URI for admission slip QR (encodes {APP_URL}/admission/{ref}).
     */
    public function admissionSlipDataUri(string $referenceNumber): string
    {
        $url = $this->baseUrl.'/admission/'.urlencode($referenceNumber);

        return $this->dataUri($url, 80);
    }

    /**
     * Generate data URI for result sheet / consultation QR (encodes {APP_URL}/consultation/{ref}).
     */
    public function consultationDataUri(string $referenceNumber): string
    {
        $url = $this->baseUrl.'/consultation/'.urlencode($referenceNumber);

        return $this->dataUri($url, 80);
    }

    /**
     * Generate a QR code as a base64 data URI for embedding in HTML img src.
     */
    public function dataUri(string $data, int $size = 80): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: $size,
            margin: 2,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $result = $builder->build();

        return $result->getDataUri();
    }
}
