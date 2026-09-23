<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('tournament_matches', function (Blueprint $table) {
                $table->dropForeign(['venue_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may already be dropped
        }

        $validUserIds = DB::table('users')->pluck('id')->toArray();
        if (!empty($validUserIds)) {
            DB::table('tournament_matches')
                ->whereNotNull('venue_id')
                ->whereNotIn('venue_id', $validUserIds)
                ->update(['venue_id' => null]);
        } else {
            DB::table('tournament_matches')->update(['venue_id' => null]);
        }

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->foreign('venue_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        try {
            Schema::table('tournament_matches', function (Blueprint $table) {
                $table->dropForeign(['venue_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key may already be dropped
        }

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->foreign('venue_id')->references('id')->on('courts')->nullOnDelete();
        });
    }
};
