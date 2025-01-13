<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $admin;
    private $nonAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'Administrator'
        ]);

        // Create non-admin user
        $this->nonAdmin = User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'Teacher'
        ]);
    }

    public function test_only_admin_can_list_users()
    {
        // Admin request
        $response = $this->actingAs($this->admin)->getJson('/api/users');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'pagination',
            'filters'
        ]);

        // Non-admin request
        $response = $this->actingAs($this->nonAdmin)->getJson('/api/users');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_user()
    {
        $userData = [
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'role' => 'Teacher',
            'language_preference' => 'English',
            'date_of_birth' => '1990-01-01'
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/users', $userData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => $userData['email']]);
    }

    public function test_admin_can_update_user()
    {
        $user = User::factory()->create();
        $updateData = [
            'email' => $this->faker->unique()->safeEmail,
            'language_preference' => 'French'
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/users/{$user->user_id}", $updateData);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => $updateData['email']]);
    }

    public function test_admin_can_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$user->user_id}");
        
        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['user_id' => $user->user_id]);
    }

    public function test_admin_cannot_delete_own_account()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$this->admin->user_id}");
        
        $response->assertStatus(400);
        $this->assertDatabaseHas('users', ['user_id' => $this->admin->user_id]);
    }

    public function test_validation_rules_for_user_creation()
    {
        $invalidData = [
            'email' => 'not-an-email',
            'password' => '123', // too short
            'role' => 'InvalidRole',
            'language_preference' => 'Spanish' // not allowed
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', $invalidData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'email',
            'password',
            'role',
            'language_preference'
        ]);
    }

    public function test_user_search_functionality()
    {
        User::factory()->create(['email' => 'searchtest@example.com']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/users?search=searchtest');
        
        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => 'searchtest@example.com']);
    }

    public function test_user_role_filtering()
    {
        // Create exactly 3 additional teachers
        User::factory()->count(3)->create(['role' => 'Teacher']);
        
        $response = $this->actingAs($this->admin)
            ->getJson('/api/users?role=Teacher');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'pagination',
            'filters'
        ]);
        
        // Get the actual count from the response
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(4, count($responseData['data']), 'Should have 4 teachers (3 new + 1 from setup)');
    }
}
