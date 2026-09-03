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
        // 1. Add scoring columns to tournament_matches table
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->unsignedTinyInteger('best_of')->default(3)->after('score');
            $table->unsignedTinyInteger('current_game')->default(1)->after('best_of');

            $table->foreignId('toss_winner_player_id')
                ->nullable()
                ->after('current_game')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('initial_server_player_id')
                ->nullable()
                ->after('toss_winner_player_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('initial_serving_side', 1)
                ->nullable()
                ->after('initial_server_player_id');

            $table->foreignId('current_server_id')
                ->nullable()
                ->after('initial_serving_side')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('current_serving_side', 1)
                ->nullable()
                ->after('current_server_id');

            $table->boolean('can_change_serving_side')
                ->default(true)
                ->after('current_serving_side');

            $table->timestamp('match_start_time')
                ->nullable()
                ->after('can_change_serving_side');

            $table->timestamp('match_end_time')
                ->nullable()
                ->after('match_start_time');

            $table->timestamp('current_game_start_time')
                ->nullable()
                ->after('match_end_time');
        });

        // 2. Create tournament_match_games table
        Schema::create('tournament_match_games', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_id')
                ->constrained('tournament_matches')
                ->cascadeOnDelete();

            $table->unsignedInteger('game_number');

            $table->unsignedInteger('home_score')->default(0);
            $table->unsignedInteger('away_score')->default(0);

            $table->foreignId('winner_player_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('starting_server_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('starting_serving_side', 1)->nullable();

            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            $table->string('status')->default('in_progress');

            $table->timestamps();

            $table->unique(['match_id', 'game_number']);
        });

        // 3. Create tournament_match_rallies table
        Schema::create('tournament_match_rallies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_id')
                ->constrained('tournament_matches')
                ->cascadeOnDelete();

            $table->foreignId('game_id')
                ->constrained('tournament_match_games')
                ->cascadeOnDelete();

            $table->unsignedInteger('sequence');

            $table->foreignId('server_player_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('serving_side', 1)->nullable();

            $table->string('call_type'); // ace, clean_winner, unforced_error, stroke, no_let, let

            $table->foreignId('striker_player_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('awarded_to_player_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedInteger('home_score_after');
            $table->unsignedInteger('away_score_after');

            $table->foreignId('next_server_player_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('next_serving_side', 1)->nullable();
            $table->boolean('can_change_serving_side')->default(true);

            $table->timestamp('event_time')->nullable();

            $table->boolean('is_undone')->default(false);
            $table->timestamp('undone_at')->nullable();

            $table->foreignId('undone_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['game_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_match_rallies');
        Schema::dropIfExists('tournament_match_games');

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropForeign(['toss_winner_player_id']);
            $table->dropForeign(['initial_server_player_id']);
            $table->dropForeign(['current_server_id']);

            $table->dropColumn([
                'best_of',
                'current_game',
                'toss_winner_player_id',
                'initial_server_player_id',
                'initial_serving_side',
                'current_server_id',
                'current_serving_side',
                'can_change_serving_side',
                'match_start_time',
                'match_end_time',
                'current_game_start_time',
            ]);
        });
    }
};
