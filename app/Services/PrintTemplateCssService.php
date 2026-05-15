<?php

namespace App\Services;

use Illuminate\Support\Facades\Vite;

/**
 * Injects sandboxed, self-contained Tailwind CSS into print template HTML.
 *
 * The print-template.css is a separate Vite entry that bundles all Tailwind
 * utilities used by print templates. This service loads the built CSS and wraps
 * it in a CSS @scope rule so styles only apply inside .print-template containers,
 * preventing global app styles from bleeding in.
 */
class PrintTemplateCssService
{
    private ?string $cachedCss = null;

    /**
     * Wrap rendered template HTML in a scoped container with injected CSS.
     */
    public function wrap(string $html): string
    {
        $css = $this->getScopedCss();

        return "<style>{$css}</style>\n<div class=\"print-template\">{$html}</div>";
    }

    /**
     * Wrap two applicant HTML blocks in a single dual-layout container with shared CSS.
     * Uses CSS Grid for accurate 50/50 vertical split.
     */
    public function wrapDual(string $html1, string $html2): string
    {
        $css = $this->getScopedCss();

        return "<style>{$css}</style>\n"
            ."<div class=\"print-template print-template--dual\">\n"
            ."  <div class=\"print-template--half\">{$html1}</div>\n"
            ."  <div class=\"print-template--half\">{$html2}</div>\n"
            .'</div>';
    }

    /**
     * Get the scoped CSS string (cached per request).
     *
     * Wraps the built print-template CSS in @scope(.print-template) { ... }
     * so all Tailwind utilities are isolated to .print-template containers.
     */
    public function getScopedCss(): string
    {
        if ($this->cachedCss !== null) {
            return $this->cachedCss;
        }

        $rawCss = $this->loadBuiltCss();
        $scoped = "@scope (.print-template) {\n{$rawCss}\n}";

        $this->cachedCss = $scoped;

        return $scoped;
    }

    /**
     * Load the built print-template CSS asset.
     */
    protected function loadBuiltCss(): string
    {
        $manifestPath = public_path('build/manifest.json');

        if (! file_exists($manifestPath)) {
            // Fallback: try asset() helper (works in dev with Vite dev server)
            return $this->loadFromAssetHelper();
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $key = 'resources/css/print-template.css';

        if (isset($manifest[$key]['file'])) {
            $cssPath = public_path("build/{$manifest[$key]['file']}");
            if (file_exists($cssPath)) {
                return file_get_contents($cssPath);
            }
        }

        return $this->loadFromAssetHelper();
    }

    /**
     * Fallback: load CSS using Vite facade (works in dev mode).
     */
    protected function loadFromAssetHelper(): string
    {
        try {
            $url = Vite::asset('resources/css/print-template.css');

            // In dev mode, the asset is served by Vite dev server.
            // We can't inline it, so return a link tag instead.
            if (str_contains($url, '://')) {
                return $this->linkTagCss($url);
            }

            $cssPath = public_path(parse_url($url, PHP_URL_PATH));
            if (file_exists($cssPath)) {
                return file_get_contents($cssPath);
            }

            return $this->linkTagCss($url);
        } catch (\Throwable $e) {
            // Vite asset not available — return empty string gracefully.
            // Templates will fall back to the app's global Tailwind.
            return '';
        }
    }

    /**
     * Generate a <link> tag for external CSS (used in Vite dev mode).
     *
     * When the Vite dev server is running, we can't inline the CSS because
     * it's served dynamically. Instead, we link to it and add an @import rule
     * inside @scope so it's still scoped.
     */
    protected function linkTagCss(string $url): string
    {
        return "@scope (.print-template) {\n  @import url('{$url}');\n}";
    }
}
