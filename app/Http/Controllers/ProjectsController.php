<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Assignee;
use App\Models\Attachment;
use App\Models\BoardList;
use App\Models\CheckList;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\RecentProject;
use App\Models\StarredProject;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TeamMember;
use App\Models\Timer;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProjectsController extends Controller
{
    /**
     * Display the "Projects" page.
     * @return \Inertia\Response Returns the Inertia response containing the "Projects" page.
     */
    public function index()
    {
        return Inertia::render('Projects/Index', [
            'title' => 'Projects',
        ]);
    }

    /**
     * Display the "Test" page.
     * @return \Inertia\Response Returns the Inertia response containing the "Test" page.
     */
    public function test()
    {
        return Inertia::render('Projects/Test', [
            'title' => 'Projects',
        ]);
    }

    /**
     * Display the "No Project" page.
     *
     * @return \Inertia\Response Returns the Inertia response containing the "No Project" page.
     */
    public function noProject()
    {
        return Inertia::render('Projects/Na', [
            'title' => 'No Workspace',
            'notice' => 'You did not assigned any workspace yet. Please contact with admin'
        ]);
    }

    /**
     * Display the project view page.
     *
     * @param string $uid The UID of the project to view.
     * @param \Illuminate\Http\Request $request The current request instance.
     * @return \Inertia\Response Returns the Inertia response containing the project view page.
     */
    public function view($uid, Request $request)
    {
        $authId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $authId)->orWhereHas('member')->pluck('id');
        $requests = $request->all();
        $project = Project::bySlugOrId($uid)
            ->whereIn('workspace_id', $workspaceIds)
            ->with('workspace.member')
            ->with('star')
            ->with('background')
            ->first();

        RecentProject::updateOrCreate(
            [
                'user_id' => $authId,
                'project_id' => $project->id
            ],
            [
                'opened' => Carbon::now()
            ]
        );

        $boardLists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();

        $listIndex = [];
        $loopIndex = 0;
        foreach ($boardLists as &$listItem) {
            $listIndex[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex += 1;
        }

        if ($project->is_private && (auth()->user()['role_id'] != 1)) {
            $requests['private_task'] = $authId;
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
            if (isset($listIndex[$task['list_id']])) {
                $boardLists[$listIndex[$task['list_id']]]['tasks'][] = $task;
            }
        }

        return Inertia::render('Projects/View', [
            'title' => 'Board | ' . $project->title,
            'board_lists' => $boardLists,
            'lists' => $boardLists,
            'list_index' => $listIndex,
            'filters' => $requests,
            'project' => $project,
            'tasks' => $tasks,
        ]);
    }

    /**
     * Display the project view page with a specific task.
     *
     * @param string $projectUid The UID of the project to view.
     * @param string $taskUid The UID of the task to view.
     * @param \Illuminate\Http\Request $request The current request instance.
     * @return \Inertia\Response Returns the Inertia response containing the project view page with the specified task.
     */
    public function viewWithTask($projectUid, $taskUid, Request $request)
    {
        $requests = $request->all();
        $authId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $authId)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($projectUid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $listIndex = [];
        $boardLists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($boardLists as &$listItem) {
            $listIndex[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex += 1;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->whereHas('list')
            ->withCount('checklistDone')
            ->withCount('comments')
            ->withCount('checklists')
            ->withCount('attachments')
            ->with('assignees')
            ->orderByOrder()
            ->get()->toArray();
        foreach ($tasks as $task) {
            $boardLists[$listIndex[$task['list_id']]]['tasks'][] = $task;
        }
        return Inertia::render('Projects/View', [
            'title' => 'Projects',
            'filters' => $requests,
            'board_lists' => $boardLists,
            'lists' => $boardLists,
            'list_index' => $listIndex,
            'project' => $project,
            'task' => Task::where('id', $taskUid)->orWhere('slug', $taskUid)->first(),
            'tasks' => $tasks,
        ]);
    }

    /**
     * Display the project table view page.
     *
     * @param string $uid The UID of the project to view.
     * @param \Illuminate\Http\Request $request The current request instance.
     * @return \Inertia\Response Returns the Inertia response containing the project table view page.
     */
    public function viewTable($uid, Request $request)
    {
        $requests = $request->all();
        $authId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $authId)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $listIndex = [];
        $boardLists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($boardLists as &$listItem) {
            $listIndex[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex += 1;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->with('timer')
            ->whereHas('list')
            ->with('assignees')
            ->with('list')
            ->orderByOrder()
            ->get()->toArray();
        foreach ($tasks as $task) {
            $boardLists[$listIndex[$task['list_id']]]['tasks'][] = $task;
        }
        return Inertia::render('Projects/Table', [
            'title' => 'Table | ' . $project->title,
            'board_lists' => $boardLists,
            'lists' => $boardLists,
            'list_index' => $listIndex,
            'project' => $project,
            'filters' => $requests,
            'tasks' => $tasks
        ]);
    }

    /**
     * Display the project table view page with a specific task.
     *
     * @param string $uid The UID of the project to view.
     * @param string $taskUid The UID of the task to view.
     * @param \Illuminate\Http\Request $request The current request instance.
     * @return \Inertia\Response Returns the Inertia response containing the project table view page with the specified task.
     */
    public function viewTableWithTask($uid, $taskUid, Request $request)
    {
        $requests = $request->all();
        $authId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $authId)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $listIndex = [];
        $boardLists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($boardLists as &$listItem) {
            $listIndex[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex += 1;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->whereHas('list')
            ->with('assignees')
            ->with('list')
            ->orderByOrder()
            ->get()->toArray();
        foreach ($tasks as $task) {
            $boardLists[$listIndex[$task['list_id']]]['tasks'][] = $task;
        }
        return Inertia::render('Projects/Table', [
            'title' => 'Projects',
            'board_lists' => $boardLists,
            'lists' => $boardLists,
            'list_index' => $listIndex,
            'filters' => $requests,
            'project' => $project,
            'task' => Task::where('id', $taskUid)->orWhere('slug', $taskUid)->first(),
            'timer' => Timer::with('task')->mine()->running()->first() ?? null,
            'tasks' => $tasks
        ]);
    }


    /**
     * Display the project dashboard page.
     *
     * @param string $uid The UID of the project to view.
     * @return \Inertia\Response Returns the Inertia response containing the project dashboard page.
     */
    public function viewDashboard($uid)
    {
        $authId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $authId)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $taskIds = Task::where('project_id', $project->id)->pluck('id')->toArray();
        $perList = Task::select('list_id', DB::raw('count(*) as total'))->where('project_id', $project->id)->groupBy('list_id')->whereHas('list')->with('list')->get()->toArray();
        $perAssignee = Assignee::select('user_id', DB::raw('count(*) as total'))->whereIn('task_id', $taskIds)->groupBy('user_id')->with('user')->get()->toArray();
        $perLabel = TaskLabel::select('label_id', DB::raw('count(*) as total'))->whereIn('task_id', $taskIds)->groupBy('label_id')->with('label')->get()->toArray();
        $dueDone = Task::where('project_id', $project->id)->where('is_done', 1)->count();
        $noDue = Task::where('project_id', $project->id)->whereNull('due_date')->count();
        $dueOver = Task::where('project_id', $project->id)->where('due_date', '<', Carbon::now())->count();
        $dueLater = Task::where('project_id', $project->id)->where('due_date', '>', Carbon::now()->addDay())->count();
        $dueSoon = Task::where('project_id', $project->id)->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDay()])->count();
        return Inertia::render('Projects/Dashboard', [
            'title' => 'Dashboard | ' . $project->title,
            'per_list' => $perList,
            'project' => $project,
            'per_assignee' => $perAssignee,
            'per_label' => $perLabel,
            'due_data' => [
                ['due' => ['name' => 'Complete', 'color' => '#22A06B'], 'total' => $dueDone],
                ['due' => ['name' => 'Due soon', 'color' => '#B38600'], 'total' => $dueSoon],
                ['due' => ['name' => 'Due later', 'color' => '#E56910'], 'total' => $dueLater],
                ['due' => ['name' => 'Overdue', 'color' => '#C9372C'], 'total' => $dueOver],
                ['due' => ['name' => 'No due date', 'color' => '#607d8b'], 'total' => $noDue],
            ]
        ]);
    }

    /**
     * Display the project calendar page.
     *
     * @param string $uid The UID of the project to view.
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
    public function viewCalendar($uid, Request $request)
    {
        $requests = $request->all();
        $authId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $authId)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $listIndex = [];
        $boardLists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($boardLists as &$listItem) {
            $listIndex[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex += 1;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->with('timer')
            ->whereHas('list')
            ->with('assignees')
            ->with('list')
            ->orderByOrder()
            ->get()->toArray();
        foreach ($tasks as $task) {
            $boardLists[$listIndex[$task['list_id']]]['tasks'][] = $task;
        }
        return Inertia::render('Projects/Calendar', [
            'title' => 'Calendar | ' . $project->title,
            'board_lists' => $boardLists,
            'lists' => $boardLists,
            'list_index' => $listIndex,
            'project' => $project,
            'filters' => $requests,
            'tasks' => $tasks
        ]);
    }

    /**
     * Display the project time logs page.
     *
     * @param string $projectUid The UID of the project to get time logs for.
     * @param \Illuminate\Http\Request $request The current request instance.
     * @return \Inertia\Response Returns the Inertia response containing the project time logs.
     */
    public function viewTimeLogs($projectUid, Request $request)
    {
        $requests = $request->all();
        $authId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $authId)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($projectUid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $timerQuery = Timer::whereHas('task', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->filter($requests);
        return Inertia::render('Projects/Timer.vue', [
            'title' => 'Time Logs | ' . $project->title,
            'project' => $project,
            'filters' => $requests,
            'total_duration' => $timerQuery->sum('duration'),
            'time_logs' => $timerQuery->with('task')
                ->with('user')
                ->orderBy('created_at', 'DESC')
                ->paginate(9)
                ->withQueryString()
                ->through(function ($log) {
                    return [
                        'id' => $log->id,
                        'title' => $log->title,
                        'user' => $log->user,
                        'task' => $log->task,
                        'task_id' => $log->task_id,
                        'duration' => $log->duration,
                        'started_at' => $log->started_at,
                        'stopped_at' => $log->stopped_at,
                        'created_at' => $log->created_at,
                    ];
                }),
        ]);
    }

    /**
     * Get project-related data for a specific project.
     *
     * @param int $projectId The ID of the project to get data for.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the project data.
     */
    public function projectOtherData($projectId)
    {
        $project = Project::where('id', $projectId)->first();
        $labels = Label::get();
        $lists = BoardList::withCount('tasks')->get();
        $teamMembers = TeamMember::with('user')->where('workspace_id', $project->workspace_id)->get();
        return response()->json(['labels' => $labels, 'lists' => $lists, 'team_members' => $teamMembers]);
    }

    /**
     * Get workspace-related data for a specific workspace.
     *
     * @param int $workspaceId The ID of the workspace to get data for.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the workspace data.
     */
    public function workspaceOtherData($workspaceId)
    {
        $labels = Label::get();
        $teamMembers = TeamMember::with('user')->where('workspace_id', $workspaceId)->get();
        return response()->json(['labels' => $labels, 'team_members' => $teamMembers]);
    }

    /**
     * Update the specified project in the database.
     *
     * @param  int  $id The ID of the project to update.
     * @param  Request  $request The current request instance containing the new data for the project.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the updated project data.
     */
    public function update($id, Request $request)
    {
        $project = Project::whereId($id)->first();
        $requestData = $request->all();
        foreach ($requestData as $itemKey => $itemValue) {
            $project->{$itemKey} = $itemValue;
        }
        $project->save();
        return response()->json($project);
    }

    /**
     * Deletes the specified project and its associated data.
     *
     * @param  int  $id The ID of the project to delete.
     * @return \Illuminate\Http\RedirectResponse Redirects to the workspace view after the project and its associated data have been deleted.
     */
    public function destroy($id)
    {
        $project = Project::where('id', $id)->first();
        $workspaceId = $project->workspace_id;
        if (!empty($project)) {
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
        return Redirect::route('workspace.view', $workspaceId);
    }

    /**
     * Get all projects.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all projects.
     */
    public function all()
    {
        $projects = Project::get();
        return response()->json($projects);
    }

    /**
     * Get all projects in JSON format.
     *
     * @param int $workspaceId The ID of the workspace to get projects for.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the projects for the specified workspace.
     */
    public function jsonAll($workspaceId)
    {
        $projects = Project::where('workspace_id', $workspaceId)->with('background')->with('star')->get();
        return response()->json($projects);
    }

    /**
     * Create a new project using JSON data.
     *
     * @param \Illuminate\Http\Request $request The current request instance containing the new project data.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the new project data.
     */
    public function jsonCreate(Request $request)
    {
        $requests = $request->all();
        $requests['user_id'] = auth()->id();
        $project = Project::create($requests);

        $slug = StringHelper::sanitizeForSlug($project->title);
        $existingItem = Project::where('slug', $slug)->first();
        if (!empty($existingItem)) {
            $slug = $slug . '-' . $project->id;
        }
        $project->slug = $slug;
        $project->save();

        return response()->json($project);
    }

    /**
     * Get project members in JSON format.
     *
     * @param int $projectId The ID of the project to get members for.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the project members.
     */
    public function jsonMembers($projectId)
    {
        $assignees = Assignee::whereHas('task', function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        })->where('user_id', '!=', auth()->id())->groupBy('user_id')->with('user:id,first_name,last_name,photo_path')->get();
        return response()->json($assignees);
    }

    /**
     * Get project filter data in JSON format.
     *
     * @param int $projectId The ID of the project to get filter data for.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the project filter data.
     */
    public function jsonFilterData($projectId)
    {
        $assignees = Assignee::whereHas('task', function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        })->where('user_id', '!=', auth()->id())->groupBy('user_id')->with('user:id,first_name,last_name,photo_path')->get();
        $labels = Label::orderBy('name')->get();
        return response()->json(['assignees' => $assignees, 'labels' => $labels]);
    }

    /**
     * Get recently accessed projects in JSON format.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the recently accessed projects.
     */
    public function jsonRecent()
    {
        $userId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $userId)->orWhereHas('member')->pluck('id');
        $projects = RecentProject::where('user_id', $userId)->with('project')->has('project.workspace')->whereHas('project', function ($q) use ($workspaceIds) {
            $q->whereIn('workspace_id', $workspaceIds);
        })->orderBy('opened', 'desc')->paginate(10)
            ->through(function ($project) {
                return [
                    'id' => $project->project->id,
                    'title' => $project->project->title,
                    'slug' => $project->project->slug,
                    'star' => (bool)$project->project->star,
                    'workspace' => $project->project->workspace->name,
                    'background' => $project->project->background ? $project->project->background->image : null,
                ];
            });
        return response()->json($projects);
    }

    /**
     * Get starred projects in JSON format.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the starred projects.
     */
    public function jsonStar()
    {
        $userId = auth()->id();
        $workspaceIds = Workspace::where('user_id', $userId)->orWhereHas('member')->pluck('id');
        $projects = StarredProject::where('user_id', $userId)->with('project')->has('project.workspace')->whereHas('project', function ($q) use ($workspaceIds) {
            $q->whereIn('workspace_id', $workspaceIds);
        })->orderBy('updated_at', 'desc')->paginate(100)
            ->through(function ($project) {
                return [
                    'id' => $project->project->id,
                    'title' => $project->project->title,
                    'slug' => $project->project->slug,
                    'star' => (bool)$project->project->star,
                    'workspace' => $project->project->workspace->name,
                    'background' => $project->project->background ? $project->project->background->image : null,
                ];
            });
        return response()->json($projects);
    }
}
