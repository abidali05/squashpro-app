<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('app_notifications', 'notifiable_type')) {
                $table->string('notifiable_type', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('app_notifications', 'notifiable_id')) {
                $table->unsignedBigInteger('notifiable_id')->nullable()->after('notifiable_type');
            }

            if (! Schema::hasColumn('app_notifications', 'data')) {
                $table->json('data')->nullable()->after('type');
            }

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->dropColumn(['notifiable_type', 'notifiable_id', 'data']);
        });
    }
};
