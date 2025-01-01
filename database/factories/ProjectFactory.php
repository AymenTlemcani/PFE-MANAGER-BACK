<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'summary' => $this->faker->paragraph(),
            'technologies' => $this->faker->words(3, true),
            'material_needs' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['Classical', 'Innovative', 'StartUp', 'Patent', 'Internship']),
            'option' => $this->faker->randomElement(['GL', 'IA', 'RSD', 'SIC']),
            'status' => 'Proposed',
            'submission_date' => now(),
            'last_updated_date' => now()
            // Remove submitted_by from here, it will be set using state
        ];
    }

    public function submittedBy($user)
    {
        return $this->state(fn (array $attributes) => [
            'submitted_by' => $user->user_id  // Fix: Use user_id instead of id
        ]);
    }
}