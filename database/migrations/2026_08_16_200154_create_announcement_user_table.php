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
        Schema::create('announcement_user', function (Blueprint $table) {

            $table->foreignId('announcement_id')
                ->constrained('announcements')
                ->cascadeOnDelete();

            $table->foreignUlid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->primary(['announcement_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_user');
    }
};
