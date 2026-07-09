<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Food;

class Article extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(Article::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Article::class, 'parent_id');
    }

    public function food() {
        return $this->hasOne(Food::class);
    }
}
