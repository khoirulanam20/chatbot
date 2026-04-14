<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRating extends Model
{
    protected $fillable = [
        'message_id', 'rating', 'feedback',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
