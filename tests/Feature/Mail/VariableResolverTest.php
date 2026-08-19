<?php

namespace Tests\Feature\Mail;

use App\Domain\Mail\Services\VariableResolver;
use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariableResolverTest extends TestCase
{
    use RefreshDatabase;

    private VariableResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(VariableResolver::class);
    }

    public function test_resolve_returns_standard_variables(): void
    {
        $registration = Registration::factory()->create([
            'name' => 'Jane Runner',
            'email' => 'jane@example.com',
        ]);

        $variables = $this->resolver->resolve($registration);

        $this->assertEquals('Jane Runner', $variables['name']);
        $this->assertEquals('jane@example.com', $variables['email']);
        $this->assertArrayHasKey('track_name', $variables);
        $this->assertArrayHasKey('event_name', $variables);
        $this->assertArrayHasKey('registration_date', $variables);
        $this->assertArrayHasKey('draw_status', $variables);
        $this->assertArrayHasKey('contact_email_link', $variables);
        $this->assertArrayHasKey('theme_primary_color', $variables);
        $this->assertArrayHasKey('theme_background_color', $variables);
        $this->assertArrayHasKey('theme_text_color', $variables);
        $this->assertArrayHasKey('theme_accent_color', $variables);
    }

    public function test_resolve_includes_team_name_when_present(): void
    {
        $team = Team::factory()->create(['track_id' => 1, 'max_members' => 10]);
        $registration = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
        ]);

        $variables = $this->resolver->resolve($registration);

        $this->assertEquals($team->name, $variables['team_name']);
        $this->assertNotEmpty($variables['team_members_list']);
    }

    public function test_resolve_returns_empty_team_name_when_no_team(): void
    {
        $registration = Registration::factory()->create([
            'team_id' => null,
        ]);

        $variables = $this->resolver->resolve($registration);

        $this->assertEquals('', $variables['team_name']);
        $this->assertEquals('', $variables['team_members_list']);
    }

    public function test_resolve_handles_custom_questions(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'shirt_size',
                'type' => 'select',
                'translations' => ['en' => ['label' => 'Shirt Size'], 'de' => ['label' => 'Hemdgröße']],
                'options' => [
                    ['value' => 'm', 'label_en' => 'Medium', 'label_de' => 'Mittel'],
                    ['value' => 'l', 'label_en' => 'Large', 'label_de' => 'Groß'],
                ],
            ],
        ];
        $settings->save();

        $registration = Registration::factory()->create([
            'custom_answers' => ['shirt_size' => 'm'],
        ]);

        $variables = $this->resolver->resolve($registration);

        $this->assertEquals('m', $variables['custom.shirt_size']);
        $this->assertEquals('Medium', $variables['custom.shirt_size.en']);
        $this->assertEquals('Mittel', $variables['custom.shirt_size.de']);
    }

    public function test_resolve_handles_custom_checkbox_questions(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'dietary',
                'type' => 'checkbox',
                'translations' => ['en' => ['label' => 'Dietary'], 'de' => ['label' => 'Ernährung']],
                'options' => [
                    ['value' => 'vegetarian', 'label_en' => 'Vegetarian', 'label_de' => 'Vegetarisch'],
                    ['value' => 'vegan', 'label_en' => 'Vegan', 'label_de' => 'Vegan'],
                ],
            ],
        ];
        $settings->save();

        $registration = Registration::factory()->create([
            'custom_answers' => ['dietary' => ['vegetarian', 'vegan']],
        ]);

        $variables = $this->resolver->resolve($registration);

        $this->assertEquals('vegetarian, vegan', $variables['custom.dietary']);
        $this->assertEquals('Vegetarian, Vegan', $variables['custom.dietary.en']);
        $this->assertEquals('Vegetarisch, Vegan', $variables['custom.dietary.de']);
    }

    public function test_resolve_handles_text_custom_questions_without_locale_suffixes(): void
    {
        $settings = app(EventSettings::class);
        $settings->custom_questions = [
            [
                'key' => 'comment',
                'type' => 'text',
                'translations' => ['en' => ['label' => 'Comment']],
                'options' => [],
            ],
        ];
        $settings->save();

        $registration = Registration::factory()->create([
            'custom_answers' => ['comment' => 'Hello world'],
        ]);

        $variables = $this->resolver->resolve($registration);

        $this->assertEquals('Hello world', $variables['custom.comment']);
        $this->assertArrayNotHasKey('custom.comment.en', $variables);
        $this->assertArrayNotHasKey('custom.comment.de', $variables);
    }

    public function test_get_sample_variables_returns_all_standard_keys(): void
    {
        $samples = $this->resolver->getSampleVariables();

        $this->assertArrayHasKey('name', $samples);
        $this->assertArrayHasKey('email', $samples);
        $this->assertArrayHasKey('track_name', $samples);
        $this->assertArrayHasKey('event_name', $samples);
        $this->assertArrayHasKey('registration_date', $samples);
        $this->assertArrayHasKey('draw_status', $samples);
        $this->assertArrayHasKey('team_name', $samples);
        $this->assertArrayHasKey('contact_email_link', $samples);
    }
}
