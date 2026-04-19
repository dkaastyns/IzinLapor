<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Complaint;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id'];

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}