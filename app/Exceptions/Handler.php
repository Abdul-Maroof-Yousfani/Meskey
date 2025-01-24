<?php

namespace App\Exceptions;

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

    

    /**
     * Handle exceptions and customize responses.
     */
    public function render($request, Throwable $exception)
    {
        // Validation Error
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        // Authorization Error
        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        // Model Not Found Error
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'message' => 'The requested resource was not found.',
            ], 404);
        }

        // Default behavior (for general errors)
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'An error occurred.',
            ], $this->isHttpException($exception) ? $exception->getStatusCode() : 500);
        }

        // Fallback to Laravel's default handling
        return parent::render($request, $exception);
    }


}
