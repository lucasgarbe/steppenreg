# Configuration Guide for Other Clubs

This guide explains how to customize Steppenreg for your own cycling club or organization.

## Overview

All club and event-specific settings can be configured through the admin panel. No code changes are required.

## Initial Setup

After installing Steppenreg, follow these steps to customize it for your organization:

### 1. Environment Variables

Update your `.env` file with your email configuration:

```env
MAIL_FROM_ADDRESS=your-event@yourclub.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Admin Panel Configuration

1. Log in to the admin panel at `/admin`
2. Navigate to **Manage Event** (Settings icon in the navigation)
3. Configure the following sections:

#### Event Details

- **Event Name**: The name of your cycling event (e.g., "Tour de Example")
- **Current Application State**: Set the registration status

#### Organization / Club Information

Configure your club's branding and contact information:

- **Organization Name**: Full name of your club (e.g., "Example Cycling Club e.V.")
- **Organization Website**: Your club's main website URL (e.g., "https://example-cycling.com")
- **Contact Email**: Support email for participants (e.g., "registration@example-cycling.com")
- **Logo Filename**: Filename of your logo in the `public/` directory (e.g., "logo.png")
- **Event Website URL**: Direct link to your event page (optional, e.g., "https://example-cycling.com/tour")

### 3. Upload Your Logo

Place your organization's logo file in the `public/` directory of the application:

```bash
# Example
cp /path/to/your/logo.png public/logo.png
```

Make sure the filename matches what you entered in the admin panel.

### 4. Test Your Configuration

After saving your settings:

1. Clear the application cache:
   ```bash
   ./vendor/bin/sail artisan cache:clear
   ./vendor/bin/sail artisan config:clear
   ./vendor/bin/sail artisan view:clear
   ```

2. Visit the public registration page and verify:
   - Footer shows your organization name and links to your website
   - Contact email is your configured email
   - Page title and favicon use your branding
   - "Back to Home" button links to your event website

## Configurable Elements

The following elements will automatically use your configured settings:

### Branding

- **Page Titles**: Uses your Event Name
- **Favicon**: Uses your Logo Filename across all pages
- **Footer**: Displays your Organization Name with link to Organization Website

### Contact Information

- **Support Email**: Contact email is used on:
  - Registration success page
  - Event closed/waitlist pages
  - Email templates

### URLs

- **Back to Home Links**: Uses Event Website URL (falls back to Organization Website if not set)
- **Footer Links**: Links to Organization Website

### Dynamic Text

The following text automatically includes your event name:

- **German**: "Bist du schonmal beim {Event Name} mitgefahren?"
- **English**: "Have you participated in {Event Name} before?"

## Tips

1. **Logo Format**: Use PNG format with transparent background for best results
2. **Logo Size**: Recommended size is 32x32 pixels or 64x64 pixels for favicon
3. **URLs**: Always include `https://` in website URLs
4. **Email**: Use a monitored email address - participants will contact you here
5. **Testing**: Test all public pages after configuration to ensure everything displays correctly

## Troubleshooting

### Settings Not Updating

If your changes don't appear immediately:

1. Clear all caches:
   ```bash
   ./vendor/bin/sail artisan cache:clear
   ./vendor/bin/sail artisan config:clear
   ./vendor/bin/sail artisan view:clear
   ./vendor/bin/sail artisan settings:clear-cache
   ```

2. Refresh your browser (Ctrl+F5 or Cmd+Shift+R)

### Logo Not Displaying

1. Verify the logo file exists in `public/` directory
2. Check the filename exactly matches what you entered (case-sensitive)
3. Ensure file permissions allow web server to read the file

### Contact Email Not Working

1. Verify the email address is correctly entered in settings
2. Check your `.env` file has correct `MAIL_FROM_ADDRESS`
3. Test email configuration using Laravel's mail testing commands

## Need Help?

If you encounter issues during configuration, check the Laravel logs:

```bash
./vendor/bin/sail artisan log:show
```

Or contact the Steppenreg maintainers for support.
