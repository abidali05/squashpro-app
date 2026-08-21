<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Club Working Hours
        Schema::create('club_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('users')->cascadeOnDelete();
            $table->string('day'); // 'monday', 'tuesday', etc.
            $table->boolean('is_open')->default(true);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->timestamps();

            $table->unique(['club_id', 'day']);
        });

        // 2. Club Non-Member Booking Windows
        Schema::create('club_non_member_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('users')->cascadeOnDelete();
            $table->string('day');
            $table->boolean('is_available')->default(true);
            $table->time('from_time')->nullable();
            $table->time('to_time')->nullable();
            $table->timestamps();
        });

        // 3. Tournament Invitations (Multi-club)
        Schema::create('tournament_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('invited_club_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // 'pending', 'accepted', 'rejected'
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        // 4. Tournament Teams
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('club_id')->constrained('users')->cascadeOnDelete();
            $table->string('submission_status')->default('not_submitted'); // 'submitted', 'not_submitted'
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'club_id']);
        });

        // 5. Tournament Team Players (Ordered)
        Schema::create('tournament_team_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('tournament_teams')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('users')->cascadeOnDelete();
            $table->integer('position');
            $table->timestamps();

            $table->unique(['team_id', 'player_id']);
            $table->unique(['team_id', 'position']);
        });

        // 6. Court Slots (Pricing & Availability schedule)
        Schema::create('court_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['court_id', 'day', 'start_time']);
        });

        // 7. Court Status Audits
        Schema::create('court_status_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->string('previous_status');
            $table->string('new_status');
            $table->text('reason');
            $table->timestamp('changed_at')->useCurrent();
        });

        // 8. Add Scorer/Umpire Flags to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('are_you_scorer')->default(false);
            $table->boolean('are_you_umpire')->default(false);
        });

        // 9. Add membership_type to club_memberships table
        Schema::table('club_memberships', function (Blueprint $table) {
            $table->string('membership_type')->default('permanent'); // 'temporary', 'permanent'
            $table->timestamp('membership_expiry_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('club_memberships', function (Blueprint $table) {
            $table->dropColumn(['membership_type', 'membership_expiry_date']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['are_you_scorer', 'are_you_umpire']);
        });

        Schema::dropIfExists('court_status_audits');
        Schema::dropIfExists('court_slots');
        Schema::dropIfExists('tournament_team_players');
        Schema::dropIfExists('tournament_teams');
        Schema::dropIfExists('tournament_invitations');
        Schema::dropIfExists('club_non_member_windows');
        Schema::dropIfExists('club_working_hours');
    }
};
