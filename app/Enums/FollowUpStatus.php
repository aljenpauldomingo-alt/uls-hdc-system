<?php

namespace App\Enums;

/**
 * Director's follow-up status for a student on the At Risk page.
 */
enum FollowUpStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case InterviewScheduled = 'interview_scheduled';
    case OngoingCounseling = 'ongoing_counseling';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::InterviewScheduled => 'Interview scheduled',
            self::OngoingCounseling => 'Ongoing counseling',
            self::Closed => 'Closed',
        };
    }

    /**
     * Tailwind classes for the status badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30',
            self::Contacted, self::InterviewScheduled => 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-300 dark:ring-blue-400/30',
            self::OngoingCounseling => 'bg-indigo-50 text-indigo-700 ring-indigo-700/10 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/30',
            self::Closed => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-300 dark:ring-green-400/30',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases()), 'label', 'value');
    }
}
