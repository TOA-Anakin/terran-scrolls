<?php

namespace App\Http\Controllers\Auth;

use App\Events\ForgotPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $isDemo = (int)config('app.demo');
        $enableRegistration = Setting::where('slug', 'enable_registration')->first();
        return Inertia::render('Auth/Login', ['is_demo' => $isDemo, 'enable_registration' => $enableRegistration]);
    }

    /**
     * Display the registration view.
     *
     * @return \Inertia\Response
     */
    public function register()
    {
        $isDemo = (int)config('app.demo');
        return Inertia::render('Auth/Register', ['is_demo' => $isDemo]);
    }

    /**
     * Display the forgot password view.
     *
     * @return \Inertia\Response
     */
    public function forgotPassword()
    {
        $isDemo = (int)config('app.demo');
        return Inertia::render('Auth/ForgotPassword', ['is_demo' => $isDemo]);
    }

    /**
     * Send forgot password mail.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forgotPasswordMail(Request $request)
    {
        $requestData = $request->validate(['email' => 'required|email|exists:users']);

        $token = Str::random(64);
        DB::table('password_resets')->insert([
            'email' => $requestData['email'],
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        event(new ForgotPassword(['email' => $requestData['email'], 'token' => $token]));

        return back()->with('success', 'We have e-mailed your password reset link!');
    }

    /**
     * Display the forgot password input view.
     *
     * @param $token
     * @return \Inertia\Response
     */
    public function forgotPasswordToken($token)
    {
        return Inertia::render('Auth/ForgotPasswordInput', ['token' => $token]);
    }

    /**
     * Store the new password.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forgotPasswordStore(Request $request)
    {
        $requestData = $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
            'token' => 'required'
        ]);

        $updatePassword = DB::table('password_resets')
            ->where([
                'email' => $requestData['email'],
                'token' => $requestData['token']
            ])
            ->first();

        if (!$updatePassword) {
            return Redirect::back()->with('error', 'Invalid email or token!');
        }

        User::where('email', $requestData['email'])->update(['password' => Hash::make($requestData['password'])]);

        DB::table('password_resets')->where(['email' => $requestData['email']])->delete();

        return Redirect::route('login')->with('success', 'Your password has been changed!');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::DASHBOARD);
    }

    /**
     * Handle an incoming registration request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function registerStore(Request $request)
    {

        $requestData = $request->validate([
            'first_name' => ['required', 'max:50'],
            'last_name' => ['required', 'max:50'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:10'],
            'phone' => ['nullable', 'max:20'],
            'country' => ['nullable', 'max:20'],
            'city' => ['nullable', 'max:30'],
            'address' => ['nullable'],
        ]);

        $role = Role::where('slug', 'normal')->first();
        if (!empty($role)) {
            $requestData['role_id'] = $role->id;
        } else {
            $requestData['role_id'] = 2;
        }


        $user = User::create($requestData);
        Auth::loginUsingId($user->id, true);

        $request->session()->regenerate();
        return redirect()->intended(RouteServiceProvider::DASHBOARD);
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
