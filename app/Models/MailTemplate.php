<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mailLogs(): HasMany
    {
        return $this->hasMany(MailLog::class, 'template_key', 'key');
    }

    public function renderContent(array $variables = []): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($variables as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    public function getVariablesFromContent(): array
    {
        $content = $this->subject.' '.$this->body;
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    public static function getAvailableVariables(): array
    {
        $variables = [
            'name' => 'Participant name',
            'email' => 'Participant email',
            'track_name' => 'Track name',
            'event_name' => 'Event name',
            'registration_date' => 'Registration date',
            'draw_status' => 'Draw status',
            'team_name' => 'Team name (if applicable)',
            'track_distance' => 'Track distance',
            'contact_email_link' => 'Contact email link (clickable)',
            'theme_primary_color' => 'Theme primary color (configured in settings)',
            'theme_background_color' => 'Theme background color (configured in settings)',
            'theme_text_color' => 'Theme text color (configured in settings)',
            'theme_accent_color' => 'Theme accent color (configured in settings)',
        ];

        // Add custom question variables dynamically
        try {
            $eventSettings = app(\App\Settings\EventSettings::class);
            $customQuestions = $eventSettings->custom_questions ?? [];

            foreach ($customQuestions as $question) {
                $key = $question['key'] ?? '';
                if (empty($key)) {
                    continue;
                }

                $type = $question['type'] ?? 'text';

                // Get the English label, fallback to key if not available
                $label = $question['translations']['en']['label'] ?? ucfirst(str_replace('_', ' ', $key));

                // Add type information to help users understand the variable
                $typeInfo = match ($type) {
                    'checkbox' => ' (multiple values, comma-separated)',
                    'select', 'radio' => ' (selected option)',
                    'date' => ' (formatted date)',
                    'number' => ' (numeric value)',
                    'email' => ' (email address)',
                    'textarea' => ' (long text)',
                    default => ' (text)'
                };

                // Add raw value variable
                $variables["custom.{$key}"] = "Custom: {$label}{$typeInfo} - raw value";

                // Add locale-suffixed variables only for option-based types
                if (in_array($type, ['select', 'radio', 'checkbox'])) {
                    $variables["custom.{$key}.en"] = "Custom: {$label}{$typeInfo} - English label";
                    $variables["custom.{$key}.de"] = "Custom: {$label}{$typeInfo} - German label";
                }
            }
        } catch (\Exception $e) {
            // Silently handle any errors when EventSettings is not available
            // This can happen during migrations or in testing environments
        }

        return $variables;
    }

    /**
     * Get all available variables with custom questions included, grouped by category
     */
    public static function getAvailableVariablesGrouped(): array
    {
        $allVariables = static::getAvailableVariables();

        $grouped = [
            'Standard Variables' => [],
            'Custom Question Answers' => [],
        ];

        foreach ($allVariables as $key => $description) {
            if (str_starts_with($key, 'custom.')) {
                $grouped['Custom Question Answers'][$key] = $description;
            } else {
                $grouped['Standard Variables'][$key] = $description;
            }
        }

        // Remove empty groups
        return array_filter($grouped, fn ($group) => ! empty($group));
    }

    /**
     * Get information about whether custom questions have locale-suffixed variants
     */
    public static function hasLocaleSuffixedCustomVariables(): bool
    {
        try {
            $eventSettings = app(\App\Settings\EventSettings::class);
            $customQuestions = $eventSettings->custom_questions ?? [];

            foreach ($customQuestions as $question) {
                $type = $question['type'] ?? 'text';
                if (in_array($type, ['select', 'radio', 'checkbox'])) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Silently handle errors
        }

        return false;
    }
}
