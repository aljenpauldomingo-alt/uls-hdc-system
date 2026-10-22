@php
    use App\Support\Form5;
    use App\Support\Form8;

    $answer = function (string $field) use ($record): ?string {
        $value = session()->hasOldInput() ? old($field) : $record->{$field};

        return $value === null || $value === '' ? null : ((string) (int) $value);
    };
    $initial = collect(Form5::concernYesNoFields())->mapWithKeys(fn (string $field) => [$field => $answer($field)])->all();
    $urgent = Form8::followUpConcerns();
    $support = config('hdc.student_support');
    $columns = fn (array $items) => array_chunk($items, (int) ceil(count($items) / 2), true);
    $groupHeading = 'text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-gray-100';
@endphp

<div x-data="{ answers: @js($initial), urgent: @js($urgent) }"
    x-on:change="if ($event.target.type === 'radio') answers[$event.target.name] = $event.target.value"
    class="space-y-8">

    <p class="rounded-md border border-indigo-200/70 bg-indigo-50/80 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-400/20 dark:bg-indigo-500/10 dark:text-indigo-200">
        {{ __('Your answers are confidential and are only seen by the HDC Director. They help the HDC support you.') }}
    </p>

    @if ($errors->hasAny(Form5::concernYesNoFields()))
        <div role="alert" class="rounded-md border border-red-300/70 bg-red-50/90 px-4 py-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-950/60 dark:text-red-200">
            {{ __('Please answer Yes or No for every item.') }}
        </div>
    @endif

    <div role="note" aria-live="polite" x-show="urgent.some((key) => answers[key] === '1')" x-cloak
        class="rounded-md border border-teal-200/80 bg-teal-50/90 px-4 py-4 text-sm text-teal-900 dark:border-teal-400/20 dark:bg-teal-500/10 dark:text-teal-100"
        data-student-support>
        <p class="font-semibold">{{ $support['title'] }}</p>
        <p class="mt-1">{{ $support['message'] }}</p>
        <p class="mt-2">{{ $support['office_contact'] }}</p>
        <p class="mt-2">{{ $support['hotline'] }}</p>
    </div>

    <fieldset>
        <legend class="{{ $groupHeading }}">{{ __('Psychological Concerns') }}</legend>
        <div class="mt-2 grid gap-x-10 sm:grid-cols-2">
            @foreach ($columns(Form8::CONCERNS) as $column)
                <div class="divide-y divide-gray-200/70 dark:divide-white/10">
                    @foreach ($column as $key => $label)
                        <x-form.yes-no-pair :name="$key" :label="$label" :value="$record->{$key}" required />
                    @endforeach
                </div>
            @endforeach
        </div>
        <x-form.text name="concerns_specify" label="If yes, specify (loss of significant others: who)" :value="$record->concerns_specify"
            class="mt-4" maxlength="1000" x-show="answers.{{ Form5::CONCERN_SPECIFY_TRIGGER }} === '1'" x-cloak
            x-bind:required="answers.{{ Form5::CONCERN_SPECIFY_TRIGGER }} === '1'" />
    </fieldset>

    <fieldset>
        <legend class="{{ $groupHeading }}">{{ __('Relationship Concerns') }}</legend>
        <div class="mt-2 grid gap-x-10 sm:grid-cols-2">
            @foreach ($columns([...Form8::RELATIONSHIP_CONCERNS, 'relationship_others' => 'Others']) as $column)
                <div class="divide-y divide-gray-200/70 dark:divide-white/10">
                    @foreach ($column as $key => $label)
                        <x-form.yes-no-pair :name="$key" :label="$label" :value="$record->{$key}" required />
                    @endforeach
                </div>
            @endforeach
        </div>
        <x-form.text name="relationship_other" label="Others: specify" :value="$record->relationship_other"
            class="mt-4" maxlength="1000" x-show="answers.relationship_others === '1'" x-cloak
            x-bind:required="answers.relationship_others === '1'" />
    </fieldset>

    <fieldset>
        <legend class="{{ $groupHeading }}">{{ __('Coping Strategies') }}</legend>
        <div class="mt-2 grid gap-x-10 sm:grid-cols-2">
            @foreach ($columns([...Form8::COPING_STRATEGIES, 'coping_others' => 'Others']) as $column)
                <div class="divide-y divide-gray-200/70 dark:divide-white/10">
                    @foreach ($column as $key => $label)
                        <x-form.yes-no-pair :name="$key" :label="$label" :value="$record->{$key}" required />
                    @endforeach
                </div>
            @endforeach
        </div>
        <x-form.text name="coping_other" label="Others: specify" :value="$record->coping_other"
            class="mt-4" maxlength="1000" x-show="answers.coping_others === '1'" x-cloak
            x-bind:required="answers.coping_others === '1'" />
    </fieldset>
</div>
