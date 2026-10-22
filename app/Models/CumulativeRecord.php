<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Support\Form5;
use App\Support\Form8;
use Database\Factories\CumulativeRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HDC Form 5: Student Cumulative Record.
 *
 * Workflow fields (user_id, status, completed_steps, submitted/reviewed/consent columns)
 * are intentionally not fillable; they are only set explicitly in code.
 */
#[Fillable([
    'school_year', 'semester', 'month_of_entry', 'student_type',
    'last_name', 'first_name', 'middle_name', 'program_id', 'year_level', 'major', 'home_address',
    'sex', 'civil_status', 'contact_number', 'email', 'birth_date', 'place_of_birth', 'nationality',
    'religion', 'ethnicity', 'boarding_address', 'emergency_contact_name', 'emergency_contact_address',
    'emergency_contact_number', 'spouse_name', 'number_of_children',
    'parents_status', 'family_view', 'family_concern', 'home_language',
    'height', 'weight', 'handicap', 'normal_eyesight', 'normal_hearing', 'persistent_illness',
    'hospitalized', 'hospitalization_reason', 'had_surgery', 'surgery_details', 'has_allergies', 'allergy_details',
    'scholarships_honors', 'schooling_nature', 'interruption_reason', 'scholarship_to_apply',
    'difficult_subjects', 'best_liked_subjects', 'home_facilities', 'recent_book',
    'course_reasons', 'course_reason_other', 'second_course', 'considered_occupation',
    'has_work_experience', 'work_experience_when', 'work_experience_details', 'work_experience_likes',
    'hobbies', 'self_description', 'skills_to_develop', 'skills_other',
    'willing_to_see_counselor', 'counselor_willingness_reason',
    'eating_disorder', 'depression', 'discrimination', 'pregnancy', 'abortion', 'anxiety_stress', 'financial',
    'alcohol_use', 'attempted_suicide', 'substance_abuse', 'body_dysmorphic', 'miscarriage', 'self_esteem',
    'sexual_assault', 'acculturation', 'loss_of_significant_others', 'concerns_specify',
    'family', 'same_sex', 'peers', 'significant_others', 'relationship_others', 'relationship_other',
    'praying', 'laughing', 'drinking', 'malling', 'listening_to_music', 'crying', 'going_out_with_peers',
    'partying', 'shopping', 'coping_others', 'coping_other',
])]
#[Hidden(['family_view', 'family_concern', 'photo_path', 'concerns_specify', 'relationship_other', 'coping_other'])]
class CumulativeRecord extends Model
{
    /** @use HasFactory<CumulativeRecordFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'consent_given' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $concernCasts = [
            ...array_fill_keys(Form5::concernYesNoFields(), 'boolean'),
            ...array_fill_keys(Form8::SPECIFY_FIELDS, 'encrypted'),
        ];

        return [
            ...$concernCasts,
            'status' => RecordStatus::class,
            'month_of_entry' => 'integer',
            'year_level' => 'integer',
            'number_of_children' => 'integer',
            'birth_date' => 'date',
            'parents_status' => 'array',
            'home_facilities' => 'array',
            'course_reasons' => 'array',
            'self_description' => 'array',
            'skills_to_develop' => 'array',
            'completed_steps' => 'array',
            'family_view' => 'encrypted',
            'family_concern' => 'encrypted',
            'normal_eyesight' => 'boolean',
            'normal_hearing' => 'boolean',
            'hospitalized' => 'boolean',
            'had_surgery' => 'boolean',
            'has_allergies' => 'boolean',
            'has_work_experience' => 'boolean',
            'willing_to_see_counselor' => 'boolean',
            'consent_given' => 'boolean',
            'consented_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Course preference chosen on the form.
     *
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<RecordParent, $this>
     */
    public function parents(): HasMany
    {
        return $this->hasMany(RecordParent::class);
    }

    /**
     * @return HasMany<RecordSibling, $this>
     */
    public function siblings(): HasMany
    {
        return $this->hasMany(RecordSibling::class)->orderBy('position');
    }

    /**
     * @return HasMany<RecordSchool, $this>
     */
    public function schools(): HasMany
    {
        return $this->hasMany(RecordSchool::class)->orderBy('position');
    }

    /**
     * @return HasMany<RecordOrganization, $this>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(RecordOrganization::class)->orderBy('position');
    }

    public function parent(string $relation): ?RecordParent
    {
        return $this->parents->firstWhere('relation', $relation);
    }

    /**
     * Students may edit until staff mark the record as reviewed.
     */
    public function isEditableByStudent(): bool
    {
        return $this->status !== RecordStatus::Reviewed;
    }

    public function hasCompletedStep(string $step): bool
    {
        return in_array($step, $this->completed_steps ?? [], true);
    }

    public function markStepCompleted(string $step): void
    {
        $this->completed_steps = array_values(array_unique([...$this->completed_steps ?? [], $step]));
    }

    /**
     * @return list<string>
     */
    public function missingSteps(): array
    {
        return array_values(array_diff(Form5::formSteps(), $this->completed_steps ?? []));
    }

    /**
     * Percentage of the form completed (submission counts as the final step).
     */
    public function progressPercent(): int
    {
        $total = count(Form5::formSteps()) + 1;
        $done = count(Form5::formSteps()) - count($this->missingSteps()) + ($this->submitted_at ? 1 : 0);

        return (int) round($done / $total * 100);
    }

    /**
     * First step the student still needs to complete, or the review step.
     */
    public function nextIncompleteStep(): string
    {
        return $this->missingSteps()[0] ?? 'review';
    }

    /**
     * Whether any Form 5 concern listed in config('hdc.follow_up_concerns') was answered Yes.
     */
    public function hasUrgentConcern(): bool
    {
        foreach (Form8::followUpConcerns() as $concern) {
            if ($this->{$concern} === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Psychological, relationship and coping items answered Yes (keys).
     *
     * @return list<string>
     */
    public function concernsAnsweredYes(): array
    {
        return array_values(array_filter(Form8::yesNoFields(), fn (string $field) => $this->{$field} === true));
    }

    /**
     * True for records submitted before the concerns step existed (no answers at all).
     */
    public function hasNoConcernAnswers(): bool
    {
        foreach (Form5::concernYesNoFields() as $field) {
            if ($this->{$field} !== null) {
                return false;
            }
        }

        return true;
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    public function fullName(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' ');
    }
}
