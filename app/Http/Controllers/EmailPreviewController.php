<?php

namespace App\Http\Controllers;

use App\Domain\Mail\Mail\TemplateBasedEmail;
use App\Domain\Mail\Models\MailTemplate;
use App\Domain\Mail\Services\MailTemplateService;
use App\Domain\Mail\Services\VariableResolver;
use App\Models\Registration;
use Illuminate\Http\Request;

class EmailPreviewController extends Controller
{
    public function preview(Request $request, string $templateKey, MailTemplateService $mailService)
    {
        $template = MailTemplate::where('key', $templateKey)->first();

        if (! $template) {
            abort(404, 'Template not found');
        }

        $registration = Registration::first();
        if (! $registration) {
            abort(404, 'No registrations found for preview');
        }

        $preview = $mailService->previewTemplate($templateKey, $registration);

        if (isset($preview['error'])) {
            abort(404, $preview['error']);
        }

        $variables = app(VariableResolver::class)->resolve($registration);
        $renderedSubject = $mailService->replaceVariables($template->subject, $variables);
        $renderedHtmlBody = $mailService->renderMarkdownBody($template->body, $variables);
        $renderedTextBody = $mailService->renderPlainTextBody($template->body, $variables);

        $mailable = new TemplateBasedEmail(
            $renderedSubject,
            $renderedHtmlBody,
            $renderedTextBody,
            $registration,
            $variables
        );

        if ($request->has('source')) {
            $html = $mailable->render();

            return response($html, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        return $mailable;
    }

    public function index()
    {
        $templates = MailTemplate::where('is_active', true)->get();

        return view('email-preview.index', compact('templates'));
    }
}
