<?php

namespace Database\Factories;

use App\Enums\FollowUpStatus;
use App\Models\StudentFollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentFollowUp>
 */
class StudentFollowUpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'status' => FollowUpStatus::Contacted,
            'note' => null,
            'updated_by' => User::factory()->director(),
        ];
    }
}
