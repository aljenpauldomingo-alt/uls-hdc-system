<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Models\CumulativeRecord;
use App\Models\User;
use App\Support\Form5;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CumulativeRecord>
 */
class CumulativeRecordFactory extends Factory
{
    /**
     * A draft with only the entry step done.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'school_year' => '2026-2027',
            'semester' => '1st',
            'month_of_entry' => 8,
            'student_type' => 'new',
            'status' => RecordStatus::Draft,
            'completed_steps' => ['entry'],
        ];
    }

    /**
     * Every form step answered, ready to submit.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'home_address' => fake()->address(),
            'sex' => 'female',
            'civil_status' => 'single',
            'contact_number' => '09171234567',
            'email' => fake()->safeEmail(),
            'birth_date' => '2008-05-14',
            'place_of_birth' => 'Santiago City',
            'nationality' => 'Filipino',
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_address' => fake()->address(),
            'emergency_contact_number' => '09181234567',
            'parents_status' => ['married_in_church'],
            'normal_eyesight' => true,
            'normal_hearing' => true,
            'hospitalized' => false,
            'had_surgery' => false,
            'has_allergies' => false,
            'schooling_nature' => 'continuous',
            'course_reasons' => ['personal_choice'],
            'has_work_experience' => false,
            'self_description' => ['careful', 'serious', 'calm', 'dedicated', 'quiet'],
            'skills_to_develop' => ['study_skills'],
            'willing_to_see_counselor' => true,
            ...array_fill_keys(Form5::concernYesNoFields(), false),
            'completed_steps' => Form5::formSteps(),
        ]);
    }

    public function submitted(): static
    {
        return $this->completed()->state(fn (array $attributes) => [
            'status' => RecordStatus::Submitted,
            'submitted_at' => now(),
            'consent_given' => true,
            'consented_at' => now(),
            'consent_name' => 'Student Name',
        ]);
    }

    public function reviewed(): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'status' => RecordStatus::Reviewed,
            'reviewed_at' => now(),
            'reviewed_by' => User::factory()->director(),
        ]);
    }
}
