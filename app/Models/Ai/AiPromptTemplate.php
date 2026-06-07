<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiPromptTemplate extends Model
{
    protected $table = 'ai_prompt_templates';

    protected $fillable = [
        'name',
        'description',
        'system_prompt',
        'user_prompt',
        'parameters',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function attempts(): HasMany
    {
        return $this->hasMany(\App\Models\Task\Attempt::class, 'prompt_template_id');
    }
}
