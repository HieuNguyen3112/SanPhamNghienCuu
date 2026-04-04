<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminResearchCleanupController extends Controller
{
    private const EXECUTE_CONFIRM = 'DELETE_PAPER_ACTIVITIES';

    public function paperDryRun(Request $request)
    {
        return $this->runPaperCleanup($request, false);
    }

    public function paperExecute(Request $request)
    {
        return $this->runPaperCleanup($request, true);
    }

    private function runPaperCleanup(Request $request, bool $execute)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'chunk' => ['nullable', 'integer', 'min:1', 'max:500'],
            'activity_ids' => ['nullable', 'array'],
            'activity_ids.*' => ['integer', 'min:1'],
            'confirm_token' => ['nullable', 'string'],
        ]);

        if ($execute && (($validated['confirm_token'] ?? '') !== self::EXECUTE_CONFIRM)) {
            return response()->json([
                'message' => 'Xac nhan cleanup chua hop le.',
                'code' => 'PAPER_CLEANUP_CONFIRMATION_REQUIRED',
                'required_confirm_token' => self::EXECUTE_CONFIRM,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $options = [];
        if (! empty($validated['limit'])) {
            $options['--limit'] = (int) $validated['limit'];
        }
        if (! empty($validated['chunk'])) {
            $options['--chunk'] = (int) $validated['chunk'];
        }
        if (! empty($validated['activity_ids']) && is_array($validated['activity_ids'])) {
            $options['--activity-id'] = array_values(array_map('intval', $validated['activity_ids']));
        }
        if ($execute) {
            $options['--execute'] = true;
            if (app()->environment('production')) {
                $options['--force-production'] = true;
            }
        }

        $startedAt = microtime(true);
        $command = 'spnc:research:cleanup-paper';

        try {
            $exitCode = Artisan::call($command, $options);
            $output = Artisan::output();
        } catch (\Throwable $exception) {
            Log::error('research_activity.paper_cleanup_http_failed', [
                'execute' => $execute,
                'message' => $exception->getMessage(),
                'options' => $this->sanitizeOptionsForLog($options),
                'requested_by' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => $execute
                    ? 'Khong the thuc thi cleanup paper activity.'
                    : 'Khong the chay dry-run cleanup paper activity.',
                'code' => 'PAPER_CLEANUP_COMMAND_FAILED',
                'technical_message' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $mode = $execute ? 'execute' : 'dry_run';

        Log::warning('research_activity.paper_cleanup_http_triggered', [
            'mode' => $mode,
            'exit_code' => $exitCode,
            'duration_ms' => $durationMs,
            'options' => $this->sanitizeOptionsForLog($options),
            'requested_by' => $request->user()?->id,
        ]);

        AuditLogger::log($request, [
            'action_group' => 'system',
            'action_code' => $execute ? 'PAPER_CLEANUP_EXECUTED' : 'PAPER_CLEANUP_DRY_RUN',
            'action_label' => $execute ? 'Chay cleanup paper activity' : 'Dry-run cleanup paper activity',
            'target_type' => 'research_activities',
            'target_id' => null,
            'target_display' => 'kind=paper',
            'request_http_status' => Response::HTTP_OK,
            'changes' => [
                'mode' => $mode,
                'exit_code' => $exitCode,
                'duration_ms' => $durationMs,
                'options' => $this->sanitizeOptionsForLog($options),
            ],
        ], $request->user());

        return response()->json([
            'success' => $exitCode === 0,
            'message' => 'ok',
            'data' => [
                'mode' => $mode,
                'command' => $command,
                'exit_code' => $exitCode,
                'duration_ms' => $durationMs,
                'options' => $this->sanitizeOptionsForLog($options),
                'output' => $output,
                'required_confirm_token' => $execute ? null : self::EXECUTE_CONFIRM,
            ],
        ], Response::HTTP_OK);
    }

    private function sanitizeOptionsForLog(array $options): array
    {
        $sanitized = [];

        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_values(array_map('intval', $value));
                continue;
            }

            $sanitized[$key] = is_bool($value) ? $value : (int) $value;
        }

        return $sanitized;
    }
}
