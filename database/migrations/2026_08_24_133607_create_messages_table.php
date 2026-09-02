<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 実際のチャットのやり取りを管理するテーブル
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_chat_conversation_id')
                ->constrained('ai_chat_conversations')
                ->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->string('status')->default('pending'); // pending / sent / failed
            $table->string('role')->default('user'); // user / assistant
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
