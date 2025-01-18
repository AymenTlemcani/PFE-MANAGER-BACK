<?php

namespace Tests\Feature\Email;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\EmailTemplate;
use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericEmail;

class EmailsTest extends TestCase
{
    use RefreshDatabase;

    private EmailService $emailService;
    private $admin;
    private $template;

    public function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        
        $this->emailService = new EmailService();
        $this->admin = User::factory()->create(['role' => 'Administrator']);
        
        // Create base template for tests
        $this->template = EmailTemplate::create([
            'name' => 'test_template',
            'subject' => 'Test Campaign',
            'content' => 'Hello {name}, this is a test.',
            'type' => 'Notification',
            'language' => 'English',
            'is_active' => true
        ]);
    }

    // Template Management Tests
    public function test_admin_can_create_template()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/email/templates', [
            'name' => 'welcome_template',
            'subject' => 'Welcome to PFE Manager',
            'content' => 'Hello {name}, welcome to the platform!',
            'description' => 'Welcome email for new users',
            'placeholders' => ['name'],
            'type' => 'System',
            'language' => 'English',
            'is_active' => true
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('email_templates', [
            'name' => 'welcome_template',
            'type' => 'System'
        ]);
    }

    public function test_non_admin_cannot_access_email_management()
    {
        $nonAdmin = User::factory()->create(['role' => 'Teacher']);

        $response = $this->actingAs($nonAdmin)->postJson('/api/email/templates', [
            'name' => 'test_template',
            'subject' => 'Test',
            'content' => 'Test content',
            'type' => 'System',
            'language' => 'English'
        ]);

        $response->assertForbidden();
    }

    // Campaign Management Tests
    public function test_admin_can_create_and_activate_campaign()
    {
        // Create campaign
        $response = $this->actingAs($this->admin)->postJson('/api/email/campaigns', [
            'name' => 'Test Campaign',
            'type' => 'Notification',
            'target_audience' => 'Students',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(7),
            'template_id' => $this->template->template_id,
            'reminders' => [
                [
                    'days_before_deadline' => 1,
                    'send_time' => '09:00:00'
                ]
            ]
        ]);

        $response->assertStatus(201);
        $campaign = EmailCampaign::first();

        // Create target users and activate campaign
        User::factory(3)->create(['role' => 'Student']);
        
        $this->actingAs($this->admin)
            ->postJson("/api/email/campaigns/{$campaign->campaign_id}/activate")
            ->assertOk();

        $this->assertEquals('Active', $campaign->fresh()->status);
    }

    // Email Service Tests
    public function test_email_service_functionality()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'language_preference' => 'English'
        ]);

        Student::create([
            'user_id' => $user->user_id,
            'name' => 'John',
            'surname' => 'Doe',
            'master_option' => 'GL',
            'overall_average' => 15.50,
            'admission_year' => 2023
        ]);

        $result = $this->emailService->sendEmail(
            $user, 
            $this->template, 
            ['name' => 'John']
        );

        $this->assertTrue($result);
        
        Mail::assertSent(GenericEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    // Campaign Targeting Tests
    public function test_campaign_targeting_specific_audience()
    {
        \DB::beginTransaction();
        try {
            // Create users
            User::factory(2)->create(['role' => 'Student']);
            User::factory(2)->create(['role' => 'Teacher']);
            
            $campaign = EmailCampaign::create([
                'name' => 'Students Only',
                'type' => 'Notification',
                'target_audience' => 'Students',
                'start_date' => now(),
                'end_date' => now()->addDays(7),
                'status' => 'Draft'
            ]);

            $campaign->reminderSchedules()->create([
                'template_id' => $this->template->template_id,
                'days_before_deadline' => 1,
                'send_time' => '09:00:00'
            ]);

            $this->actingAs($this->admin)
                ->postJson("/api/email/campaigns/{$campaign->campaign_id}/activate");

            // Process queued jobs
            $this->artisan('queue:work --once');
            $this->artisan('queue:work --once');

            $studentEmailCount = EmailLog::whereHas('user', function($query) {
                $query->where('role', 'Student');
            })->count();

            $this->assertEquals(2, $studentEmailCount);
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    // Email Logging Tests
    public function test_email_logs_monitoring()
    {
        $user = User::factory()->create();
        $campaign = EmailCampaign::create([
            'name' => 'Test Campaign',
            'type' => 'Notification',
            'target_audience' => 'All',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'status' => 'Active'
        ]);

        EmailLog::create([
            'campaign_id' => $campaign->campaign_id,
            'template_id' => $this->template->template_id,
            'recipient_email' => $user->email,
            'user_id' => $user->user_id,
            'status' => 'Sent',
            'sent_at' => now()
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/email/campaigns/{$campaign->campaign_id}/logs");

        $response->assertOk();
    }

    // Campaign Status Tests
    public function test_campaign_status_lifecycle()
    {
        $campaign = EmailCampaign::create([
            'name' => 'Lifecycle Test',
            'type' => 'Notification',
            'target_audience' => 'All',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'status' => 'Draft'
        ]);

        $campaign->reminderSchedules()->create([
            'template_id' => $this->template->template_id,
            'days_before_deadline' => 1,
            'send_time' => '09:00:00'
        ]);

        // Test activation
        $this->actingAs($this->admin)
            ->postJson("/api/email/campaigns/{$campaign->campaign_id}/activate");
        $this->assertEquals('Active', $campaign->fresh()->status);

        // Test completion
        $campaign->update(['end_date' => now()->subDay()]);
        $this->artisan('emails:check-completed-campaigns');
        $this->assertEquals('Completed', $campaign->fresh()->status);
    }
}
