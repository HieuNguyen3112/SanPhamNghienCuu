<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Luon tra JSON 401 cho SPA (/me va cac route json)
        $this->renderable(function (AuthenticationException $e, $request) {
            return response()->json([
                'code' => 'UNAUTHENTICATED',
                'message' => 'Unauthenticated',
            ], 401);
        });

        // Role/permission bi chan
        $this->renderable(function (UnauthorizedException $e, $request) {
            $message = $e->getMessage() ?: 'Forbidden: missing role or permission';
            return response()->json([
                'code' => 'FORBIDDEN_MISSING_ROLE',
                'message' => $message,
            ], 403);
        });

        // Cac 403 khac (vi du chua verify email) - tra JSON co ma rieng
        $this->renderable(function (HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() === 403 && str_contains(strtolower($e->getMessage()), 'not verified')) {
                return response()->json([
                    'code' => 'UNVERIFIED_EMAIL',
                    'message' => 'Email chua duoc xac minh',
                ], 403);
            }

            if ($e->getStatusCode() === 403 && $request->is('me')) {
                return response()->json([
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage() ?: 'Forbidden',
                ], 403);
            }
        });
    }
}
