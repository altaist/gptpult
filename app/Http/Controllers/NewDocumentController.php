<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewDocumentController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        return Inertia::render('NewDocument', [
            'document_types' => DocumentType::all()
        ]);
    }
} 