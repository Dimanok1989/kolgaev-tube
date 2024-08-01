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
        Schema::create('download_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->comment('Идентификатор пользователя');
            $table->string('url', 1000)->nullable()->comment('Ссылка на видео');
            $table->string('callback_url', 1000)->nullable()->comment('Ссылка обработки уведомлений');
            $table->string('token', 100)->comment('Уникальный токен процесса')->index();
            $table->string('title', 500)->nullable()->comment('Имя видео');
            $table->string('thumbnail_url', 1000)->nullable()->comment('Ссылка на превьюшку');
            $table->string('video_path', 1000)->nullable()->comment('Путь до файла с видео');
            $table->string('audio_path', 1000)->nullable()->comment('Путь до файла с аудиодорожеой');
            $table->timestamps();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->timestamp('done_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_processes');
    }
};
