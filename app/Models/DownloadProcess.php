<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
