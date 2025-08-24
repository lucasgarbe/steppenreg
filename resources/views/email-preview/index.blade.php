<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template Preview</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .template-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .template-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .template-card h3 { margin-top: 0; color: #2c3e50; }
        .preview-link { background: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px; }
        .preview-link:hover { background: #2980b9; }
    </style>
</head>
<body>
    <h1>Email Template Preview</h1>
    <p>Preview all active email templates with sample data</p>
    
    <div class="template-list">
        @foreach($templates as $template)
            <div class="template-card">
                <h3>{{ $template->name }}</h3>
                <p><strong>Key:</strong> {{ $template->key }}</p>
                <p><strong>Subject:</strong> {{ $template->subject }}</p>
                <p>{{ Str::limit(strip_tags($template->body), 100) }}</p>
                
                <a href="{{ route('email.preview', $template->key) }}" class="preview-link" target="_blank">
                    Preview Email
                </a>
            </div>
        @endforeach
    </div>
    
    @if($templates->isEmpty())
        <p>No active email templates found.</p>
    @endif
</body>
</html>