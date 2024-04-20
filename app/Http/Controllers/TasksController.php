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
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TeamMember;
use App\Models\Timer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Mavinoo\Batch\BatchFacade as Batch;


class TasksController extends Controller
{
    /**
     * Create a new task
     * @param Request $request - contains the new task data
     * @return \Illuminate\Http\JsonResponse - returns the newly created task with its relationships loaded
     */
    public function createTask(Request $request)
    {
        $userId = auth()->id();
        $requestData = $request->all();
        $requestData['user_id'] = $userId;
        $task = Task::create($requestData);
        $slug = StringHelper::sanitizeForHtml($task->title);
        $existingItem = Task::where('slug', $slug)->first();

        if (!empty($existingItem)) {
            $slug = $slug . '-' . $task->id;
        }

        $task->slug = $slug;
        $task->save();

        $task->load('lastAssignee')
            ->load('taskLabels.label')
            ->loadCount('checklistDone')
            ->loadCount('comments')
            ->loadCount('checklists')
            ->loadCount('attachments')
            ->loadCount('assignees');

        return response()->json($task);
    }

    /**
     * Update a specific task
     * @param int $taskId - the ID of the task to update
     * @param Request $request - contains the updated task data
     * @return \Illuminate\Http\JsonResponse - updated task with its relationships loaded
     */
    public function updateTask($taskId, Request $request)
    {
        $task = Task::whereId($taskId)->first();
        $requestData = $request->all();

        foreach ($requestData as $itemKey => $itemValue) {
            $task->{$itemKey} = $itemValue;

            if ($itemKey == 'title') {
                $slug = StringHelper::sanitizeForHtml($itemValue);
                $existingItem = Task::where('slug', $slug)->first();

                if (!empty($existingItem)) {
                    $slug = $slug . '-' . $task->id;
                }

                $task->slug = $slug;
            }
        }

        $task->save();
        $task->load('list')->load('taskLabels.label')->load('assignees');

        return response()->json($task);
    }

    /**
     * Update the order of tasks
     * @param Request $request - contains the updated task order data
     * @return \Illuminate\Http\JsonResponse - result of the batch update operation
     */
    public function updateTaskOrder(Request $request)
    {
        $requestData = $request->all();
        $result = Batch::update(new Task, $requestData, 'id');

        return response()->json($result);
    }

    /**
     * Update the task list for a specific project
     * @param int $projectId - the ID of the project
     * @param Request $request - contains the updated task list data
     * @return \Illuminate\Http\JsonResponse - result of the batch update operation
     */
    public function updateTaskListByProjectId($projectId, Request $request)
    {
        $data = $request->all();
        $fromLists = [];
        $newList = [];

        if (!empty($data['is_move'])) {
            $fromLists = Task::where('list_id', $data['previous_list'])
                            ->where('project_id', $projectId)
                            ->orderBy('order')
                            ->select(['id', 'order'])
                            ->get()
                            ->toArray();

            $toLists = Task::where('list_id', $data['new_list'])
                            ->where('project_id', $projectId)
                            ->orderBy('order')
                            ->pluck('id')
                            ->toArray();

            $previousOrder = array_search($data['task_id'], $toLists);
            $out = array_splice($toLists, $previousOrder, 1);
            array_splice($toLists, $data['to'] - 1, 0, $out);
        } else {
            $toLists = Task::where('list_id', $data['new_list'])
                            ->orderBy('order')
                            ->pluck('id')
                            ->toArray();

            $out = array_splice($toLists, $data['from'] - 1, 1);
            array_splice($toLists, $data['to'] - 1, 0, $out);
        }

        foreach ($toLists as $itemKey => $itemVal) {
            $newList[$itemKey] = ['id' => $itemVal, 'order' => $itemKey + 1];
        }

        $result = Batch::update(new Task, $fromLists + $newList, 'id');

        return response()->json($result);
    }

    /**
     * Delete a task
     * @param int $id - the ID of the task to delete
     * @return \Illuminate\Http\JsonResponse - returns the result of the delete operation
     */
    public function deleteDask($id)
    {
        $result = null;
        $task = Task::where('id', $id)->first();

        if (!empty($task)) {
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

            $result = $task->delete();
        }

        return response()->json($result);
    }

