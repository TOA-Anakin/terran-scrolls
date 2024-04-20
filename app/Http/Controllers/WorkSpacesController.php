<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Assignee;
use App\Models\Attachment;
use App\Models\BoardList;
use App\Models\CheckList;
use App\Models\Comment;
use App\Models\Project;
use App\Models\RecentProject;
use App\Models\StarredProject;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TeamMember;
use App\Models\Timer;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class WorkSpacesController extends Controller
{
    /**
     * Display the workspace index page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $userId = auth()->id();

        $workspaceIds = Workspace::where('user_id', $userId)
            ->orWhereHas('member')
            ->pluck('id');

        $project = RecentProject::where('user_id', $userId)
            ->with('project')
            ->has('project.workspace')
            ->whereHas('project', function ($q) use ($workspaceIds) {
                $q->whereIn('workspace_id', $workspaceIds);
            })
            ->orderBy('opened', 'desc')
            ->first();

        if (!empty($project)) {
            return Redirect::route('projects.view.board', $project->project->slug ?: $project->project->id);
        }

        $project = Project::whereIn('workspace_id', $workspaceIds)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!empty($project)) {
            return Redirect::route('projects.view.board', $project->slug ?: $project->id);
        }

        $assignee = Assignee::where('user_id', $userId)
            ->whereHas('task')
            ->with('task')
            ->first();

        if (!empty($assignee)) {
            return Redirect::route('projects.view.board', ['uid' => $assignee->task->project_id, 'task' => $assignee->task->id]);
        }

        return Redirect::route('projects.view.na');
    }

    /**
     * Get all workspaces for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonAll()
    {
        $userId = auth()->id();
        $workSpaces = Workspace::where('user_id', $userId)
            ->orWhereHas('member')
            ->with('member')
            ->get()
            ->toArray();

        return response()->json($workSpaces);
    }

    /**
     * Get all workspaces owned by the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonMineAll()
    {
        $myWorkspaces = Workspace::where('user_id', auth()->id())
            ->limit(50)
            ->get()
            ->toArray();

        return response()->json($myWorkspaces);
    }

    /**
     * Create a new workspace.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonCreate(Request $request)
    {
        $requests = $request->all();
        $requests['user_id'] = auth()->id();
        $workspace = Workspace::create($requests);
        $slug = StringHelper::sanitizeForSlug($workspace->name);
        $existingItem = Workspace::where('slug', $slug)->first();

        if (!empty($existingItem)) {
            $slug = $slug . '-' . $workspace->id;
        }

        $workspace->slug = $slug;
        $workspace->save();

        TeamMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $requests['user_id'],
            'role' => 'admin',
            'added_by' => $requests['user_id']
        ]);

        return response()->json($workspace);
    }

    /**
     * Change the workspace of a project.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonChangeWorkspace(Request $request)
    {
        $requestData = $request->all();
        $project = Project::where('id', $requestData['project_id'])->first();
        $project->workspace_id = $requestData['workspace_id'];
        $project->save();

        return response()->json($project);
    }

    /**
     * Update an existing workspace.
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonUpdateWorkspace($id, Request $request)
    {
        $requestData = $request->all();
        $workspace = Workspace::where('id', $id)->first();

        foreach ($requestData as $itemKey => $itemValue) {
            $workspace->{$itemKey} = $itemValue;
        }

        $workspace->save();

        return response()->json($workspace);
    }

    /**
     * Add a member to a workspace.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonAddMember(Request $request)
    {
        $requestData = $request->all();
        $teamMember = TeamMember::where(['workspace_id' => $requestData['workspace_id'], 'user_id' => $requestData['user_id']])->first();

        if (!empty($teamMember)) {
            $teamMember->delete();
            $teamMember = ['success' => true];
        } else {
            $requestData['added_by'] = auth()->id();
            $teamMember = TeamMember::create($requestData);
            $teamMember->load('user');
        }

        return response()->json($teamMember);
    }

    /**
     * Display the workspace view page.
     *
     * @param string $uid
     * @return \Inertia\Response
     */
    public function workspaceView($uid)
    {
        $workspace = Workspace::whereId($uid)
            ->orWhere('slug', $uid)
            ->whereHas('member')
            ->with('member')
            ->first();

        $projects = Project::where('workspace_id', $workspace->id)
            ->with('star')
            ->with('background')
            ->get();

        return Inertia::render('Workspaces/View', [
            'title' => 'Projects | ' . $workspace->name,
            'workspace' => $workspace,
            'projects' => $projects
        ]);
    }

    /**
     * Display the workspace members page.
     *
     * @param string $uid
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function workspaceMembers($uid, Request $request)
    {
        $workspace = Workspace::whereId($uid)
            ->orWhere('slug', $uid)
            ->whereHas('member')
            ->with('member')
            ->first();

        if ($workspace->member->role != 'admin') {
            return Redirect::route('workspace.view', $workspace->id);
        }

        $projects = Project::where('workspace_id', $workspace->id)
            ->with('star')
            ->with('background')
            ->get();

        return Inertia::render('Workspaces/Members', [
            'title' => 'Members | ' . $workspace->name,
            'workspace' => $workspace,
            'projects' => $projects,
            'team_members' => TeamMember::where('workspace_id', $workspace->id)
                ->filter($request->only('search'))
                ->orderBy('created_at', 'DESC')
                ->paginate(10)
                ->withQueryString()
                ->through(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->user->first_name . ' ' . $member->user->last_name,
                        'photo' => $member->user->photo_path,
                        'role' => $member->role,
                        'workspace_id' => $member->workspace_id,
                        'user_id' => $member->user_id,
                        'created_at' => $member->created_at,
                    ];
                }),
        ]);
    }

    /**
     * Display the workspace tables page.
     *
     * @param string $uid
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse | \Inertia\Response|\Illuminate\Http\Response
     */
    public function workspaceTables($uid, Request $request)
    {
        $user = auth()->user()->load('role');

        $requests = $request->all();

        if (!empty($user->role)) {
            if ($user->role->slug != 'admin' && empty($requests['user'])) {
                return Redirect::route('workspace.tables', ['uid' => $uid, 'user' => $user->id]);
            }
        } else {
            return abort(404);
        }

        $listIndex = [];
        $boardLists = BoardList::orderByOrder()->get();
        $workspace = Workspace::where('id', $uid)
            ->orWhere('slug', $uid)
            ->whereHas('member')
            ->with('member')
            ->first();

        $loopIndex = 0;
        foreach ($boardLists as &$listItem) {
            $listIndex[$listItem->id] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex += 1;
        }

        return Inertia::render('Workspaces/Table', [
            'title' => 'Tasks | ' . $workspace->name,
            'board_lists' => $boardLists,
            'filters' => $requests,
            'list_index' => $listIndex,
            'workspace' => $workspace,
            'tasks' => Task::filter($requests)->whereHas('project', function ($q) use ($workspace) {
                $q->where('workspace_id', $workspace->id);
            })->with('list')
                ->with('taskLabels.label')
                ->with('project.background')
                ->with('assignees')
                ->with('timer')
                ->isOpen()
                ->orderByOrder()
                ->get()
        ]);
    }

    /**
     * Get other users in a workspace.
     *
     * @param int $workspaceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOtherUsers($workspaceId)
    {
        $workspaceUsers = TeamMember::where('workspace_id', $workspaceId)->groupBy('user_id')->pluck('user_id');
        $users = User::select('id', 'first_name', 'last_name', 'photo_path')->where('id', '!=', auth()->id())->get();

        return response()->json(['users' => $users, 'workspace_users' => $workspaceUsers]);
    }

    /**
     * Delete a workspace and its associated data.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $workspace = Workspace::where('id', $id)->first();
        $workspace->delete();
        TeamMember::where('workspace_id', $id)->delete();

        $projects = Project::where('workspace_id', $id)->get();

        foreach ($projects as $project) {
            BoardList::where('project_id', $project->id)->delete();
            RecentProject::where('project_id', $project->id)->delete();
            StarredProject::where('project_id', $project->id)->delete();

            $tasks = Task::where('project_id', $project->id)->get();

            foreach ($tasks as $task) {
                $attachments = Attachment::where('task_id', $task->id)->get();

                foreach ($attachments as $attachment) {
                    if (!empty($attachment->path) && File::exists(public_path($attachment->path))) {
                        File::delete(public_path($attachment->path));
                    }

                    $attachment->delete();
                }

                CheckList::where('task_id', $task->id)->delete();
                Timer::where('task_id', $task->id)->delete();
                Comment::where('task_id', $task->id)->delete();
                Assignee::where('task_id', $task->id)->delete();
                TaskLabel::where('task_id', $task->id)->delete();
                $task->delete();
            }

            $project->delete();
        }

        return Redirect::route('dashboard');
    }
}
