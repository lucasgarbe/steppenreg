<?php

namespace Database\Seeders;

use App\Domain\Mail\Models\MailTemplate;
use Illuminate\Database\Seeder;

class MailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'registration_confirmation',
                'name' => 'Registration Confirmation',
                'subject' => 'Welcome to {{event_name}} - Registration Confirmed!',
                'body' => $this->getRegistrationConfirmationBody(),
                'is_active' => true,
            ],
            [
                'key' => 'draw_success',
                'name' => 'Draw Success Notification',
                'subject' => 'Great news! You\'re confirmed for {{event_name}}',
                'body' => $this->getDrawSuccessBody(),
                'is_active' => true,
            ],
            [
                'key' => 'draw_waitlist',
                'name' => 'Draw Waitlist Notification',
                'subject' => 'You\'re on the waitlist for {{event_name}}',
                'body' => $this->getDrawWaitlistBody(),
                'is_active' => true,
            ],
            [
                'key' => 'draw_rejection',
                'name' => 'Draw Rejection Notification',
                'subject' => 'Update on your {{event_name}} registration',
                'body' => $this->getDrawRejectionBody(),
                'is_active' => true,
            ],
            [
                'key' => 'waitlist_registration_success',
                'name' => 'Waitlist Registration Confirmation',
                'subject' => 'You\'re on the {{event_name}} waitlist!',
                'body' => $this->getWaitlistRegistrationSuccessBody(),
                'is_active' => true,
            ],
            [
                'key' => 'withdrawal_confirmation',
                'name' => 'Withdrawal Confirmation',
                'subject' => 'Your withdrawal from {{event_name}} is confirmed',
                'body' => $this->getWithdrawalConfirmationBody(),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            MailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }

    private function getRegistrationConfirmationBody(): string
    {
        return '## Welcome to {{event_name}}!

Dear {{name}},

Thank you for registering for **{{event_name}}**! We have successfully received your registration and you are now in our system.

> [!success]
> **Registration Details:**
> - **Name:** {{name}}
> - **Email:** {{email}}
> - **Track:** {{track_name}} ({{track_distance}})
> - **Registration Date:** {{registration_date}}
> {{#team_name}}- **Team:** {{team_name}}{{/team_name}}

**What happens next?**

- Your registration is confirmed and saved in our system
- You will receive further updates about the draw process
- Keep an eye on your inbox for important event information

If you have any questions, please don\'t hesitate to contact us.

Best regards,
The {{event_name}} Team';
    }

    private function getDrawSuccessBody(): string
    {
        return '## Congratulations! You\'re in!

Dear {{name}},

Exciting news! You have been **selected** in the draw for **{{event_name}}**!

> [!success]
> **Your Participation Details:**
> - **Track:** {{track_name}} ({{track_distance}})
> - **Status:** {{draw_status}}
> {{#team_name}}- **Team:** {{team_name}}{{/team_name}}

**Next Steps:**

- Mark your calendar for the event date
- Start your training preparation
- Watch for additional event information and instructions
- Ensure your contact information is up to date

We\'re excited to see you at {{event_name}}!

Best regards,
The {{event_name}} Team';
    }

    private function getDrawWaitlistBody(): string
    {
        return '## You\'re on the waitlist

Dear {{name}},

Thank you for your interest in **{{event_name}}**. While we haven\'t been able to confirm your spot immediately, you\'ve been placed on our **waitlist**.

> [!warning]
> **Waitlist Information:**
> - **Track:** {{track_name}} ({{track_distance}})
> - **Status:** {{draw_status}}
> {{#team_name}}- **Team:** {{team_name}}{{/team_name}}

**What does this mean?**

- You have a good chance of getting a spot if others cancel
- We\'ll notify you immediately if a spot becomes available
- Keep training and stay prepared!
- No further action is needed from you at this time

We appreciate your patience and hope to see you at {{event_name}}!

Best regards,
The {{event_name}} Team';
    }

    private function getDrawRejectionBody(): string
    {
        return '## Update on your registration

Dear {{name}},

Thank you for your registration for **{{event_name}}**. Unfortunately, due to limited capacity, we weren\'t able to confirm your participation in this year\'s event.

> [!danger]
> **Registration Details:**
> - **Track:** {{track_name}} ({{track_distance}})
> - **Status:** {{draw_status}}
> {{#team_name}}- **Team:** {{team_name}}{{/team_name}}

We know this is disappointing news, and we truly appreciate your interest in participating.

**Other ways to stay involved:**

- Keep training for future events
- Follow us for updates about next year\'s registration
- Consider volunteering at this year\'s event
- You\'ll be among the first to know about future opportunities

Thank you for your understanding, and we hope to see you at a future {{event_name}} event!

Best regards,
The {{event_name}} Team';
    }

    private function getWaitlistRegistrationSuccessBody(): string
    {
        return '## {{#team_name}}Your team has{{/team_name}}{{^team_name}}You\'ve{{/team_name}} successfully joined the waitlist!

Dear {{name}},

Great news! {{#team_name}}Your team **{{team_name}}** has{{/team_name}}{{^team_name}}You have{{/team_name}} successfully joined the waitlist for **{{event_name}}**.

> [!warning]
> **Waitlist Status:**
> - **Track:** {{track_name}} ({{track_distance}})
> - **Status:** On Waitlist Pool
> {{#team_name}}- **Team:** {{team_name}}
> - **Team Members:** {{team_members_list}}{{/team_name}}
> - **Joined Waitlist:** {{waitlist_date}}

**How the waitlist works:**

{{#team_name}}- Your entire team is now in the waitlist pool
- Teams are kept together - if selected, all members are promoted
- Selection is done randomly from the pool (no positions)
{{/team_name}}{{^team_name}}- You\'re now in the waitlist pool
- Selection is done randomly from the pool (no positions)
- All waitlist entries have equal chance of selection
{{/team_name}}- If a spot becomes available, you\'ll be notified immediately
- Keep training and stay prepared!
- No further action is needed from you

> [!note]
> **Fair Selection:** Our waitlist uses a pool system where {{#team_name}}teams{{/team_name}}{{^team_name}}participants{{/team_name}} are selected randomly rather than by registration order. This ensures everyone has an equal opportunity!

We\'ll keep you updated on any changes to your status. Thank you for your patience!

Best regards,
The {{event_name}} Team';
    }

    private function getWithdrawalConfirmationBody(): string
    {
        return '## Withdrawal Confirmed

Dear {{name}},

We\'ve received and processed your withdrawal from **{{event_name}}**.

> [!danger]
> **Withdrawal Details:**
> - **Track:** {{track_name}} ({{track_distance}})
> - **Previous Status:** Confirmed Participant
> - **Current Status:** Withdrawn
> {{#team_name}}- **Team:** {{team_name}}{{/team_name}}
> - **Withdrawal Date:** {{withdrawal_date}}

We\'re sorry you won\'t be able to join us this time, but we understand that circumstances change.

> [!warning]
> **Your spot has been offered to someone on the waitlist**
> Thanks to your timely withdrawal, another participant will have the chance to experience {{event_name}}!

**Looking forward:**

- You\'re welcome to register for future events
- Follow us for updates about next year\'s {{event_name}}
- We hope to see you at a future event!

Thank you for letting us know about your change in plans. We wish you all the best!

Best regards,
The {{event_name}} Team';
    }
}
