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
        Schema::create('qa_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('certification_id')
                ->constrained()
                ->cascadeOnDelete();
            // 資格情報が削除されるとその資格の質問は削除される
            $table->string('title');
            $table->text('body');
            $table->string('status')->default('open');
            $table->foreignUlid('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            // ユーザーが退出しても質問は削除されない
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_threads');
    }
};
