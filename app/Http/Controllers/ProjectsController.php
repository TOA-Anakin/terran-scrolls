<?php

namespace App\Http\Controllers;

use App\Models\BoardList;
use App\Models\Project;
use App\Models\RecentProject;
use App\Models\Task;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectsController extends Controller
{
    /**
     * Display the "No Project" page.
     *
     * @return \Inertia\Response
     */
    public function noProject() {
        return Inertia::render('Projects/Na', [
            'title' => 'No Workspace',
            'notice' => 'You did not assigned any workspace yet. Please contact with admin'
        ]);
    }

    /**
     * Display the project view page.
     *
     * @param string $uid
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
    public function view($uid, Request $request) {
        $auth_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $auth_id)->orWhereHas('member')->pluck('id');
        $requests = $request->all();
        $project = Project::bySlugOrId($uid)
            ->whereIn('workspace_id', $workspaceIds)
            ->with('workspace.member')
            ->with('star')
            ->with('background')
            ->first();

        RecentProject::updateOrCreate(
            [
                'user_id' => $auth_id,
                'project_id' => $project->id
            ],
            [
                'opened' => Carbon::now()
            ]
        );

        $board_lists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();

        $list_index = [];
        $loopIndex = 0;
        foreach ($board_lists as &$listItem) {
            $list_index[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex += 1;
        }

        if ($project->is_private && (auth()->user()['role_id'] != 1)) {
            $requests['private_task'] = $auth_id;
        }

        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->with('timer')
            ->whereHas('list')
            ->with('cover')
            ->withCount('checklistDone')
            ->withCount('comments')
            ->withCount('checklists')
            ->withCount('attachments')->with('assignees')
            ->orderByOrder()->get()->toArray();

        foreach ($tasks as $task) {
            if (isset($list_index[$task['list_id']])) {
                $board_lists[$list_index[$task['list_id']]]['tasks'][] = $task;
            }
        }

        return Inertia::render('Projects/View', [
            'title' => 'Board | ' . $project->title,
            'board_lists' => $board_lists,
            'lists' => $board_lists,
            'list_index' => $list_index,
            'filters' => $requests,
            'project' => $project,
            'tasks' => $tasks,
        ]);
    }
}
