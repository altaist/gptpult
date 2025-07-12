<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class QueueOperationsLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Логгируем только запросы связанные с генерацией документов
        if ($this->shouldLogRequest($request)) {
            $this->logRequestStart($request, $startTime);
        }
        
        $response = $next($request);
        
        if ($this->shouldLogRequest($request)) {
            $this->logRequestEnd($request, $response, $startTime);
        }
        
        return $response;
    }
    
    /**
     * Определяет, нужно ли логгировать данный запрос
     */
    private function shouldLogRequest(Request $request): bool
    {
        $path = $request->path();
        
        // Логгируем запросы связанные с генерацией документов
        $patterns = [
            'documents/*/generate-full',
            'documents/*/generation-progress',
            'documents/*/start-generation',
            'api/documents/*/generate-full',
            'api/documents/*/generation-progress',
            'api/documents/*/start-generation',
        ];
        
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Логгирует начало запроса
     */
    private function logRequestStart(Request $request, float $startTime): void
    {
        $documentId = $this->extractDocumentId($request);
        
        Log::channel('queue_operations')->info('🌐 HTTP REQUEST START', [
            'event' => 'http_request_start',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'request_id' => $this->generateRequestId($request),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'document_id' => $documentId,
            'user_id' => Auth::check() ? Auth::id() : null,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'referer' => $request->header('Referer'),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'request_size' => strlen($request->getContent()),
            'has_files' => $request->hasFile('file'),
            'session_id' => session()->getId(),
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid()
        ]);
    }
    
    /**
     * Логгирует окончание запроса
     */
    private function logRequestEnd(Request $request, Response $response, float $startTime): void
    {
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        $documentId = $this->extractDocumentId($request);
        
        $logLevel = $response->getStatusCode() >= 400 ? 'error' : 'info';
        $icon = $response->getStatusCode() >= 400 ? '❌' : '✅';
        
        Log::channel('queue_operations')->{$logLevel}("{$icon} HTTP REQUEST END", [
            'event' => 'http_request_end',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'request_id' => $this->generateRequestId($request),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'document_id' => $documentId,
            'user_id' => Auth::check() ? Auth::id() : null,
            'status_code' => $response->getStatusCode(),
            'status_text' => Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown',
            'response_size' => strlen($response->getContent()),
            'execution_time_ms' => $executionTime,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'process_id' => getmypid(),
            'response_headers' => $this->getImportantHeaders($response)
        ]);
        
        // Дополнительное логгирование для медленных запросов
        if ($executionTime > 1000) { // Более 1 секунды
            Log::channel('queue_operations')->warning('🐌 SLOW HTTP REQUEST', [
                'event' => 'slow_http_request',
                'timestamp' => now()->format('Y-m-d H:i:s.v'),
                'request_id' => $this->generateRequestId($request),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'document_id' => $documentId,
                'user_id' => Auth::check() ? Auth::id() : null,
                'execution_time_ms' => $executionTime,
                'status_code' => $response->getStatusCode(),
                'process_id' => getmypid()
            ]);
        }
    }
    
    /**
     * Извлекает document_id из запроса
     */
    private function extractDocumentId(Request $request): ?int
    {
        // Пытаемся извлечь из URL параметров
        $path = $request->path();
        
        if (preg_match('/documents\/(\d+)/', $path, $matches)) {
            return (int) $matches[1];
        }
        
        // Пытаемся извлечь из параметров запроса
        if ($request->has('document_id')) {
            return (int) $request->input('document_id');
        }
        
        // Пытаемся извлечь из route параметров
        if ($request->route() && $request->route()->parameter('document')) {
            $document = $request->route()->parameter('document');
            if (is_object($document) && isset($document->id)) {
                return $document->id;
            }
            if (is_numeric($document)) {
                return (int) $document;
            }
        }
        
        return null;
    }
    
    /**
     * Генерирует уникальный ID запроса
     */
    private function generateRequestId(Request $request): string
    {
        return substr(md5($request->ip() . $request->userAgent() . microtime(true)), 0, 8);
    }
    
    /**
     * Получает важные заголовки ответа
     */
    private function getImportantHeaders(Response $response): array
    {
        $headers = [];
        
        $importantHeaders = [
            'Content-Type',
            'Content-Length',
            'Cache-Control',
            'Location',
            'Set-Cookie'
        ];
        
        foreach ($importantHeaders as $header) {
            if ($response->headers->has($header)) {
                $headers[$header] = $response->headers->get($header);
            }
        }
        
        return $headers;
    }
} 