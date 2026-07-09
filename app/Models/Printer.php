<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'ip',
        'status',
        'type',
        'article_id',
        'profit_manager_id',
        'food_id',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class,'type');
    }

    // Relation with ProfitManager
    public function profitManager()
    {
        return $this->belongsTo(ProfitManager::class);
    }

    // Relation with Food
    public function food()
    {
        return $this->belongsTo(Food::class);
    }
}
