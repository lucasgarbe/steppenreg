<?php

namespace App\Domain\Mail\Services;

use App\Domain\Mail\Mail\FlexibleMail;
use App\Domain\Mail\Mail\TemplateBasedEmail;
use App\Domain\Mail\Models\MailLog;
use App\Domain\Mail\Models\MailTemplate;
use App\Models\Registration;
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
        $template = MailTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        $variables = $this->variableResolver->resolve($registration);

        $renderedSubject = $this->replaceVariables($template->subject, $variables);
        $renderedHtmlBody = $this->renderMarkdownBody($template->body, $variables);
        $renderedTextBody = $this->renderPlainTextBody($template->body, $variables);

        $mailLog = $this->findOrCreateQueuedMailLog($templateKey, $registration, $variables);

        try {
            $mailable = new TemplateBasedEmail(
                $renderedSubject,
                $renderedHtmlBody,
                $renderedTextBody,
                $registration,
                $variables
            );
            Mail::to($registration->email, $registration->name)->send($mailable);

            $mailLog->markAsSent();
        } catch (\Exception $e) {
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
            $mailLog = MailLog::logEmail(
                templateKey: $templateKey,
                recipientEmail: $registration->email,
                registrationId: $registration->id,
                variables: $variables
            );
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
     * Process Mustache-style {{#key}}...{{/key}} conditionals.
     */
    public function processConditionals(string $content, array $variables): string
    {
        $content = preg_replace('/\{\{#([^}]+)\}\}/', '<!-- START: $1 -->', $content);
        $content = preg_replace('/\{\{\/([^}]+)\}\}/', '<!-- END: $1 -->', $content);

        if (! empty($variables['team_name'])) {
            $content = preg_replace('/<!-- START: team_name -->(.*?)<!-- END: team_name -->/s', '$1', $content);
        } else {
            $content = preg_replace('/<!-- START: team_name -->(.*?)<!-- END: team_name -->/s', '', $content);
        }

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

        return $converter->convert($body)->getContent();
    }

    /**
     * Render a plain text body: replace variables, process conditionals, strip HTML.
     */
    public function renderPlainTextBody(string $body, array $variables): string
    {
        $body = $this->replaceVariables($body, $variables);
        $body = $this->processConditionals($body, $variables);
        $body = strip_tags($body);
        $body = preg_replace('/\n\s*\n\s*\n/', "\n\n", $body);

        return trim($body);
    }
}
