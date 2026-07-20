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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('non_member_booking_allowed')->default(false)->after('club_logo');
            $table->time('non_member_booking_start_time')->nullable()->after('non_member_booking_allowed');
            $table->time('non_member_booking_end_time')->nullable()->after('non_member_booking_start_time');
            $table->string('timezone')->nullable()->after('non_member_booking_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'non_member_booking_allowed',
                'non_member_booking_start_time',
                'non_member_booking_end_time',
                'timezone',
            ]);
        });
    }
};
