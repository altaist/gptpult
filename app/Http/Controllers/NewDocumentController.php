<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Services\Documents\DocumentLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NewDocumentController extends Controller
{
    protected DocumentLimitService $documentLimitService;
    protected \App\Services\RecaptchaService $recaptchaService;

    public function __construct(
        DocumentLimitService $documentLimitService,
        \App\Services\RecaptchaService $recaptchaService
    ) {
        $this->documentLimitService = $documentLimitService;
        $this->recaptchaService = $recaptchaService;
    }

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        // Проверяем лимиты пользователя
        $limitCheck = $this->documentLimitService->canCreateDocument($user);
        
        // Если пользователь не может создать документ, показываем экран ограничения
        if (!$limitCheck['can_create']) {
            return Inertia::render('documents/DocumentLimitReached', [
                'limit_info' => $limitCheck,
                'message' => $this->documentLimitService->getLimitMessage($user)
            ]);
        }

        // Если лимит позволяет, показываем форму создания
        return Inertia::render('documents/NewDocument', [
            'document_types' => DocumentType::all(),
            'limit_info' => $limitCheck,
            'recaptcha' => [
                'site_key' => $this->recaptchaService->getSiteKey(),
                'enabled' => $this->recaptchaService->isEnabled()
            ]
        ]);
    }
} 