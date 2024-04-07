<?php

namespace App\Http\Controllers;

use App\Models\StarredProject;

class StarredProjectsController extends Controller
{
    /**
     * Toggles the starred status of a project for the authenticated user.
     * @param int $projectId The ID of the project to star or unstar.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a 'done' message.
     */
    public function toggleStarred($projectId)
    {
        $data = ['user_id' => auth()->id(), 'project_id' => $projectId];
        $existingProject = StarredProject::where($data)->first();
        if (!empty($existingProject)) {
            $existingProject->delete();
        } else {
            StarredProject::create($data);
        }
        return response()->json('done');
    }
}
