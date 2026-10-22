<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use Database\Factories\StudentFollowUpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Director's follow-up status and private note for one student (At Risk page).
 * student_id and updated_by are set explicitly in code.
 */
#[Fillable(['status', 'note'])]
#[Hidden(['note'])]
class StudentFollowUp extends Model
{
    /** @use HasFactory<StudentFollowUpFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FollowUpStatus::class,
            'note' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
