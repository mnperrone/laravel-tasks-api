<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Lista de campos que nunca se almacenan en sesión ante excepciones de validación.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Registra los callbacks de manejo de excepciones para la aplicación.
     */
    public function register(): void
    {
        // Puedes agregar lógica de reportes personalizados aquí.
    }

    /**
     * Renderiza una excepción como respuesta HTTP.
     */
    public function render($request, Throwable $e): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;
            $headers = [];

            if ($e instanceof HttpExceptionInterface) {
                $headers = $e->getHeaders();
            }

            $defaultMessage = __('Server Error');
            $message = $e->getMessage() ?: $defaultMessage;

            if ($status >= 500 && !config('app.debug')) {
                $message = $defaultMessage;
            }

            $payload = ['message' => $message];

            if (config('app.debug')) {
                $payload['exception'] = class_basename($e);
            }

            return response()->json($payload, $status, $headers);
        }

        return parent::render($request, $e);
    }
}
