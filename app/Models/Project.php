<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    public function getCreatedAtAttribute($date) {
        return Carbon::parse($date)->format('jS F, Y');
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }

    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

    public function projectWhereIdOrSlug($id, $slug){
        return $this->belongsTo(Task::class, 'task_id')->where('project_id', $id);
    }

    public function scopeBySlugOrId($query, $id) {
        if(!empty($id)){
            $query->where('id', $id)->orWhere('slug', $id);
        }
    }

    public function star() {
        return $this->hasOne(StarredProject::class, 'project_id')->where('user_id', auth()->id());
    }

    public function background() {
        return $this->belongsTo(Background::class, 'background_id', 'id');
    }

//    public function scopeStarred(){
//        return $this->belongsTo(StarredProject::class, 'task_id')->where('user_id', auth()->id());
//    }

//    public function scopeStarred($query) {
//        $query->where('is_star', 1);
//    }

    public function scopeFilter($query, array $filters){
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('details', 'like', '%'.$search.'%');
            });
        });
    }
}
