<?php

namespace OpenDominion\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Log;
use Throwable;
use Illuminate\Auth\AuthenticationException;

use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * {@inheritdoc}
     */
    #public function report(Exception $exception)
    #public function report(Throwable $exception)
    #{
    #    parent::report($exception);
    #}

    public function report(Throwable $exception)
    {
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            xtLog('MethodNotAllowedHttpException for URL: ' . request()->fullUrl(), 'error');
            #Log::error('MethodNotAllowedHttpException for URL: ' . request()->fullUrl());
        }


        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            xtLog('ModelNotFoundException for URL: ' . request()->fullUrl(), 'error');
            Log::error('ModelNotFoundException for URL: ' . request()->fullUrl());
        }
       
        parent::report($exception);
    }

    /**
     * {@inheritdoc}
     */
    protected function context()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json(['error' => 'Method not allowed'], 405);
        }

        return parent::render($request, $exception);
    }

    /**
     * {@inheritdoc}
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : redirect()->guest(route('auth.login'));
    }
}
