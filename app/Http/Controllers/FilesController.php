<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Files\FilesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class FilesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    private FilesService $filesService;

    public function __construct(FilesService $filesService)
    {
        $this->filesService = $filesService;
    }

    /**
     * Загрузка файла
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Максимум 10MB
            'display_name' => 'nullable|string|max:255',
            'document_id' => 'nullable|exists:documents,id'
        ]);

        $file = $this->filesService->saveFile(
            $request->file('file'),
            $request->user(),
            $request->input('display_name'),
            $request->input('document_id')
        );

        return response()->json([
            'message' => 'Файл успешно загружен',
            'file' => $file
        ]);
    }

    /**
     * Скачивание файла
     */
    public function download(File $file): StreamedResponse
    {
        // Проверяем права доступа
        $this->authorize('download', $file);

        return response()->streamDownload(function() use ($file) {
            echo Storage::disk($file->storage_disk)->get($file->path);
        }, $file->display_name, [
            'Content-Type' => $file->mime_type
        ]);
    }

    /**
     * Просмотр файла в браузере
     */
    public function view(File $file): StreamedResponse
    {
        // Проверяем права доступа
        $this->authorize('view', $file);

        return response()->streamDownload(function() use ($file) {
            echo Storage::disk($file->storage_disk)->get($file->path);
        }, $file->display_name, [
            'Content-Type' => $file->mime_type
        ]);
    }

    /**
     * Удаление файла
     */
    public function destroy(File $file)
    {
        // Проверяем права доступа
        $this->authorize('delete', $file);

        $this->filesService->deleteFile($file);

        return response()->json([
            'message' => 'Файл успешно удален'
        ]);
    }

    /**
     * Получение информации о файле
     */
    public function show(File $file)
    {
        // Проверяем права доступа
        $this->authorize('view', $file);

        return response()->json([
            'file' => $file,
            'url' => $file->getPublicUrl(),
            'formatted_size' => $file->getFormattedSize()
        ]);
    }

    /**
     * Обновление информации о файле
     */
    public function update(Request $request, File $file)
    {
        // Проверяем права доступа
        $this->authorize('update', $file);

        $request->validate([
            'display_name' => 'required|string|max:255'
        ]);

        $file->update([
            'display_name' => $request->input('display_name')
        ]);

        return response()->json([
            'message' => 'Информация о файле обновлена',
            'file' => $file
        ]);
    }
} 