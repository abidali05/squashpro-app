<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_fixtures', function (Blueprint $table) {
            $table->foreignId('home_club_id')->nullable()->change();
            $table->string('home_placeholder')->nullable()->after('home_club_id');
            $table->string('away_placeholder')->nullable()->after('away_club_id');
            $table->boolean('is_rest')->default(false)->after('is_bye');
            $table->foreignId('rest_club_id')->nullable()->after('is_rest')->constrained('users')->cascadeOnDelete();
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->foreignId('home_player_id')->nullable()->change();
            $table->foreignId('away_player_id')->nullable()->change();
            $table->string('home_player_placeholder')->nullable()->after('home_player_id');
            $table->string('away_player_placeholder')->nullable()->after('away_player_id');
            $table->foreignId('venue_id')->nullable()->after('away_player_placeholder')->constrained('courts')->nullOnDelete();
            $table->date('start_date')->nullable()->after('venue_id');
            $table->string('start_time')->nullable()->after('start_date');
        });

        Schema::create('tournament_match_scorers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('tournament_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['match_id', 'user_id']);
        });

        Schema::create('tournament_match_umpires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('tournament_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['match_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_match_umpires');
        Schema::dropIfExists('tournament_match_scorers');

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
            $table->dropColumn([
                'home_player_placeholder',
                'away_player_placeholder',
                'venue_id',
                'start_date',
                'start_time',
            ]);
        });

        Schema::table('tournament_fixtures', function (Blueprint $table) {
            $table->dropForeign(['rest_club_id']);
            $table->dropColumn([
                'home_placeholder',
                'away_placeholder',
                'is_rest',
                'rest_club_id',
            ]);
        });
    }
};
