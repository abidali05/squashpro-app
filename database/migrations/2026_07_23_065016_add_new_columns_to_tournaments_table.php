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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('tournament_type')->default('CLUB_MEMBERS_ONLY')->after('club_id');
            $table->foreignId('opponent_club_id')->nullable()->after('tournament_type')->constrained('users')->nullOnDelete();
            $table->string('gender')->default('OPEN')->after('opponent_club_id');
            $table->json('player_level')->nullable()->after('gender');
            $table->string('age_group')->nullable()->after('player_level');
            $table->unsignedInteger('maximum_players')->nullable()->after('allowed_player');
            $table->dateTime('registration_deadline')->change();
            $table->string('status')->default('open')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opponent_club_id');
            $table->dropColumn(['tournament_type', 'gender', 'player_level', 'age_group', 'maximum_players']);
            $table->date('registration_deadline')->change();
            $table->enum('status', ['open', 'full', 'closed', 'completed', 'cancelled'])->default('open')->change();
        });
    }
};
