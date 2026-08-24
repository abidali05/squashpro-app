<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_tournament_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->string('format')->nullable();
            $table->boolean('has_pools')->default(false);
            $table->json('pools')->nullable();
            $table->timestamps();

            $table->unique('tournament_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_tournament_pools');
    }
};
