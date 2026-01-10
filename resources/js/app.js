import './bootstrap';

document.addEventListener('DOMContentLoaded', function() {
    const emailLink = document.getElementById('contact-email');

    if (emailLink) {
        // Decode base64 encoded email
        const encodedEmail = emailLink.getAttribute('data-email');
        const decodedEmail = atob(encodedEmail);

        // Update link text to show decoded email
        emailLink.textContent = decodedEmail;

        // Get translated email subject from data attribute
        const emailSubject = emailLink.getAttribute('data-email-subject');
        
        // Create proper mailto link with subject
        const subject = encodeURIComponent(emailSubject);
        emailLink.href = `mailto:${decodedEmail}?subject=${subject}`;
    }
});
