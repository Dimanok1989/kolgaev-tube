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
        Schema::table('download_processes', function (Blueprint $table) {
            $table->uuid('process_id')->nullable()->comment('Уникальный идентификатор процесса загрузки')->index()->after('id');
            $table->string('video_id')->nullable()->comment('Уникальный идентификатор видео')->index()->after('chat_id');
            $table->jsonb('meta')->nullable()->after('done_at');
            $table->dropColumn('thumbnail_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('download_processes', function (Blueprint $table) {
            $table->dropColumn([
                // 'process_id',
                'video_id',
                'meta',
            ]);
            $table->string('thumbnail_url', 1000)->nullable()->comment('Ссылка на превьюшку')->after('title');
        });
    }
};
