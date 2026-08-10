<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['tournament_id', 'name']);
        });

        Schema::create('tournament_group_clubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('tournament_groups')->cascadeOnDelete();
            $table->foreignId('club_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'club_id']);
        });

        Schema::create('tournament_fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('tournament_groups')->cascadeOnDelete();
            $table->string('round');
            $table->foreignId('home_club_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('away_club_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('scheduled');
            $table->foreignId('winner_club_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained('tournament_fixtures')->cascadeOnDelete();
            $table->integer('sequence');
            $table->foreignId('home_player_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('away_player_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('scheduled');
            $table->string('score')->nullable();
            $table->foreignId('winner_player_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['fixture_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
        Schema::dropIfExists('tournament_fixtures');
        Schema::dropIfExists('tournament_group_clubs');
        Schema::dropIfExists('tournament_groups');
    }
};
