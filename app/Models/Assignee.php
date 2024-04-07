<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignee extends Model
{
    use HasFactory;
    protected $table = 'assignees';

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projectWhere($id)
    {
        return $this->belongsTo(Task::class, 'task_id')->where('project_id', $id);
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
