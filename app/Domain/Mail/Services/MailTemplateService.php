<?php

namespace App\Domain\Mail\Services;

use App\Domain\Mail\Mail\FlexibleMail;
use App\Domain\Mail\Mail\TemplateBasedEmail;
use App\Domain\Mail\Models\MailLog;
use App\Domain\Mail\Models\MailTemplate;
use App\Models\Registration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class MailTemplateService
{
    public function __construct(
        private VariableResolver $variableResolver
    ) {}

    /**
     * Send a template-based email to a registration.
     */
    public function sendEmail(string $templateKey, Registration $registration): ?MailLog
    {
        Log::info('[Mail Trail] MailTemplateService::sendEmail started', [
            'template_key' => $templateKey,
            'registration_id' => $registration->id,
            'email' => $registration->email,
        ]);

        $template = MailTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            Log::warning('[Mail Trail] Template not found or inactive', [
                'template_key' => $templateKey,
            ]);

            return null;
        }

        Log::info('[Mail Trail] Template found', [
            'template_key' => $templateKey,
            'template_id' => $template->id,
            'subject' => $template->subject,
        ]);

        $variables = $this->variableResolver->resolve($registration);

        Log::info('[Mail Trail] Variables resolved', [
            'template_key' => $templateKey,
            'registration_id' => $registration->id,
            'variable_count' => count($variables),
            'variables' => $variables,
        ]);

        $renderedSubject = $this->replaceVariables($template->subject, $variables);
        $renderedHtmlBody = $this->renderMarkdownBody($template->body, $variables);
        $renderedTextBody = $this->renderPlainTextBody($template->body, $variables);

        Log::info('[Mail Trail] Content rendered', [
            'template_key' => $templateKey,
            'rendered_subject' => $renderedSubject,
            'html_body_length' => strlen($renderedHtmlBody),
            'text_body_length' => strlen($renderedTextBody),
        ]);

        $mailLog = $this->findOrCreateQueuedMailLog($templateKey, $registration, $variables);

        Log::info('[Mail Trail] Mail log ready', [
            'mail_log_id' => $mailLog->id,
            'status' => $mailLog->status,
            'attempt_count' => $mailLog->attempt_count,
        ]);

        try {
            $mailable = new TemplateBasedEmail(
                $renderedSubject,
                $renderedHtmlBody,
                $renderedTextBody,
                $registration,
                $variables
            );

            Log::info('[Mail Trail] About to call Mail::to()->send()', [
                'mail_log_id' => $mailLog->id,
                'to_email' => $registration->email,
                'to_name' => $registration->name,
                'mailer' => config('mail.default'),
                'from_address' => config('mail.from.address'),
            ]);

            Mail::to($registration->email, $registration->name)->send($mailable);

            Log::info('[Mail Trail] Mail::send() returned without exception', [
                'mail_log_id' => $mailLog->id,
            ]);

            $mailLog->markAsSent();

            Log::info('[Mail Trail] Mail log marked as sent', [
                'mail_log_id' => $mailLog->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[Mail Trail] Exception during Mail::send()', [
                'mail_log_id' => $mailLog->id,
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            $mailLog->markAsFailed($e->getMessage());
            throw $e;
        }

        return $mailLog;
    }

    /**
     * Send a flexible (admin-composed) email to a registration.
     */
    public function sendFlexible(Registration $registration, string $subject, string $body): MailLog
    {
        $variables = $this->variableResolver->resolve($registration);

        $renderedSubject = $this->replaceVariables($subject, $variables);
        $renderedBody = $this->renderMarkdownBody($body, $variables);

        $mailLog = $this->findOrCreateQueuedMailLog('custom_email', $registration, [
            'original_subject' => $subject,
            'original_message' => $body,
            'processed_subject' => $renderedSubject,
            'processed_message' => $renderedBody,
            'name' => $registration->name,
            'email' => $registration->email,
        ]);

        try {
            Mail::to($registration->email, $registration->name)->send(new FlexibleMail(
                $renderedSubject,
                $renderedBody,
                $registration
            ));

            $mailLog->markAsSent();
        } catch (\Exception $e) {
            $mailLog->markAsFailed($e->getMessage());
            throw $e;
        }

        return $mailLog;
    }

    /**
     * Preview a template with resolved variables.
     */
    public function previewTemplate(string $templateKey, ?Registration $registration = null): array
    {
        $template = MailTemplate::where('key', $templateKey)->first();

        if (! $template) {
            return ['error' => 'Template not found'];
        }

        $variables = $registration
            ? $this->variableResolver->resolve($registration)
            : $this->variableResolver->getSampleVariables();

        return $template->renderContent($variables);
    }

    /**
     * Get sent/failed/queued counts for a template key.
     */
    public function getTemplateStats(string $templateKey): array
    {
        $baseQuery = fn () => MailLog::where('template_key', $templateKey);

        return [
            'total_sent' => $baseQuery()->where('status', 'sent')->count(),
            'total_failed' => $baseQuery()->where('status', 'failed')->count(),
            'total_queued' => $baseQuery()->where('status', 'queued')->count(),
            'last_sent' => $baseQuery()->where('status', 'sent')->latest('sent_at')->first()?->sent_at,
        ];
    }

    private function findOrCreateQueuedMailLog(string $templateKey, Registration $registration, array $variables): MailLog
    {
        $mailLog = MailLog::where('registration_id', $registration->id)
            ->where('template_key', $templateKey)
            ->where('status', 'queued')
            ->latest()
            ->first();

        if (! $mailLog) {
            Log::info('[Mail Trail] No queued mail log found, creating new one', [
                'template_key' => $templateKey,
                'registration_id' => $registration->id,
            ]);

            $mailLog = MailLog::logEmail(
                templateKey: $templateKey,
                recipientEmail: $registration->email,
                registrationId: $registration->id,
                variables: $variables
            );

            Log::info('[Mail Trail] New mail log created', [
                'mail_log_id' => $mailLog->id,
            ]);
        } else {
            Log::info('[Mail Trail] Existing queued mail log found', [
                'mail_log_id' => $mailLog->id,
                'attempt_count' => $mailLog->attempt_count,
            ]);
        }

        return $mailLog;
    }

    /**
     * Replace {{key}} placeholders with variable values.
     */
    public function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Process Mustache-style conditionals: {{#key}}...{{/key}} and {{^key}}...{{/key}}.
     */
    public function processConditionals(string $content, array $variables): string
    {
        $content = preg_replace('/\{\{#([^}]+)\}\}/', '<!-- START: $1 -->', $content);
        $content = preg_replace('/\{\{\^([^}]+)\}\}/', '<!-- UNLESS: $1 -->', $content);
        $content = preg_replace('/\{\{\/([^}]+)\}\}/', '<!-- END: $1 -->', $content);

        $content = preg_replace_callback(
            '/<!-- START: ([^>]+) -->(.*?)<!-- END: \1 -->/s',
            fn ($matches) => ! empty($variables[$matches[1]]) ? $matches[2] : '',
            $content
        );

        $content = preg_replace_callback(
            '/<!-- UNLESS: ([^>]+) -->(.*?)<!-- END: \1 -->/s',
            fn ($matches) => empty($variables[$matches[1]]) ? $matches[2] : '',
            $content
        );

        return $content;
    }

    /**
     * Render a markdown body: replace variables, process conditionals, convert to HTML.
     */
    public function renderMarkdownBody(string $body, array $variables): string
    {
        $body = $this->replaceVariables($body, $variables);
        $body = $this->processConditionals($body, $variables);

        $config = [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        $converter = new MarkdownConverter($environment);

        $html = $converter->convert($body)->getContent();

        return $this->convertPanelBlockquotes($html);
    }

    /**
     * Render a plain text body: replace variables, process conditionals, strip HTML.
     */
    public function renderPlainTextBody(string $body, array $variables): string
    {
        $body = $this->replaceVariables($body, $variables);
        $body = $this->processConditionals($body, $variables);
        $body = preg_replace('/\[\!(success|warning|danger|note|)\]/i', '', $body);
        $body = strip_tags($body);
        $body = preg_replace('/\n\s*\n\s*\n/', "\n\n", $body);

        return trim($body);
    }

    /**
     * Convert blockquotes with [!type] markers to styled panel divs.
     */
    private function convertPanelBlockquotes(string $html): string
    {
        $html = preg_replace_callback(
            '/<blockquote>(.*?)<\/blockquote>/s',
            function ($matches) {
                $content = $matches[1];

                if (preg_match('/^\s*<p>\[\!(success|warning|danger|note)?\]\s*\n?/s', $content, $typeMatch)) {
                    $type = ! empty($typeMatch[1]) ? ' '.$typeMatch[1] : '';
                    $content = preg_replace('/^\s*<p>\[\!(success|warning|danger|note)?\]\s*\n?/s', '<p>', $content);

                    return '<div class="panel'.$type.'">'.$content.'</div>';
                }

                return '<blockquote>'.$content.'</blockquote>';
            },
            $html
        );

        return $html;
    }
}
