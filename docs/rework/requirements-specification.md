# Requirements Specification

## Project Overview

**System Name**: Cycling Event Registration System  
**Purpose**: Manage participant registrations for cycling events with individual and team registrations, lottery-based selection, and waitlist management  
**Deployment**: Single deployment per event

---

## Functional Requirements

### FR-1: Registration Management

#### FR-1.1: Individual Registration
- Users can register for a track by providing personal information
- System validates registration during open period only
- System prevents duplicate registrations (same user, same track)
- System confirms registration and provides registration number
- Users receive confirmation email immediately

#### FR-1.2: Team Registration
- Users can specify team name during registration
- System creates team if doesn't exist, or joins existing team
- First registrant becomes team captain
- Team members can join until registration closes
- System validates all team members registered for same track
- Team size must be between 2-6 members (configurable)
- System notifies team members when someone joins

#### FR-1.3: Registration Updates
- Users can update personal information before draw
- Users cannot change track or team after registration
- Emergency contact information can be updated anytime

#### FR-1.4: Registration Cancellation
- Users can cancel registration anytime before event
- System confirms cancellation via email
- If confirmed participant cancels, system triggers waitlist promotion
- Team members can leave team before draw
- After draw, team cancellations must be handled as unit

### FR-2: Track Management

#### FR-2.1: Track Configuration
- Admin can create tracks with name, distance, description
- Admin sets capacity per track
- Admin sets registration open/close dates
- Admin sets draw date
- System displays track information to public

#### FR-2.2: Track Status
- System automatically opens/closes registration based on dates
- System prevents registration when track full
- System displays available spots in real-time
- System shows registration statistics to admins

### FR-3: Draw System

#### FR-3.1: Draw Execution
- Admin can execute draw after registration closes
- System validates draw prerequisites before execution
- System randomly selects registrations up to track capacity
- Teams are selected as atomic units (all or none)
- System marks selected registrations as "confirmed"
- System marks non-selected as "not_selected"
- Draw can only be executed once per track
- System logs draw execution with timestamp and results

#### FR-3.2: Draw Results
- System sends email to all participants with results
- Selected participants receive confirmation and payment info
- Non-selected participants receive waitlist information
- Admin can view draw statistics and results
- Results are final and cannot be modified

### FR-4: Waitlist Management

#### FR-4.1: Waitlist Enrollment
- System automatically enrolls non-selected registrations to waitlist
- Waitlist position assigned based on draw order (randomized)
- System sends waitlist confirmation with position
- Users can check current waitlist position

#### FR-4.2: Waitlist Promotion
- System automatically promotes next from waitlist when spot available
- Teams promoted as atomic units
- Promoted participants receive notification email
- Promoted participants have 48 hours to confirm
- If not confirmed, next in line promoted
- System updates waitlist positions after promotion

#### FR-4.3: Manual Promotion
- Admin can manually promote specific registrations
- System validates promotion eligibility
- System maintains waitlist integrity after manual changes

### FR-5: Notification System

#### FR-5.1: Automated Emails
- Registration confirmation
- Team join notification
- Draw result notification
- Waitlist enrollment confirmation
- Waitlist promotion notification
- Cancellation confirmation
- Payment reminders
- Event information updates

#### FR-5.2: Email Content
- All emails include registration details
- All emails include relevant dates and deadlines
- All emails include contact information for support
- Emails are sent asynchronously via queue

### FR-6: Reporting & Analytics

#### FR-6.1: Registration Reports
- Total registrations per track
- Individual vs team registrations breakdown
- Registration timeline (daily signups)
- Demographic information summary
- Completion rate (registered vs showed up)

#### FR-6.2: Draw Reports
- Draw results summary
- Selection statistics
- Team vs individual selection rates
- Waitlist statistics

#### FR-6.3: Export Functionality
- Export registrations to CSV/Excel
- Export draw results
- Export waitlist
- Export for race day check-in

---

## Non-Functional Requirements

### NFR-1: Performance
- System supports up to 10,000 concurrent users during registration opening
- Registration confirmation generated within 2 seconds
- Draw execution completes within 5 minutes for 5,000 registrations
- Waitlist promotion triggered within 1 minute of cancellation
- Page load time under 2 seconds for 95% of requests

