<?php

declare(strict_types=1);

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
        Schema::create('google_calendar_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('access_token');
            $table->string('refresh_token');
            $table->timestamp('expires_at');
            $table->timestamp('refresh_token_expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_calendar_tokens');
    }
};
