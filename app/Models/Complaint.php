<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Category;

class Complaint extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'location',
        'location_detail',
        'image_path',
        'image_paths',
        'status',
        'admin_response',
        'estimated_completion_date',
        'rating',
        'rating_comment',
        'notified_at',
        'admin_notified_at',
        'progress',
    ];

    protected $casts = [
        'image_paths'               => 'array',
        'estimated_completion_date' => 'date:Y-m-d',
        'notified_at'               => 'datetime',
        'admin_notified_at'         => 'datetime',
        'progress'                  => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}