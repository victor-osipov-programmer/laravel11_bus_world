<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e) {
            return response([
                'error' => [
                    'code' => 422,
                    'message' => "Validation error",
                    'errors' => $e->errors(),
                ]
            ], 422);
        });
        $exceptions->render(function (NotFoundHttpException $e) {
            return response([
                'error' => [
                    'code' => 404,
                    'message' => "Not found",
                ]
            ], 404);
        });
    })->create();
