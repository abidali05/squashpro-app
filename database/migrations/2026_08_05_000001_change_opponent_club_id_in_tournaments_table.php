<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dbDriver = Schema::getConnection()->getDriverName();

        if ($dbDriver !== 'sqlite') {
            try {
                Schema::table('tournaments', function (Blueprint $table) {
                    $table->dropForeign(['opponent_club_id']);
                });
            } catch (\Exception $e) {}
            
            try {
                Schema::table('tournaments', function (Blueprint $table) {
                    $table->dropIndex('tournaments_opponent_club_id_foreign');
                });
            } catch (\Exception $e) {}
        }

        Schema::table('tournaments', function (Blueprint $table) {
            $table->text('opponent_club_id')->nullable()->change();
        });
    }

    public function down(): void
    {
    }
};
