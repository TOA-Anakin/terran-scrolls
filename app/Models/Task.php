<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->firstOrFail();
    }

    public function scopeByUser($query, $id)
    {
        if (!empty($id)) {
            $query->where('user_id', $id);
        }
    }

    public function scopeOrderByOrder($query)
    {
        $query->orderBy('order');
    }

    public function scopeIsOpen($query)
    {
        $query->where('is_archive', 0);
    }

    public function scopeByProject($query, $id)
    {
        if (!empty($id)) {
            $query->where('project_id', $id);
        }
    }

    public function list()
    {
        return $this->belongsTo(BoardList::class, 'list_id')->where('is_archive', 0);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function cover()
    {
        return $this->belongsTo(Attachment::class, 'cover');
    }

    public function checklists()
    {
        return $this->hasMany(CheckList::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function timers()
    {
        return $this->hasMany(Timer::class)->where('user_id', auth()->id());
    }

    public function timer()
    {
        return $this->hasOne(Timer::class, 'task_id')->where('user_id', auth()->id())->whereNull('stopped_at');
    }

    public function assignees()
    {
        return $this->hasMany(Assignee::class)->with('user');
    }

    public function lastAssignee()
    {
        return $this->hasMany(Assignee::class)->latest('id')->limit(1);
    }

    public function taskLabels()
    {
        return $this->hasMany(TaskLabel::class, 'task_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function checklistDone()
    {
        return $this->hasMany(CheckList::class)->where('check_lists.is_done', '=', 1);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            });
        })->when($filters['user'] ?? null, function ($query, $user) {
            $fUsers = explode(',', $user);
            $includeTasks = Assignee::whereIn('user_id', $fUsers)->groupBy('task_id')->pluck('task_id');
            if (in_array('null', $fUsers)) {
                $excludeTask = Assignee::groupBy('task_id')->pluck('task_id');
                $query->whereNotIn('id', $excludeTask)->orWhereIn('id', $includeTasks);
            } else {
                $query->whereIn('id', $includeTasks);
            }
        })->when($filters['private_task'] ?? null, function ($query, $privateTask) {
            $includeTasks = Assignee::where('user_id', $privateTask)->groupBy('task_id')->pluck('task_id');
            $query->whereIn('id', $includeTasks);
        })->when($filters['label'] ?? null, function ($query, $label) {
            $fLabels = explode(',', $label);
            $includeTasks = TaskLabel::whereIn('label_id', $fLabels)->groupBy('task_id')->pluck('task_id');
            $query->whereIn('id', $includeTasks);
        })->when($filters['range'] ?? null, function ($query, $filters) {
            $start = $filters['range']['start'] ?? date("Y-m-d H:i:s", strtotime('monday this week'));
            $end = $filters['range']['end'] ?? date("Y-m-d H:i:s", strtotime('sunday this week 23:59'));
            //            dd($start, $end);
            //            $query->whereDate('date', '>=', $start)->whereDate('date', '<=', $end);
            $query->whereBetween('created_at', [$start, $end])->orWhereBetween('due_date', [$start, $end]);
            //            $query->where([['created_at', '>=', $start], ['created_at', '>=', $end]])->orWhere([['due_date', '>=', $start], ['due_date', '>=', $end]]);
            //            $query->orderBy('created_at', 'asc');
        })->when($filters['due'] ?? null, function ($query, $due) {
            $dueDates = explode(',', $due);
            if (in_array('over', $dueDates)) {
                $query->where([
                    ['is_done', '0', 0],
                    ['due_date', '<', Carbon::now()]
                ]);
            }
            if (in_array('next_day', $dueDates)) {
                if (in_array('over', $dueDates)) {
                    $query->orWhereBetween('due_date', [Carbon::now(), Carbon::now()->addDay()]);
                } else {
                    $query->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDay()]);
                }
            }
            if (in_array('null', $dueDates)) {
                if (in_array('over', $dueDates) || in_array('next_day', $dueDates)) {
                    $query->orWhereNull('due_date');
                } else {
                    $query->whereNull('due_date');
                }
            }
        });
    }
}
