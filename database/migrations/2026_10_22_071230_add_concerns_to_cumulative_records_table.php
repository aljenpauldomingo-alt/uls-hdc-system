<?php

use App\Support\Form5;
use App\Support\Form8;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Form 5 "Psychological, Relationship and Coping" step: one nullable boolean per Yes/No question
 * (null = not answered) and encrypted "specify" texts. Same keys as Form 8.
 *
 * Existing drafts keep the step incomplete, so students see it. Records that were already
 * submitted or reviewed are left as they were: the step is marked complete with no answers.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cumulative_records', function (Blueprint $table) {
            foreach (Form5::concernYesNoFields() as $field) {
                $table->boolean($field)->nullable();
            }

            foreach (Form8::SPECIFY_FIELDS as $field) {
                $table->text($field)->nullable();
            }
        });

        DB::table('cumulative_records')
            ->whereIn('status', ['submitted', 'reviewed'])
            ->orderBy('id')
            ->chunkById(200, function ($records): void {
                foreach ($records as $record) {
                    $steps = json_decode((string) $record->completed_steps, true) ?: [];

                    if (! in_array('concerns', $steps, true)) {
                        DB::table('cumulative_records')->where('id', $record->id)
                            ->update(['completed_steps' => json_encode([...$steps, 'concerns'])]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cumulative_records', function (Blueprint $table) {
            $table->dropColumn([...Form5::concernYesNoFields(), ...Form8::SPECIFY_FIELDS]);
        });
    }
};
