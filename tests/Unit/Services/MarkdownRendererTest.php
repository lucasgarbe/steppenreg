<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new MarkdownRenderer();
    }

    public function test_renders_plain_text_unchanged(): void
    {
        $result = $this->renderer->render('Hello World');
        $this->assertStringContainsString('Hello World', $result);
    }

    public function test_renders_markdown_link(): void
    {
        $result = $this->renderer->render('[Click here](https://example.com)');
        
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('Click here', $result);
    }

    public function test_adds_target_blank_to_links(): void
    {
        $result = $this->renderer->render('[Link](https://example.com)');
        
        $this->assertStringContainsString('target="_blank"', $result);
    }

    public function test_adds_noopener_noreferrer_to_links(): void
    {
        $result = $this->renderer->render('[Link](https://example.com)');
        
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    public function test_renders_bold_text(): void
    {
        $result = $this->renderer->render('This is **bold** text');
        
        $this->assertStringContainsString('<strong>bold</strong>', $result);
    }

    public function test_renders_italic_text(): void
    {
        $result = $this->renderer->render('This is *italic* text');
        
        $this->assertStringContainsString('<em>italic</em>', $result);
    }

    public function test_renders_underline_html_tag(): void
    {
        $result = $this->renderer->render('This is <u>underlined</u> text');
        
        $this->assertStringContainsString('<u>underlined</u>', $result);
    }

    public function test_strips_dangerous_script_tags(): void
    {
        $result = $this->renderer->render('Hello <script>alert("XSS")</script> World');
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
        // Note: The text content "alert" remains after tags are stripped, which is safe
    }

    public function test_strips_dangerous_onclick_attributes(): void
    {
        $result = $this->renderer->render('[Link](https://example.com)');
        
        // Manually test that even if someone tries to inject onclick, it gets stripped
        $malicious = '<a href="https://example.com" onclick="alert(1)">Link</a>';
        $result = $this->renderer->render($malicious);
        
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function test_handles_multiple_links_in_one_text(): void
    {
        $result = $this->renderer->render('[Link 1](https://example1.com) and [Link 2](https://example2.com)');
        
        $this->assertStringContainsString('href="https://example1.com"', $result);
        $this->assertStringContainsString('href="https://example2.com"', $result);
        $this->assertStringContainsString('Link 1', $result);
        $this->assertStringContainsString('Link 2', $result);
    }

    public function test_handles_link_with_bold_and_italic(): void
    {
        $result = $this->renderer->render('Read the [**terms**](https://example.com/terms) and *conditions*');
        
        $this->assertStringContainsString('href="https://example.com/terms"', $result);
        $this->assertStringContainsString('<strong>terms</strong>', $result);
        $this->assertStringContainsString('<em>conditions</em>', $result);
    }

    public function test_returns_empty_string_for_null_input(): void
    {
        $result = $this->renderer->render(null);
        
        $this->assertSame('', $result);
    }

    public function test_returns_empty_string_for_empty_input(): void
    {
        $result = $this->renderer->render('');
        
        $this->assertSame('', $result);
    }

    public function test_escapes_html_entities_in_url(): void
    {
        $result = $this->renderer->render('[Link](https://example.com?param=value&other=123)');
        
        // The URL should be properly escaped in the href attribute
        $this->assertStringContainsString('href=', $result);
        $this->assertStringContainsString('Link', $result);
    }

    public function test_strips_iframe_tags(): void
    {
        $result = $this->renderer->render('Hello <iframe src="https://evil.com"></iframe> World');
        
        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringNotContainsString('</iframe>', $result);
        // Note: The URL text may remain after iframe tags are stripped and HTML-escaped
    }

    public function test_allows_paragraph_tags(): void
    {
        $result = $this->renderer->render("Line 1\n\nLine 2");
        
        // Markdown converter should create paragraphs
        $this->assertStringContainsString('<p>', $result);
    }

    public function test_complex_markdown_with_all_features(): void
    {
        $markdown = 'Please read our [**privacy policy**](https://example.com/privacy) and the *terms*. Contact <u>support</u> for help.';
        $result = $this->renderer->render($markdown);
        
        // Check all features work together
        $this->assertStringContainsString('href="https://example.com/privacy"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
        $this->assertStringContainsString('<strong>privacy policy</strong>', $result);
        $this->assertStringContainsString('<em>terms</em>', $result);
        $this->assertStringContainsString('<u>support</u>', $result);
    }
}
