<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_tournament_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->string('tournament_format')->nullable();
            $table->json('competition_setup')->nullable();
            $table->json('pool_rules')->nullable();
            $table->json('knockout_rounds')->nullable();
            $table->json('match_equipment')->nullable();
            $table->json('scoring_rules')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique('tournament_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_tournament_rules');
    }
};
