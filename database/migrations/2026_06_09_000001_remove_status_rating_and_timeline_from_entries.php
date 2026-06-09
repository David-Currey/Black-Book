<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('entry_notes')) {
            Schema::drop('entry_notes');
        }

        $hasStatus = Schema::hasColumn('people', 'status');
        $hasRating = Schema::hasColumn('people', 'rating');

        if ($hasStatus || $hasRating) {
            Schema::table('people', function (Blueprint $table) use ($hasStatus, $hasRating) {
                if ($hasStatus) {
                    $table->dropColumn('status');
                }

                if ($hasRating) {
                    $table->dropColumn('rating');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasStatus = Schema::hasColumn('people', 'status');
        $hasRating = Schema::hasColumn('people', 'rating');

        if (! $hasStatus || ! $hasRating) {
            Schema::table('people', function (Blueprint $table) use ($hasStatus, $hasRating) {
                if (! $hasStatus) {
                    $table->string('status')->default('neutral')->after('game');
                }

                if (! $hasRating) {
                    $table->unsignedTinyInteger('rating')->nullable()->after('status');
                }
            });
        }

        if (! Schema::hasTable('entry_notes')) {
            Schema::create('entry_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->date('occurred_on')->nullable();
                $table->text('note');
                $table->timestamps();
            });
        }
    }
};
