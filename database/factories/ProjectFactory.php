<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $user = User::factory()->create(['role' => $this->faker->randomElement(['Teacher', 'Student', 'Company'])]);

        return [
            'title' => $this->faker->sentence(),
            'summary' => $this->faker->paragraphs(3, true),
            'technologies' => $this->faker->words(3, true),
            'material_needs' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['Classical', 'Innovative', 'StartUp', 'Patent', 'Internship']),
            'option' => $this->faker->randomElement(['GL', 'IA', 'RSD', 'SIC']),
            'status' => 'Proposed',
            'submitted_by' => $user->user_id,
            'submission_date' => now(),
            'last_updated_date' => now()
        ];
    }

    public function submittedBy(User $user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'submitted_by' => $user->user_id
            ];
        });
    }
}