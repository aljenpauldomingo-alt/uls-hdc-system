{{-- Student identity line for SATA pages. Expects $student. --}}
<p class="text-sm text-gray-600 dark:text-gray-400">
    {{ $student->username }}
    · {{ $student->program?->code }} – {{ $student->program?->college?->name }}
    · {{ \App\Support\Form5::YEAR_LEVELS[$student->year_level] ?? '' }}
</p>
