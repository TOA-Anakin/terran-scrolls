<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model {
    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task(){
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function getCreatedAtAttribute($date){
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }
}
