<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_fixtures', function (Blueprint $table) {
            $table->foreignId('court_id')->nullable()->after('rest_club_id')->constrained('courts')->nullOnDelete();
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->foreignId('court_id')->nullable()->after('venue_id')->constrained('courts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tournament_fixtures', function (Blueprint $table) {
            $table->dropForeign(['court_id']);
            $table->dropColumn('court_id');
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropForeign(['court_id']);
            $table->dropColumn('court_id');
        });
    }
};
