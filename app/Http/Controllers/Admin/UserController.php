<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'blocked', fn($query) => $query->where('is_blocked', true))
            ->when($status === 'active', fn($query) => $query->where('is_blocked', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
            'usersCount' => User::count(),
            'verifiedCount' => User::whereNotNull('email_verified_at')->count(),
            'blockedCount' => User::where('is_blocked', true)->count(),
            'adminsCount' => User::where('is_admin', true)->count(),
        ]);
    }

    public function toggleBlock(User $user): RedirectResponse
    {
        if ($user->is(Auth::user())) {
            return back()->withErrors(['user' => 'Нельзя заблокировать самого себя.']);
        }

        if ($user->is_admin && !$user->is_blocked && User::where('is_admin', true)->where('is_blocked', false)->count() <= 1) {
            return back()->withErrors(['user' => 'Нельзя заблокировать последнего активного администратора.']);
        }

        $user->forceFill([
            'is_blocked' => !$user->is_blocked,
        ])->save();

        return back()->with('success', $user->is_blocked ? 'Пользователь заблокирован.' : 'Пользователь разблокирован.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(Auth::user())) {
            return back()->withErrors(['user' => 'Нельзя удалить самого себя.']);
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->withErrors(['user' => 'Нельзя удалить последнего администратора.']);
        }

        $user->delete();

        return back()->with('success', 'Пользователь удалён.');
    }
}
