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
        Schema::create('qa_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qa_thread_id')
                ->constrained()
                ->cascadeOnDelete();
            // 質問が削除されると回答も削除される
            $table->text('body');
            $table->timestamps();
            $table->foreignUlid('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            // ユーザーが退出しても回答は削除されない
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_replies');
    }
};
