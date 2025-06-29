<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class EmailAuthController extends Controller
{
    /**
     * Показать форму ввода email
     */
    public function showEmailForm()
    {
        return Inertia::render('Auth/EmailAuth', [
            'step' => 'email'
        ]);
    }

    /**
     * Отправить код подтверждения на email
     */
    public function sendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = $request->email;
        
        // Генерируем 6-значный код
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Ищем или создаем пользователя
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Пользователь',
                'email' => $email,
                'password' => bcrypt(Str::random(32)), // Случайный пароль
                'email_verification_code' => $code,
                'email_verification_expires_at' => Carbon::now()->addMinutes(10),
            ]);
        } else {
            $user->update([
                'email_verification_code' => $code,
                'email_verification_expires_at' => Carbon::now()->addMinutes(10),
            ]);
        }

        // Отправляем код через уведомление
        $user->notify(new EmailVerificationCode($code));

        return Inertia::render('Auth/EmailAuth', [
            'step' => 'code',
            'email' => $email,
            'message' => 'Код отправлен на вашу почту'
        ]);
    }

    /**
     * Проверить код и авторизовать пользователя
     */
    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->email)
                   ->where('email_verification_code', $request->code)
                   ->where('email_verification_expires_at', '>', Carbon::now())
                   ->first();

        if (!$user) {
            return back()->withErrors([
                'code' => 'Неверный код или код истек'
            ])->withInput();
        }

        // Очищаем код после успешной проверки
        $user->update([
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'email_verified_at' => Carbon::now(),
        ]);

        // Авторизуем пользователя
        Auth::login($user);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Отправить код повторно
     */
    public function resendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Пользователь не найден'
            ])->withInput();
        }

        // Генерируем новый код
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'email_verification_code' => $code,
            'email_verification_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Отправляем код через уведомление
        $user->notify(new EmailVerificationCode($code));

        return back()->with('message', 'Код отправлен повторно');
    }
}
