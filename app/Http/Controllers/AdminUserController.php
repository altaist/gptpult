<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Список всех пользователей
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Поиск по имени, email или ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        // Фильтр по роли
        if ($request->filled('role')) {
            $query->where('role_id', $request->role);
        }

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->withCount(['documents', 'orders', 'payments'])
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles' => collect(UserRole::cases())->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label()
            ])
        ]);
    }

    /**
     * Показать форму создания пользователя
     */
    public function create()
    {
        return Inertia::render('admin/users/Create', [
            'roles' => collect(UserRole::cases())->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label()
            ])
        ]);
    }

    /**
     * Создать нового пользователя
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'status' => 'nullable|integer',
            'balance_rub' => 'nullable|numeric|min:0',
            'telegram_id' => 'nullable|string',
            'telegram_username' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Пользователь успешно создан');
    }

    /**
     * Показать пользователя
     */
    public function show(User $user)
    {
        $user->load(['documents.documentType', 'orders', 'payments', 'transitions']);

        return Inertia::render('admin/users/Show', [
            'user' => $user,
            'statistics' => [
                'documents_count' => $user->documents()->count(),
                'orders_count' => $user->orders()->count(),
                'payments_sum' => $user->payments()->sum('amount'),
                'balance' => $user->balance_rub ?? 0,
            ]
        ]);
    }

    /**
     * Показать форму редактирования пользователя
     */
    public function edit(User $user)
    {
        return Inertia::render('admin/users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id->value,
                'status' => $user->status,
                'balance_rub' => $user->balance_rub,
                'telegram_id' => $user->telegram_id,
                'telegram_username' => $user->telegram_username,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'telegram_linked_at' => $user->telegram_linked_at,
                'privacy_consent_at' => $user->privacy_consent_at,
            ],
            'roles' => collect(UserRole::cases())->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label()
            ])
        ]);
    }

    /**
     * Обновить пользователя
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role_id' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'status' => 'nullable|integer',
            'balance_rub' => 'nullable|numeric',
            'telegram_id' => 'nullable|string',
            'telegram_username' => 'nullable|string',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Пользователь успешно обновлен');
    }

    /**
     * Удалить пользователя
     */
    public function destroy(User $user)
    {
        // Проверяем, что это не текущий пользователь
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Нельзя удалить самого себя');
        }

        // Проверяем, есть ли связанные данные
        if ($user->documents()->exists() || $user->orders()->exists()) {
            return back()->with('error', 'Нельзя удалить пользователя с документами или заказами');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно удален');
    }
} 