<?php

namespace App\Http\Controllers\Auth;

use App\Events\AdminDashboardUpdated;
use App\Http\Controllers\Controller;
use App\Models\Task\Attempt;
use App\Models\Task\Task;
use App\Models\User;
use App\Service\Task\TaskStatus;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        try {
            event(new Registered($user));
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            $user->delete();
            
            return redirect()->route('register')
                ->withInput($request->only('name', 'email'))
                ->with('error', 'Ошибка отправки письма. Проверьте введённые данные.');
        }

        event(new AdminDashboardUpdated(
            'user',
            'Новый пользователь',
            "{$user->name} зарегистрировался",
            [
                'users' => User::count(),
                'tasks' => Task::count(),
                'attempts_today' => Attempt::whereDate('created_at', today())->count(),
                'completed_tasks' => Attempt::where('status', TaskStatus::COMPLETED->value)->count(),
            ],
            [
                'user_name' => $user->name,
                'user_email' => $user->email,
            ],
        ));

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('status', 'verification-link-sent');
    }
}
