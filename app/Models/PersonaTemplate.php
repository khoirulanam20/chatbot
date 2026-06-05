<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'role',
        'tone',
        'instructions',
        'restrictions',
        'greeting_style',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{role: string, tone: string, instructions: string, restrictions: string, greeting_style: string}
     */
    public function toPersonaArray(): array
    {
        return [
            'role' => $this->role ?? '',
            'tone' => $this->tone ?? 'ramah',
            'instructions' => $this->instructions ?? '',
            'restrictions' => $this->restrictions ?? '',
            'greeting_style' => $this->greeting_style ?? '',
        ];
    }
}
