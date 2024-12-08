<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Administrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestUser($role = 'Administrator')
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => $role,
            'must_change_password' => true
        ]);

        if ($role === 'Administrator') {
            Administrator::create([
                'user_id' => $user->user_id,
                'name' => 'Test',
                'surname' => 'Admin'
            ]);
        }

        return $user;
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = $this->createTestUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'user_id',
                        'email',
                        'role',
                        'administrator' => [
                            'admin_id',
                            'name',
                            'surname'
                        ]
                    ],
                    'token'
                ]);
    }

    public function test_user_cannot_login_with_incorrect_credentials()
    {
        $user = $this->createTestUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['credentials']);
    }

    public function test_user_can_view_profile()
    {
        $user = $this->createTestUser();
        
        $response = $this->actingAs($user)
                        ->getJson('/api/profile');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user_id',
                    'email',
                    'role',
                    'administrator' => [
                        'admin_id',
                        'name',
                        'surname'
                    ]
                ]);
    }

    public function test_user_can_update_profile()
    {
        $user = $this->createTestUser();
        
        $response = $this->actingAs($user)
                        ->putJson('/api/profile', [
                            'language_preference' => 'English',
                            'profile_picture_url' => 'https://example.com/photo.jpg'
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'language_preference' => 'English',
                    'profile_picture_url' => 'https://example.com/photo.jpg'
                ]);
    }

    public function test_user_can_change_password()
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
                        ->postJson('/api/change-password', [
                            'current_password' => 'password123',
                            'new_password' => 'newpassword123'
                        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Password updated successfully']);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password()
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
                        ->postJson('/api/change-password', [
                            'current_password' => 'wrongpassword',
                            'new_password' => 'newpassword123'
                        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['current_password']);
    }

    public function test_user_can_logout()
    {
        $user = $this->createTestUser();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->postJson('/api/logout');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Logged out successfully']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}