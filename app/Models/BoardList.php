<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardList extends Model
{
    use HasFactory;

    protected $table = 'board_lists';

    public $timestamps = false;

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->firstOrFail();
    }

    public function scopeByProject($query, $id) {
        if(!empty($id)){
            $query->where('project_id', $id);
        }
    }

    public function scopeOrderByOrder($query)
    {
        $query->orderBy('order');
    }

    public function scopeIsOpen($query) {
        $query->where('is_archive', 0);
    }

    public function tasks(){
        return $this->hasMany(Task::class, 'list_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, array $filters){
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            });
        });
    }
}
