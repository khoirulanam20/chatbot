<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'role', 'content', 'metadata', 'tokens', 'sources',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sources' => 'array',
        'tokens' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(MessageRating::class);
    }
}