### NFR-2: Reliability
- System uptime: 99.9% during registration period
- Zero data loss for completed registrations
- All email notifications delivered within 5 minutes
- Automatic retry for failed email delivery (3 attempts)
- Database backups every 6 hours during active period

### NFR-3: Security
- All user data encrypted at rest
- HTTPS for all communications
- Authentication required for admin functions
- Input validation on all user inputs
- Protection against SQL injection, XSS, CSRF
- Rate limiting on registration endpoint (5 attempts per IP per minute)
- GDPR compliant data handling

### NFR-4: Usability
- Mobile-responsive design
- Support for major browsers (Chrome, Firefox, Safari, Edge)
- Accessible (WCAG 2.1 Level AA)
- Multi-language support (English, German, French)
- Clear error messages
- Progress indicators for multi-step processes
- Confirmation dialogs for destructive actions

### NFR-5: Maintainability
- Modular architecture with clear domain boundaries
- Comprehensive unit and integration tests (80% coverage minimum)
- Automated deployment pipeline
- Logging for all critical operations
- Monitoring and alerting for system health
- Documentation for all APIs and services

### NFR-6: Scalability
- Horizontal scaling for web servers
- Queue system for background jobs
- Caching for frequently accessed data
- Database query optimization
- CDN for static assets

---

## User Stories

### Registration Phase

**US-1: Individual Registration**  
As a participant, I want to register for a cycling event track so that I can participate in the event.

Acceptance Criteria:
- Registration form includes all required fields
- System validates email format and phone number
- Confirmation email sent immediately
- Registration number provided
- System prevents duplicate registration

**US-2: Team Registration**  
As a participant, I want to register as part of a team so that I can ride with my friends.

Acceptance Criteria:
- Can specify team name during registration
- System creates or joins existing team
- Notified when team members join
- Can see current team members
- Team captain identified

**US-3: Check Registration Status**  
As a participant, I want to view my registration details so that I can verify my information.

Acceptance Criteria:
- Can access registration via email link or registration number
- Shows all registration details
- Shows team information if applicable
- Shows current status
- Shows next steps

**US-4: Update Registration**  
As a participant, I want to update my contact information so that I can be reached properly.

Acceptance Criteria:
- Can update personal details before draw
- Cannot change track or team
- Confirmation of update provided
- Update notification sent

**US-5: Cancel Registration**  
As a participant, I want to cancel my registration so that I can free my spot if I cannot participate.

Acceptance Criteria:
- Can cancel before event date
- Confirmation dialog shown
- Cancellation confirmation email sent
- Refund information provided

### Draw Phase

**US-6: Execute Draw**  
As an admin, I want to execute the draw so that I can fairly select participants.

Acceptance Criteria:
- Can only execute after registration closes
- Confirmation required before execution
- Progress indicator shown during execution
- Summary of results displayed
- All participants notified

**US-7: View Draw Results**  
As an admin, I want to view draw results so that I can see who was selected.

Acceptance Criteria:
- List of selected registrations
- List of non-selected registrations
- Statistics summary
- Export functionality
- Filter and search capability

**US-8: Receive Draw Result**  
As a participant, I want to receive my draw result so that I know if I can participate.

Acceptance Criteria:
- Email received within 5 minutes of draw
- Clear indication of selection status
- Next steps provided (payment for selected, waitlist info for non-selected)
- Contact information for questions

### Waitlist Phase

**US-9: View Waitlist Position**  
As a participant, I want to check my waitlist position so that I know my chances of promotion.

Acceptance Criteria:
- Can access via email link or registration number
- Shows current position
- Shows estimated promotion chance
- Shows number ahead in queue
- Updates in real-time

**US-10: Receive Promotion**  
As a participant, I want to be notified if promoted from waitlist so that I can confirm my participation.

Acceptance Criteria:
- Email notification received immediately
- Clear deadline for confirmation
- Payment information provided
- Link to confirm participation
- Countdown timer shown

**US-11: Promote from Waitlist**  
As an admin, I want the system to automatically promote from waitlist so that spots don't remain empty.

Acceptance Criteria:
- Automatic promotion when spot available
- Teams promoted as units
- Notifications sent immediately
- Waitlist positions updated
- Audit log maintained

