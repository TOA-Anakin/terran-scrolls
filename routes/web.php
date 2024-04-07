<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\StarredProjectsController;
use App\Http\Controllers\WorkSpacesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// AUTH - REGISTRATION, LOGIN, LOGOUT, PASSWORD RESET
Route::post('register', [AuthenticatedSessionController::class, 'registerStore'])
    ->name('register.store')
    ->middleware('guest');

Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store')
    ->middleware('guest');

Route::post('password-reset-email', [AuthenticatedSessionController::class, 'forgotPasswordMail'])
    ->name('password.reset.email')
    ->middleware('guest');

Route::post('password-reset-confirm', [AuthenticatedSessionController::class, 'forgotPasswordStore'])
    ->name('password.reset.store')
    ->middleware('guest');

Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login')
    ->middleware('guest');

Route::get('register', [AuthenticatedSessionController::class, 'register'])
    ->name('register')
    ->middleware('guest');

Route::get('password-reset', [AuthenticatedSessionController::class, 'forgotPassword'])
    ->name('password.reset')
    ->middleware('guest');

Route::get('password-reset/{token}', [AuthenticatedSessionController::class, 'forgotPasswordToken'])
    ->name('password.reset.token')
    ->middleware('guest');

Route::delete('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');


Route::middleware(['auth'])->group(function () {

    // DASHBOARD, HOME
    Route::get('/', [WorkSpacesController::class, 'index'])->name('dashboard');

    Route::get('/home', function () {
        return redirect()->route('dashboard');
    })->name('home');


    // WORKSPACES
    Route::delete('workspace/destroy/{id}', [WorkSpacesController::class, 'destroy'])->name('workspace.destroy');


    // -- WORKSPACES - view routes
    Route::get('w/{uid}', [WorkSpacesController::class, 'workspaceView'])->name('workspace.view');

    Route::get('w/{uid}/members', [WorkSpacesController::class, 'workspaceMembers'])->name('workspace.members');

    Route::get('w/{uid}/tables', [WorkSpacesController::class, 'workspaceTables'])->name('workspace.tables');


    // -- WORKSPACES - JSON routes
    Route::post('json/workspace/create', [WorkSpacesController::class, 'jsonCreate'])->name('json.workspace.create');

    Route::post('json/workspace/change', [WorkSpacesController::class, 'jsonChangeWorkspace'])->name('json.workspace.change');

    Route::post('json/workspace/update/{id}', [WorkSpacesController::class, 'jsonUpdateWorkspace'])->name('json.workspace.update');

    Route::post('json/workspace/member/add', [WorkSpacesController::class, 'jsonAddMember'])->name('json.workspace.member.add');

    Route::get('json/workspaces/mine', [WorkSpacesController::class, 'jsonMineAll'])->name('json.workspaces.mine');

    Route::get('json/workspaces/all', [WorkSpacesController::class, 'jsonAll'])->name('json.workspaces.all');

    Route::get('json/workspaces/other_users/{workspaceId}', [WorkSpacesController::class, 'getOtherUsers'])->name('json.workspaces.users.other');

    Route::get('json/menu_data/workspaces', [WorkSpacesController::class, 'jsonMineAll'])->name('json.menu_data.workspaces');


    // PROJECTS
    Route::post('project/update/{id}', [ProjectsController::class, 'update'])->name('project.update');

    Route::get('project/all', [ProjectsController::class, 'all'])->name('project.all');

    Route::get('project/other/data/{projectId}', [ProjectsController::class, 'projectOtherData'])->name('project.other.data');

    Route::get('workspace/other/data/{workspaceId}', [ProjectsController::class, 'workspaceOtherData'])->name('workspace.other.data');


    // -- PROJECTS - view routes
    Route::get('projects', [ProjectsController::class, 'index'])->name('projects.index');

    Route::get('project/test', [ProjectsController::class, 'test'])->name('project.test');

    Route::get('p/na', [ProjectsController::class, 'noProject'])->name('projects.view.na');

    Route::get('p/board/{uid}', [ProjectsController::class, 'view'])->name('projects.view.board');

    Route::get('p/board/{projectUid}/task/{taskUid}', [ProjectsController::class, 'viewWithTask'])->name('projects.board.with.task');

    Route::get('p/table/{uid}', [ProjectsController::class, 'viewTable'])->name('projects.view.table');

    Route::get('p/table/{projectUid}/task/{taskUid}', [ProjectsController::class, 'viewTableWithTask'])->name('projects.table.with.task');

    Route::get('p/calendar/{uid}', [ProjectsController::class, 'viewCalendar'])->name('projects.view.calendar');

    Route::get('p/dashboard/{uid}', [ProjectsController::class, 'viewDashboard'])->name('projects.view.dashboard');

    Route::get('p/time-logs/{uid}', [ProjectsController::class, 'viewTimeLogs'])->name('projects.view.time_logs');


    // -- PROJECTS - JSON routes
    Route::post('json/project/create', [ProjectsController::class, 'jsonCreate'])->name('json.project.create');

    Route::get('json/projects/all/{workspaceId}', [ProjectsController::class, 'jsonAll'])->name('json.projects.all');

    Route::get('json/projects/recent', [ProjectsController::class, 'jsonRecent'])->name('json.projects.recent');

    Route::get('json/projects/star', [ProjectsController::class, 'jsonStar'])->name('json.projects.star');

    Route::get('json/project/members/{projectId}', [ProjectsController::class, 'jsonMembers'])->name('json.project.members');

    Route::get('json/project/filter/data/{projectId}', [ProjectsController::class, 'jsonFilterData'])->name('json.project.filter.data');


    // STARRED PROJECTS
    Route::post('json/p/starred/save/{projectId}', [StarredProjectsController::class, 'toggleStarred'])->name('json.p.starred.save');
});
