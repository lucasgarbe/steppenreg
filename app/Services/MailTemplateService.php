<?php

namespace App\Services;

use App\Mail\TemplateBasedEmail;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Mail;

class MailTemplateService
{
    public function __construct(
        private MailVariableResolver $variableResolver
    ) {}

    public function sendEmail(string $templateKey, Registration $registration): ?MailLog
    {
        $template = MailTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return null;
        }

        $variables = $this->variableResolver->resolve($registration);

        $mailLog = MailLog::logEmail(
            templateKey: $templateKey,
            recipientEmail: $registration->email,
            registrationId: $registration->id,
            variables: $variables
        );

        try {
            $mailable = new TemplateBasedEmail($template, $registration, $variables);
            Mail::to($registration->email, $registration->name)->send($mailable);

            $mailLog->markAsSent();
        } catch (\Exception $e) {
            $mailLog->markAsFailed($e->getMessage());
        }

        return $mailLog;
    }

    public function previewTemplate(string $templateKey, ?Registration $registration = null): array
    {
        $template = MailTemplate::where('key', $templateKey)->first();
        
        if (!$template) {
            return ['error' => 'Template not found'];
        }

        $variables = $registration 
            ? $this->variableResolver->resolve($registration)
            : $this->variableResolver->getSampleVariables();

        return $template->renderContent($variables);
    }

    public function getTemplateStats(string $templateKey): array
    {
        $logs = MailLog::where('template_key', $templateKey);

        return [
            'total_sent' => $logs->where('status', 'sent')->count(),
            'total_failed' => $logs->where('status', 'failed')->count(),
            'total_queued' => $logs->where('status', 'queued')->count(),
            'last_sent' => $logs->where('status', 'sent')->latest('sent_at')->first()?->sent_at,
        ];
    }
}