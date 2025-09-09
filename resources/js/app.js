import './bootstrap';

document.addEventListener('DOMContentLoaded', function() {
    const emailLink = document.getElementById('contact-email');

    // Decode base64 encoded email
    const encodedEmail = emailLink.getAttribute('data-email');
    const decodedEmail = atob(encodedEmail);

    // Update link text to show decoded email
    emailLink.textContent = decodedEmail;

    // Create proper mailto link with subject
    const subject = encodeURIComponent('Question about {{ $eventSettings->event_name }}');
    emailLink.href = `mailto:${decodedEmail}?subject=${subject}`;
});
