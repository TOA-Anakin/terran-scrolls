<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timer extends Model
{
    use HasFactory;

    public function scopeByTask($query, $id)
    {
        if (!empty($id)) {
            $query->where('task_id', $id);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeMine($query)
    {
        return $query->where('user_id', auth()->id());
    }

    public function scopeRunning($query)
    {
        return $query->whereNull('stopped_at');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('task', function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%');
                })->orWhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%');
                })->orWhereHas('user', function ($q) use ($search) {
                    $q->where('last_name', 'like', '%' . $search . '%');
                });
            });
        })->when($filters['user'] ?? null, function ($query, $user) {
            $fUsers = explode(',', $user);
            $query->whereIn('user_id', $fUsers);
        });
    }
}
