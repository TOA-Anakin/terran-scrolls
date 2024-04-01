<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckList extends Model
{
    use HasFactory;
    protected $table = 'check_lists';

    public function getCreatedAtAttribute($date) {
        return Carbon::parse($date)->format('jS F, Y');
    }

    public function scopeByTask($query, $id) {
        if(!empty($id)){
            $query->where('task_id', $id);
        }
    }

    public function task(){
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function scopeFilter($query, array $filters){
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            });
        });
    }
}
