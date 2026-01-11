<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class MarkdownRenderer
{
    /**
     * Render Markdown text to sanitized HTML.
     *
     * Converts Markdown to HTML and sanitizes the output to allow only safe tags.
     * All links automatically get target="_blank" and rel="noopener noreferrer" for security.
     *
     * @param  string|null  $text  The Markdown text to render
     * @return string The sanitized HTML output
     */
    public function render(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Convert Markdown to HTML (allow HTML input to preserve <u> tags)
        $html = Str::markdown($text, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        // Sanitize: allow only safe tags (this strips dangerous tags like script, iframe, etc)
        $html = strip_tags($html, '<a><strong><em><u><p><br>');

        // Strip dangerous attributes from all tags (onclick, onload, onerror, etc)
        $html = preg_replace('/<([a-z]+)\s+([^>]*?)on[a-z]+\s*=\s*["\'][^"\']*["\']([^>]*)>/i', '<$1 $2$3>', $html);
        $html = preg_replace('/<([a-z]+)\s+([^>]*?)on[a-z]+\s*=\s*[^\s>]+([^>]*)>/i', '<$1 $2$3>', $html);

        // Add target="_blank" and rel="noopener noreferrer" to all links for security
        $html = preg_replace_callback(
            '/<a\s+([^>]*?)href=(["\'])([^"\']*)\2([^>]*)>/i',
            function ($matches) {
                $beforeHref = $matches[1];
                $url = $matches[3];
                $afterHref = $matches[4];

                // Remove any existing target or rel attributes to avoid duplicates
                $beforeHref = preg_replace('/\s*target=(["\'])[^"\']*\1/i', '', $beforeHref);
                $afterHref = preg_replace('/\s*target=(["\'])[^"\']*\1/i', '', $afterHref);
                $beforeHref = preg_replace('/\s*rel=(["\'])[^"\']*\1/i', '', $beforeHref);
                $afterHref = preg_replace('/\s*rel=(["\'])[^"\']*\1/i', '', $afterHref);

                return sprintf(
                    '<a %shref="%s"%s target="_blank" rel="noopener noreferrer">',
                    trim($beforeHref) ? trim($beforeHref).' ' : '',
                    htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                    trim($afterHref) ? ' '.trim($afterHref) : ''
                );
            },
            $html
        );

        return $html;
    }
}