### Team Management

**US-12: View Team**  
As a team member, I want to view my team details so that I can see who is on my team.

Acceptance Criteria:
- Shows all team members
- Shows registration status of each member
- Shows team captain
- Shows team size vs maximum
- Shows team status

**US-13: Leave Team**  
As a team member, I want to leave my team so that I can register individually or join another team.

Acceptance Criteria:
- Can leave before draw execution
- Cannot leave after draw
- Remaining team members notified
- Team deleted if last member leaves

---

## Data Requirements

### DR-1: Data Entities

**Registration Data**
- Personal information (name, email, phone, address)
- Emergency contact details
- Medical information
- Track selection
- Team association
- Registration timestamp
- Status history

**Team Data**
- Team name (unique per track)
- Track ID
- Captain registration ID
- Member count
- Creation timestamp

**Track Data**
- Name and description
- Distance and difficulty
- Capacity
- Registration dates
- Draw date
- Status

**Waitlist Data**
- Registration ID
- Track ID
- Position
- Enrollment timestamp

### DR-2: Data Retention
- Active registrations: Until event completion + 1 year
- Cancelled registrations: 90 days after cancellation
- Historical event data: 5 years for analytics
- User personal data: Deletable upon request (GDPR)

### DR-3: Data Volume Estimates
- Registrations per event: 2,000 - 5,000
- Tracks per event: 3 - 8
- Teams per track: 50 - 200
- Emails sent per event: 10,000 - 25,000

---

## System Constraints

### SC-1: Technical Constraints
- Must run on PHP 8.2+
- MySQL 8.0+ required
- Redis required for queue and cache
- Minimum 2GB RAM per web server
- 50GB storage per deployment

### SC-2: Business Constraints
- One deployment per event (no multi-tenancy)
- Draw can only be executed once
- Cannot modify registrations after draw
- Team size: 2-6 members (configurable)
- Waitlist promotion deadline: 48 hours

### SC-3: Integration Requirements
- Email service (SMTP or API-based like Postmark)
- Payment gateway for registration fees (future phase)
- SMS service for urgent notifications (future phase)

---

## Success Criteria

### Quantitative Metrics
- 95% registration completion rate (started to completed)
- <2% duplicate registration attempts
- 100% email delivery rate (excluding bounces)
- <1% waitlist promotion errors
- <5 support tickets per 1,000 registrations

### Qualitative Metrics
- Positive user feedback on registration process
- No critical bugs during registration period
- Admin satisfaction with draw and reporting tools
- Zero data loss incidents
- Successful event execution

---

## Risk Assessment

### High Risk
- **Registration system crashes during peak signup**: Mitigation - load testing, horizontal scaling, queue system
- **Draw algorithm error causing unfair selection**: Mitigation - thorough testing, audit logging, preview mode
- **Email delivery failures**: Mitigation - redundant email service, retry logic, SMS backup

### Medium Risk
- **Team formation confusion**: Mitigation - clear UI/UX, help documentation, support contact
- **Database performance issues with large datasets**: Mitigation - query optimization, indexing, caching
- **Waitlist promotion not triggering**: Mitigation - automated monitoring, manual override capability

### Low Risk
- **Browser compatibility issues**: Mitigation - cross-browser testing, progressive enhancement
- **Data export format issues**: Mitigation - multiple export formats, validation

---

## Future Enhancements

### Phase 2 Features
- Integrated payment processing
- Discount codes and promotional pricing
- Multiple event management (multi-tenancy)
- Mobile app for participants
- SMS notifications
- Social media integration for sharing

### Phase 3 Features
- Live race tracking
- Results management
- Photo gallery
- Certificate generation
- Historical analytics dashboard
- API for third-party integrations

---

## Glossary

**Registration**: A participant's application to join a track  
**Track**: A specific route/category within an event (e.g., 50km, 100km)  
**Team**: A group of participants registered together as a unit  
**Draw**: The lottery process to select participants when oversubscribed  
**Waitlist**: Queue of non-selected participants who may be promoted  
**Captain**: The first person to register for a team, with additional privileges  
**Atomic Unit**: Teams treated as single entities in draw and promotion  
**Promotion**: Moving a registration from waitlist to confirmed status
