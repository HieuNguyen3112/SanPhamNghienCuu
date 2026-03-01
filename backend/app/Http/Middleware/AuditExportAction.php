<?php

namespace App\Http\Middleware;

use App\Support\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditExportAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $path = trim($request->path(), '/');
        $format = $this->resolveExportFormat($path);
        $statusCode = $response->getStatusCode();
        $success = $statusCode >= 200 && $statusCode < 400;

        AuditLogger::log($request, [
            'action_group' => $this->resolveActionGroup($path),
            'action_code' => 'EXPORT_' . strtoupper($format),
            'action_label' => 'Xuất tệp ' . strtoupper($format),
            'target_type' => 'export',
            'target_display' => '/' . $path,
            'result_status' => $success ? 'success' : 'failure',
            'result_error_message' => $success ? null : 'Không thể xuất tệp',
            'request_http_status' => $statusCode,
            'note' => $request->query() ? 'query:' . http_build_query($request->query()) : null,
        ], $request->user());

        return $response;
    }

    private function resolveExportFormat(string $path): string
    {
        if (str_contains($path, '/pdf')) {
            return 'pdf';
        }

        if (str_contains($path, '/excel')) {
            return 'excel';
        }

        return 'file';
    }

    private function resolveActionGroup(string $path): string
    {
        if (str_contains($path, '/hours/')) {
            return 'approval';
        }

        return 'research';
    }
}
