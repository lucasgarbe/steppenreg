<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Settings\EventSettings;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomQuestionMarkdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up event settings with custom questions
        $settings = app(EventSettings::class);
        $settings->application_state = 'open';
        $settings->custom_questions = [];
        $settings->save();
    }

    public function test_custom_question_label_renders_markdown_link(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'Read our [privacy policy](https://example.com/privacy)',
                        'placeholder' => '',
                        'help' => '',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="https://example.com/privacy"', false);
        $response->assertSee('privacy policy', false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_custom_question_help_text_renders_markdown_link(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'Your Name',
                        'placeholder' => '',
                        'help' => 'For more info, visit [our FAQ](https://example.com/faq)',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="https://example.com/faq"', false);
        $response->assertSee('our FAQ', false);
        $response->assertSee('target="_blank"', false);
    }

    public function test_custom_question_renders_bold_text(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'This is **important** information',
                        'placeholder' => '',
                        'help' => '',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<strong>important</strong>', false);
    }

    public function test_custom_question_renders_italic_text(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'This is *emphasized* text',
                        'placeholder' => '',
                        'help' => '',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<em>emphasized</em>', false);
    }

    public function test_custom_question_renders_underline_text(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'Please <u>underline</u> this',
                        'placeholder' => '',
                        'help' => '',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<u>underline</u>', false);
    }

    public function test_custom_question_sanitizes_script_tags(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'Malicious <script>alert("XSS")</script> content',
                        'placeholder' => '',
                        'help' => '',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check that the malicious alert is not in the custom question label
        $response->assertDontSee('alert("XSS")', false);
        // The label should still contain "Malicious" and "content" but without script tags
        $response->assertSee('Malicious', false);
    }

    public function test_custom_question_sanitizes_iframe_tags(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'Safe text',
                        'placeholder' => '',
                        'help' => 'Help <iframe>malicious content</iframe> text',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check that iframe tag itself is not present as an actual HTML tag
        $content = $response->getContent();
        $this->assertStringNotContainsString('<iframe', $content);
        $this->assertStringNotContainsString('</iframe>', $content);
        // The help text should still contain "Help" and "text" but without iframe tags
        $response->assertSee('Help', false);
    }

    public function test_custom_question_with_complex_markdown(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'I agree to the [**terms**](https://example.com/terms)',
                        'placeholder' => '',
                        'help' => 'Read the *conditions* and <u>guidelines</u> on [our website](https://example.com)',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Check label
        $response->assertSee('href="https://example.com/terms"', false);
        $response->assertSee('<strong>terms</strong>', false);
        
        // Check help text
        $response->assertSee('href="https://example.com"', false);
        $response->assertSee('<em>conditions</em>', false);
        $response->assertSee('<u>guidelines</u>', false);
    }

    public function test_custom_question_without_markdown_renders_plain_text(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'Plain text label',
                        'placeholder' => '',
                        'help' => 'Plain help text',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Plain text label', false);
        $response->assertSee('Plain help text', false);
    }

    public function test_custom_question_with_multiple_links(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'test_question',
                'type' => 'text',
                'required' => false,
                'translations' => [
                    'en' => [
                        'label' => 'Check [link 1](https://example1.com) and [link 2](https://example2.com)',
                        'placeholder' => '',
                        'help' => '',
                    ],
                ],
                'sort_order' => 0,
            ],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="https://example1.com"', false);
        $response->assertSee('href="https://example2.com"', false);
        $response->assertSee('link 1', false);
        $response->assertSee('link 2', false);
    }
}
