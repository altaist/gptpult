<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AutoAuthController extends Controller
{
    /**
     * Автоматический вход по токену
     */
    public function autoLogin(Request $request)
    {
        $request->validate([
            'auth_token' => 'required|string'
        ]);

        $user = User::where('auth_token', $request->auth_token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        Auth::login($user);
        
        return response()->json([
            'user' => $user,
            'message' => 'Successfully logged in'
        ]);
    }

    /**
     * Автоматическая регистрация
     */
    public function autoRegister(Request $request)
    {
        $request->validate([
            'auth_token' => 'required|string',
            'name' => 'required|string|max:255',
            'data' => 'nullable|array'
        ]);

        // Проверяем, не существует ли уже пользователь с таким токеном
        $existingUser = User::where('auth_token', $request->auth_token)->first();
        if ($existingUser) {
            Auth::login($existingUser);
            return response()->json([
                'user' => $existingUser,
                'message' => 'User already exists'
            ]);
        }

        // Подготавливаем данные для person
        $personData = $request->data ?? [];
        if (isset($personData['telegram'])) {
            $personData['telegram'] = [
                'id' => $personData['telegram']['id'] ?? null,
                'username' => $personData['telegram']['username'] ?? null,
                'data' => $personData['telegram']['data'] ?? []
            ];
        }

        // Создаем нового пользователя
        $user = User::create([
            'name' => $request->name,
            'email' => Str::random(10) . '@auto.user',
            'password' => Hash::make(Str::random(16)),
            'auth_token' => $request->auth_token,
            'role_id' => 0, // Обычный пользователь
            'status' => 1, // Активный
            'person' => $personData,
            'settings' => [],
            'statistics' => []
        ]);

        Auth::login($user);

        return response()->json([
            'user' => $user,
            'message' => 'Successfully registered'
        ]);
    }

    /**
     * Выход из системы
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Successfully logged out']);
    }
} 