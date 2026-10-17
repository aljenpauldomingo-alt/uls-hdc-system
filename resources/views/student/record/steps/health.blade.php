@php use App\Support\FormName; @endphp

<div x-data="{
        hospitalized: @js(FormName::current('hospitalized', $record->hospitalized)),
        hadSurgery: @js(FormName::current('had_surgery', $record->had_surgery)),
        hasAllergies: @js(FormName::current('has_allergies', $record->has_allergies)),
    }" class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-2">
        <x-form.text name="height" label="Height" :value="$record->height" maxlength="30" placeholder="e.g. 5'4&quot; or 163 cm" />
        <x-form.text name="weight" label="Weight" :value="$record->weight" maxlength="30" placeholder="e.g. 55 kg" />
    </div>

    <x-form.text name="handicap" label="Any handicap? (specify)" :value="$record->handicap" maxlength="255" />

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form.yes-no name="normal_eyesight" label="Normal eyesight" :value="$record->normal_eyesight" />
        <x-form.yes-no name="normal_hearing" label="Normal hearing" :value="$record->normal_hearing" />
    </div>

    <x-form.text name="persistent_illness" label="Any persistent illness?" :value="$record->persistent_illness" maxlength="255" />

    <div class="space-y-3">
        <x-form.yes-no name="hospitalized" label="Have you been hospitalized?" :value="$record->hospitalized" model="hospitalized" />
        <x-form.text name="hospitalization_reason" label="Reason for hospitalization" :value="$record->hospitalization_reason" required maxlength="255"
            x-show="hospitalized === '1'" x-cloak />
    </div>

    <div class="space-y-3">
        <x-form.yes-no name="had_surgery" label="Have you had a surgical operation?" :value="$record->had_surgery" model="hadSurgery" />
        <x-form.text name="surgery_details" label="Surgery details" :value="$record->surgery_details" required maxlength="255"
            x-show="hadSurgery === '1'" x-cloak />
    </div>

    <div class="space-y-3">
        <x-form.yes-no name="has_allergies" label="Do you have allergies?" :value="$record->has_allergies" model="hasAllergies" />
        <x-form.text name="allergy_details" label="Allergy details" :value="$record->allergy_details" required maxlength="255"
            x-show="hasAllergies === '1'" x-cloak />
    </div>
</div>
