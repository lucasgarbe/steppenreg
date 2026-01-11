@php
    $themeTextColor = $variables['theme_text_color'] ?? '#1a1a1a';
    $themeBackgroundColor = $variables['theme_background_color'] ?? '#fffdf8c2';
    $themePrimaryColor = $variables['theme_primary_color'] ?? '#F9C458';
    $themeAccentColor = $variables['theme_accent_color'] ?? '#7a58fc';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $template->name }}</title>
    <style>
        /* Email-safe CSS */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: <?php echo $themeTextColor; ?>;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: <?php echo $themeBackgroundColor; ?>;
        }

        .email-container {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }

        h1, h2, h3 {
            color: #2c3e50;
            margin-top: 0;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 20px;
            margin-bottom: 16px;
        }

        h3 {
            font-size: 18px;
            margin-bottom: 12px;
        }

        p {
            margin: 0 0 16px 0;
        }

        ul {
            margin: 0 0 16px 0;
            padding-left: 20px;
        }

        li {
            margin-bottom: 8px;
        }

        a {
            color: <?php echo $themePrimaryColor; ?>;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: <?php echo $themePrimaryColor; ?>;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
        }

        .button:hover {
            background-color: <?php echo $themePrimaryColor; ?>;
            opacity: 0.9;
            text-decoration: none;
        }

        .button.success {
            background-color: #27ae60;
        }

        .button.success:hover {
            background-color: #229954;
        }

        .button.warning {
            background-color: #f39c12;
        }

        .button.warning:hover {
            background-color: #d68910;
        }

        .button.danger {
            background-color: #e74c3c;
        }

        .button.danger:hover {
            background-color: #c0392b;
        }

        .panel {
            background-color: #f8f9fa;
            border-left: 4px solid <?php echo $themeAccentColor; ?>;
            padding: 15px;
            margin: 20px 0;
        }

        .panel.success {
            border-left-color: #27ae60;
            background-color: #d5f4e6;
        }

        .panel.warning {
            border-left-color: #f39c12;
            background-color: #fef9e7;
        }

        .panel.danger {
            border-left-color: #e74c3c;
            background-color: #fadbd8;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 14px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #7f8c8d;
            text-align: center;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }

            .email-container {
                padding: 20px;
                margin: 10px 0;
            }

            h1 {
                font-size: 20px;
            }

            .button {
                display: block;
                text-align: center;
                margin: 15px 0;
            }

            /* Responsive table styles */
            table {
                font-size: 12px;
            }

            th, td {
                padding: 6px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        @php
            $body = $template->body;

            // Replace variables in body before processing Markdown
            foreach ($variables as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $body = str_replace($placeholder, $value, $body);
            }

            // Replace Mustache-style conditionals with simple logic
            $body = preg_replace('/\{\{#([^}]+)\}\}/', '<!-- START: $1 -->', $body);
            $body = preg_replace('/\{\{\/([^}]+)\}\}/', '<!-- END: $1 -->', $body);

            // Handle team name conditional
            if (!empty($variables['team_name'])) {
                $body = preg_replace('/<!-- START: team_name -->(.*?)<!-- END: team_name -->/s', '$1', $body);
            } else {
                $body = preg_replace('/<!-- START: team_name -->(.*?)<!-- END: team_name -->/s', '', $body);
            }

            // Convert Markdown to HTML with table support
            $config = [
                'html_input' => 'allow',
                'allow_unsafe_links' => false,
            ];
            
            $environment = new \League\CommonMark\Environment\Environment($config);
            $environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());
            $environment->addExtension(new \League\CommonMark\Extension\Table\TableExtension());
            
            $converter = new \League\CommonMark\MarkdownConverter($environment);
            
            $htmlContent = $converter->convert($body)->getContent();
            
            echo $htmlContent;
        @endphp
    </div>

    <div class="footer">
        <p>This email was sent to {{ $registration->email }}</p>
        <p>{{ $variables['event_name'] ?? 'Event' }} &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
