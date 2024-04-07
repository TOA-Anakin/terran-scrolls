<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repeat extends Model
{
    use HasFactory;

    protected $table = 'repeats';

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
