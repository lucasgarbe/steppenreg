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
            <h2 style="color: #059669;">🎉 Congratulations! You\'re in!</h2>
            
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
            
            <p><strong>Don\'t lose hope!</strong></p>
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
}