    /**
     * Add an attachment to a task
     * @param int $id - the ID of the task
     * @param Request $request - contains the attachment file
     * @return \Illuminate\Http\JsonResponse - returns the newly created attachment or an error response
     */
    public function addAttachment($id, Request $request)
    {
        $attachment = [];

        if ($request->file('file')) {
            $file = $request->file('file');
            $allowedMimeTypes = [
                'image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/svg+xml', 'image/tiff',
                'video/x-flv', 'video/mp4', 'video/3gpp', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/mpeg',
                'text/plain', 'text/csv',
                'audio/wav', 'audio/aac', 'audio/mpeg',
                'application/x-mpegURL', 'application/pdf', 'application/vnd.ms-powerpoint', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
            $contentType = $file->getClientmimeType();

            if (!in_array($contentType, $allowedMimeTypes)) {
                return response()->json(['error' => true]);
            }

            list($width, $height) = getimagesize($file);
            $fileNameOrigin = $file->getClientOriginalName();
            $fileName = uniqid() . '-' . StringHelper::sanitizeForHtml(pathinfo($fileNameOrigin, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $size = $file->getSize();
            $filePath = '/files/' . $file->storeAs('tasks', $fileName, ['disk' => 'file_uploads']);
            $attachment = Attachment::create(['task_id' => $id, 'name' => $fileNameOrigin, 'user_id' => auth()->id(), 'size' => $size, 'path' => $filePath, 'width' => $width, 'height' => $height]);
        }

        return response()->json($attachment);
    }

    /**
     * Remove an attachment from a task
     * @param int $id - the ID of the attachment
     * @return \Illuminate\Http\JsonResponse - returns the result of the delete operation
     */
    public function removeAttachment($id)
    {
        $attachment = Attachment::find($id);

        if (!empty($attachment) && !empty($attachment->path) && File::exists(public_path($attachment->path))) {
            File::delete(public_path($attachment->path));
        }

        $result = $attachment->delete();

        return response()->json($result);
    }

    /**
     * Count the number of tasks in a specific list
     * @param int $id - the ID of the list
     * @return \Illuminate\Http\JsonResponse - returns the count of tasks in the list
     */
    public function countListItemsById($id)
    {
        $taskCount = Task::where('list_id', $id)->count();

        return response()->json($taskCount);
    }

    /**
     * Get other data related to a task and project
     * @param int $taskId - the ID of the task
     * @param int $projectId - the ID of the project
     * @return \Illuminate\Http\JsonResponse - returns the labels, lists, timer, duration, projects, and team members
     */
    public function taskOtherData($taskId, $projectId)
    {
        $project = Project::where('id', $projectId)->first();
        $labels = Label::get();
        $lists = BoardList::withCount('tasks')->get();
        $projects = Project::get();
        $teamMembers = TeamMember::with('user')->groupBy('user_id')->where('workspace_id', $project->workspace_id)->get();
        $timer = Timer::running()->mine()->where('task_id', '!=', $taskId)->first() ?? null;
        $duration = Timer::where('task_id', $taskId)->sum('duration');

        return response()->json(['labels' => $labels, 'lists' => $lists, 'timer' => $timer, 'duration' => $duration, 'projects' => $projects, 'team_members' => $teamMembers]);
    }

    /**
     * Get archived tasks for a specific project
     * @param int $projectId - the ID of the project
     * @return \Illuminate\Http\JsonResponse - archived tasks with their associated data
     */
    public function jsonArchiveTasks($projectId)
    {
        $archiveTasks = Task::where('is_archive', 1)
            ->byProject($projectId)
            ->withCount('checklistDone')
            ->withCount('comments')
            ->withCount('checklists')
            ->withCount('attachments')
            ->with('assignees')
            ->with('list')
            ->has('list')
            ->get();

        return response()->json($archiveTasks);
    }

    /**
     * Get a specific task by its ID or slug
     * @param string $taskId - the ID or slug of the task
     * @return \Illuminate\Http\JsonResponse - returns the task with its associated data
     */
    public function jsonGetTask($taskId)
    {
        $task = Task::where('id', $taskId)
                    ->orWhere('slug', $taskId)
                    ->with('project')
                    ->with('timer')
                    ->with('cover')
                    ->with('list')
                    ->with('checklists')
                    ->with('comments.user')
                    ->with('attachments')
                    ->with('assignees')
                    ->with('taskLabels.label')
                    ->withCount('checklistDone')
                    ->first();

        return response()->json($task);
    }
}
