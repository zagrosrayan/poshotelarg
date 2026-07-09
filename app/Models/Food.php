<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function profitManager()
    {
        return $this->belongsTo(ProfitManager::class, 'profit_manager_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'food_id');
    }
}
