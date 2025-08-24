@php
    $body = $template->body;
    
    // Replace variables in body
    foreach ($variables as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        $body = str_replace($placeholder, $value, $body);
    }
    
    // Handle team name conditional for plain text
    if (!empty($variables['team_name'])) {
        $body = preg_replace('/\{\{#team_name\}\}(.*?)\{\{\/team_name\}\}/s', '$1', $body);
    } else {
        $body = preg_replace('/\{\{#team_name\}\}(.*?)\{\{\/team_name\}\}/s', '', $body);
    }
    
    // Strip HTML tags for plain text
    $body = strip_tags($body);
    
    // Clean up whitespace
    $body = preg_replace('/\n\s*\n\s*\n/', "\n\n", $body);
    $body = trim($body);
@endphp

{!! $body !!}

---

This email was sent to {{ $registration->email }}
{{ $variables['event_name'] ?? 'Event' }} © {{ date('Y') }}