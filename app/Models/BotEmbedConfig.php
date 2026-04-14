<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotEmbedConfig extends Model
{
    protected $fillable = [
        'chatbot_id', 'primary_color', 'position', 'size', 'greeting',
        'quick_replies', 'branding', 'sound_enabled', 'auto_open_delay', 'allow_file_upload',
    ];

    protected $casts = [
        'quick_replies' => 'array',
        'branding' => 'array',
        'sound_enabled' => 'boolean',
        'allow_file_upload' => 'boolean',
        'auto_open_delay' => 'integer',
    ];

    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }
}
