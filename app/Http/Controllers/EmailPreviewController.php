<?php

namespace App\Http\Controllers;

use App\Mail\TemplateBasedEmail;
use App\Models\MailTemplate;
use App\Models\Registration;
use App\Services\MailVariableResolver;
use Illuminate\Http\Request;

class EmailPreviewController extends Controller
{
    public function preview(Request $request, string $templateKey)
    {
        $template = MailTemplate::where('key', $templateKey)->first();
        
        if (!$template) {
            abort(404, 'Template not found');
        }
        
        // Use a sample registration or find a real one
        $registration = Registration::first();
        if (!$registration) {
            abort(404, 'No registrations found for preview');
        }
        
        $resolver = new MailVariableResolver();
        $variables = $resolver->resolve($registration);
        
        $mailable = new TemplateBasedEmail($template, $registration, $variables);
        
        // If source code is requested, return HTML source
        if ($request->has('source')) {
            $html = $mailable->render();
            return response($html, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8'
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
