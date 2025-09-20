<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

    const ACTIVE = 1, INACTIVE = 2;

    protected $fillable = [
        'user_id', 'title', 'description', 'image', 'status'
    ];

    protected $hidden = ['deleted_at'];

    // polymorphic relation: a blog can have many likes
    public function likes()
    {
        return $this->morphMany(BlogLike::class,'likeable');
    }
}
