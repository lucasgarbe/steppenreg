<?php

namespace Database\Seeders;

use App\Models\MailTemplate;
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
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #2563eb;">Welcome to {{event_name}}!</h2>
            
            <p>Dear {{name}},</p>
            
            <p>Thank you for registering for <strong>{{event_name}}</strong>! We have successfully received your registration and you are now in our system.</p>
            
            <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #374151;">Registration Details:</h3>
                <ul style="list-style: none; padding: 0;">
                    <li><strong>Name:</strong> {{name}}</li>
                    <li><strong>Email:</strong> {{email}}</li>
                    <li><strong>Track:</strong> {{track_name}} ({{track_distance}})</li>
                    <li><strong>Registration Date:</strong> {{registration_date}}</li>
                    {{#team_name}}<li><strong>Team:</strong> {{team_name}}</li>{{/team_name}}
                </ul>
            </div>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>Your registration is confirmed and saved in our system</li>
                <li>You will receive further updates about the draw process</li>
                <li>Keep an eye on your inbox for important event information</li>
            </ul>
            
            <p>If you have any questions, please don\'t hesitate to contact us.</p>
            
            <p>Best regards,<br>
            The {{event_name}} Team</p>
        </div>';
    }

    private function getDrawSuccessBody(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #059669;">Congratulations! You\'re in!</h2>
            
            <p>Dear {{name}},</p>
            
            <p>Exciting news! You have been <strong>selected</strong> in the draw for <strong>{{event_name}}</strong>!</p>
            
            <div style="background-color: #d1fae5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #059669;">
                <h3 style="margin-top: 0; color: #065f46;">Your Participation Details:</h3>
                <ul style="list-style: none; padding: 0;">
                    <li><strong>Track:</strong> {{track_name}} ({{track_distance}})</li>
                    <li><strong>Status:</strong> {{draw_status}}</li>
                    {{#team_name}}<li><strong>Team:</strong> {{team_name}}</li>{{/team_name}}
                </ul>
            </div>
            
            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Mark your calendar for the event date</li>
                <li>Start your training preparation</li>
                <li>Watch for additional event information and instructions</li>
                <li>Ensure your contact information is up to date</li>
            </ul>
            
            <div style="background-color: #fee2e2; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;">
                <p><strong>Need to withdraw?</strong></p>
                <p>If your plans change and you can\'t participate, please let us know as soon as possible so we can offer your spot to someone on the waitlist.</p>
                <p style="text-align: center;">
                    <a href="{{withdraw_url}}" style="background-color: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">Withdraw from Event</a>
                </p>
            </div>
            
            <p>We\'re excited to see you at {{event_name}}!</p>
            
            <p>Best regards,<br>
            The {{event_name}} Team</p>
        </div>';
    }

    private function getDrawWaitlistBody(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #d97706;">You\'re on the waitlist</h2>
            
            <p>Dear {{name}},</p>
            
            <p>Thank you for your interest in <strong>{{event_name}}</strong>. While we haven\'t been able to confirm your spot immediately, you\'ve been placed on our <strong>waitlist</strong>.</p>
            
            <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #d97706;">
                <h3 style="margin-top: 0; color: #92400e;">Waitlist Information:</h3>
                <ul style="list-style: none; padding: 0;">
                    <li><strong>Track:</strong> {{track_name}} ({{track_distance}})</li>
                    <li><strong>Status:</strong> {{draw_status}}</li>
                    {{#team_name}}<li><strong>Team:</strong> {{team_name}}</li>{{/team_name}}
                </ul>
            </div>
            
            <p><strong>What does this mean?</strong></p>
            <ul>
                <li>You have a good chance of getting a spot if others cancel</li>
                <li>We\'ll notify you immediately if a spot becomes available</li>
                <li>Keep training and stay prepared!</li>
                <li>No further action is needed from you at this time</li>
            </ul>
            
            <p>We appreciate your patience and hope to see you at {{event_name}}!</p>
            
            <p>Best regards,<br>
            The {{event_name}} Team</p>
        </div>';
    }

    private function getDrawRejectionBody(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #374151;">Update on your registration</h2>
            
            <p>Dear {{name}},</p>
            
            <p>Thank you for your registration for <strong>{{event_name}}</strong>. Unfortunately, due to limited capacity, we weren\'t able to confirm your participation in this year\'s event.</p>
            
            <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #374151;">Registration Details:</h3>
                <ul style="list-style: none; padding: 0;">
                    <li><strong>Track:</strong> {{track_name}} ({{track_distance}})</li>
                    <li><strong>Status:</strong> {{draw_status}}</li>
                    {{#team_name}}<li><strong>Team:</strong> {{team_name}}</li>{{/team_name}}
                </ul>
            </div>
            
            <p>We know this is disappointing news, and we truly appreciate your interest in participating.</p>
            
            <div style="background-color: #dbeafe; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
                <p><strong>Still want to participate?</strong></p>
                <p>You can join our waitlist! If someone withdraws, you might still get a chance to participate.</p>
                <p style="text-align: center;">
                    <a href="{{waitlist_url}}" style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">Join Waitlist</a>
                </p>
            </div>
            
            <p><strong>Other ways to stay involved:</strong></p>
            <ul>
                <li>Keep training for future events</li>
                <li>Follow us for updates about next year\'s registration</li>
                <li>Consider volunteering at this year\'s event</li>
                <li>You\'ll be among the first to know about future opportunities</li>
            </ul>
            
            <p>Thank you for your understanding, and we hope to see you at a future {{event_name}} event!</p>
            
            <p>Best regards,<br>
            The {{event_name}} Team</p>
        </div>';
    }

    private function getWaitlistRegistrationSuccessBody(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #d97706;">{{#team_name}}Your team has{{/team_name}}{{^team_name}}You\'ve{{/team_name}} successfully joined the waitlist!</h2>
            
            <p>Dear {{name}},</p>
            
            <p>Great news! {{#team_name}}Your team <strong>{{team_name}}</strong> has{{/team_name}}{{^team_name}}You have{{/team_name}} successfully joined the waitlist for <strong>{{event_name}}</strong>.</p>
            
            <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #d97706;">
                <h3 style="margin-top: 0; color: #92400e;">Waitlist Status:</h3>
                <ul style="list-style: none; padding: 0;">
                    <li><strong>Track:</strong> {{track_name}} ({{track_distance}})</li>
                    <li><strong>Status:</strong> On Waitlist Pool</li>
                    <li><strong>System:</strong> {{waitlist_position}}</li>
                    {{#team_name}}
                    <li><strong>Team:</strong> {{team_name}}</li>
                    <li><strong>Team Members:</strong> {{team_members_list}}</li>
                    {{/team_name}}
                    <li><strong>Joined Waitlist:</strong> {{waitlist_date}}</li>
                </ul>
            </div>
            
            <p><strong>How the waitlist works:</strong></p>
            <ul>
                {{#team_name}}
                <li>Your entire team is now in the waitlist pool</li>
                <li>Teams are kept together - if selected, all members are promoted</li>
                <li>Selection is done randomly from the pool (no positions)</li>
                {{/team_name}}
                {{^team_name}}
                <li>You\'re now in the waitlist pool</li>
                <li>Selection is done randomly from the pool (no positions)</li>
                <li>All waitlist entries have equal chance of selection</li>
                {{/team_name}}
                <li>If a spot becomes available, you\'ll be notified immediately</li>
                <li>Keep training and stay prepared!</li>
                <li>No further action is needed from you</li>
            </ul>
            
            <div style="background-color: #e0e7ff; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <p><strong>Fair Selection:</strong> Our waitlist uses a pool system where {{#team_name}}teams{{/team_name}}{{^team_name}}participants{{/team_name}} are selected randomly rather than by registration order. This ensures everyone has an equal opportunity!</p>
            </div>
            
            <p>We\'ll keep you updated on any changes to your status. Thank you for your patience!</p>
            
            <p>Best regards,<br>
            The {{event_name}} Team</p>
        </div>';
    }

    private function getWithdrawalConfirmationBody(): string
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #374151;">Withdrawal Confirmed</h2>
            
            <p>Dear {{name}},</p>
            
            <p>We\'ve received and processed your withdrawal from <strong>{{event_name}}</strong>.</p>
            
            <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #374151;">Withdrawal Details:</h3>
                <ul style="list-style: none; padding: 0;">
                    <li><strong>Track:</strong> {{track_name}} ({{track_distance}})</li>
                    <li><strong>Previous Status:</strong> Confirmed Participant</li>
                    <li><strong>Current Status:</strong> Withdrawn</li>
                    {{#team_name}}<li><strong>Team:</strong> {{team_name}}</li>{{/team_name}}
                    <li><strong>Withdrawal Date:</strong> {{withdrawal_date}}</li>
                    {{#withdrawal_reason}}<li><strong>Reason:</strong> {{withdrawal_reason}}</li>{{/withdrawal_reason}}
                </ul>
            </div>
            
            <p>We\'re sorry you won\'t be able to join us this time, but we understand that circumstances change.</p>
            
            <div style="background-color: #dbeafe; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
                <p><strong>Your spot has been offered to someone on the waitlist</strong></p>
                <p>Thanks to your timely withdrawal, another participant will have the chance to experience {{event_name}}!</p>
            </div>
            
            <p><strong>Looking forward:</strong></p>
            <ul>
                <li>You\'re welcome to register for future events</li>
                <li>Follow us for updates about next year\'s {{event_name}}</li>
                <li>We hope to see you at a future event!</li>
            </ul>
            
            <p>Thank you for letting us know about your change in plans. We wish you all the best!</p>
            
            <p>Best regards,<br>
            The {{event_name}} Team</p>
        </div>';
    }
}