<?php

namespace App\Models;

use App\Models\Task\Category;
use Illuminate\Database\Eloquent\Model;

class UserCategoryMastery extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'probability',
    ];

    protected $casts = [
        'probability' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
