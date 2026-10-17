<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per staff view/action on a full Form 5 record.
 */
#[Fillable(['user_id', 'cumulative_record_id', 'entrance_test_result_id', 'action', 'description', 'ip'])]
class AccessLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_VIEW = 'view';

    public const ACTION_REVIEW = 'review';

    public const ACTION_VIEW_SATA = 'view_sata';

    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_SUBMIT = 'submit';

    public const ACTION_APPOINTMENT = 'appointment';

    public const ACTION_MANAGE = 'manage';

    public const ACTION_LOGIN = 'login';

    /**
     * Convenience for non-record actions that only carry a description.
     */
    public static function logAction(int $userId, string $action, string $description, ?string $ip = null): self
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip' => $ip,
        ]);
    }

    /**
     * @return BelongsTo<EntranceTestResult, $this>
     */
    public function entranceTestResult(): BelongsTo
    {
        return $this->belongsTo(EntranceTestResult::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CumulativeRecord, $this>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(CumulativeRecord::class, 'cumulative_record_id');
    }
}
