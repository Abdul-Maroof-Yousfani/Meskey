<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::error('Unauthenticated', 401, [
                'success' => false,
                'message' => 'Unauthenticated. Please provide a valid token.'
            ]);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Handle exceptions and customize responses.
     */
    public function render($request, Throwable $exception)
    {
        if ($request->has('skip-error-format')) {
            return parent::render($request, $exception);
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'message' => 'The requested resource was not found.',
            ], 404);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'An error occurred.',
            ], $this->isHttpException($exception) ? $exception->getStatusCode() : 500);
        }

        if ($request->is('api/*')) {
            if ($exception instanceof AuthenticationException) {
                return $this->unauthenticated($request, $exception);
            }

            return ApiResponse::error(
                'Server Error',
                500,
                [
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'exception' => config('app.debug') ? [
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTrace()
                    ] : null
                ]
            );
        }

        return parent::render($request, $exception);
    }
}
