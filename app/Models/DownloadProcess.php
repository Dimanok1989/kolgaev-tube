<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DownloadProcess extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'chat_id',
        'url',
        'callback_url',
        'token',
        'title',
        'thumbnail_url',
        'video_path',
        'audio_path',
        'downloaded_at',
        'uploaded_at',
        'merged_at',
        'done_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'downloaded_at' => "datetime",
            'uploaded_at' => "datetime",
            'merged_at' => "datetime",
            'done_at' => "datetime",
        ];
    }

    /**
     * Проверка наличия видеофайла
     * 
     * @return bool
     */
    public function videoExists()
    {
        if (!$this->video_path) {
            return false;
        }

        return Storage::disk('public')->exists($this->video_path);
    }

    /**
     * Проверка наличия аудио
     * 
     * @return bool
     */
    public function audioExists()
    {
        if (!$this->audio_path) {
            return false;
        }

        return Storage::disk('public')->exists($this->audio_path);
    }
}
