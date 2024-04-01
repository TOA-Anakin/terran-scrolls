<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecentProject extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'recent_projects';

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeLast($query)
    {
        $query->orderBy('opened', 'DESC');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
