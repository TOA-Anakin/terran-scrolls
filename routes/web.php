<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProjectsController;
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
Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login')
    ->middleware('guest');

Route::get('register', [AuthenticatedSessionController::class, 'register'])
    ->name('register')
    ->middleware('guest');

Route::get('password-reset', [AuthenticatedSessionController::class, 'forgotPassword'])
    ->name('password.reset')
    ->middleware('guest');

Route::post('password-reset-email', [AuthenticatedSessionController::class, 'forgotPasswordMail'])
    ->name('password.reset.email')
    ->middleware('guest');

Route::get('password-reset/{token}', [AuthenticatedSessionController::class, 'forgotPasswordToken'])
    ->name('password.reset.token')
    ->middleware('guest');

Route::post('password-reset-confirm', [AuthenticatedSessionController::class, 'forgotPasswordStore'])
    ->name('password.reset.store')
    ->middleware('guest');

Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store')
    ->middleware('guest');

Route::post('register', [AuthenticatedSessionController::class, 'registerStore'])
    ->name('register.store')
    ->middleware('guest');

Route::delete('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');


Route::middleware(['auth'])->group(function () {

    // DASHBOARD, HOME
    Route::get('/', [WorkSpacesController::class, 'index'])->name('dashboard');

    Route::get('/home', function() {
        return redirect()->route('dashboard');
    })->name('home');


    // WORKSPACES
    Route::get('json/workspaces/mine', [WorkSpacesController::class, 'jsonMineAll'])->name('json.workspaces.mine');

    Route::get('json/workspaces/all', [WorkSpacesController::class, 'jsonAll'])->name('json.workspaces.all');

    Route::get('json/workspaces/other_users/{workspace_id}', [WorkSpacesController::class, 'getOtherUsers'])->name('json.workspaces.users.other');

    Route::post('json/workspace/create', [WorkSpacesController::class, 'jsonCreate'])->name('json.workspace.create');

    Route::post('json/workspace/member/add', [WorkSpacesController::class, 'jsonAddMember'])->name('json.workspace.member.add');

    Route::post('json/workspace/change', [WorkSpacesController::class, 'jsonChangeWorkspace'])->name('json.workspace.change');

    Route::post('json/workspace/update/{id}', [WorkSpacesController::class, 'jsonUpdateWorkspace'])->name('json.workspace.update');

    Route::get('json/menu_data/workspaces', [WorkSpacesController::class, 'jsonMineAll'])->name('json.menu_data.workspaces');

    Route::get('w/{uid}', [WorkSpacesController::class, 'workspaceView'])->name('workspace.view');

    Route::get('w/{uid}/members', [WorkSpacesController::class, 'workspaceMembers'])->name('workspace.members');

    Route::get('w/{uid}/tables', [WorkSpacesController::class, 'workspaceTables'])->name('workspace.tables');

    Route::delete('workspace/destroy/{id}', [WorkSpacesController::class, 'destroy'])->name('workspace.destroy');

    // PROJECTS
    Route::get('p/board/{uid}', [ProjectsController::class, 'view'])->name('projects.view.board');

    Route::get('p/na', [ProjectsController::class, 'noProject'])->name('projects.view.na');
});
