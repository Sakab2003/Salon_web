<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
    }

    public function render($request, Throwable $e)
    {
        $wantsApi = $request->ajax() || $request->expectsJson() || $request->is('api/*');

        if ($wantsApi && $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: 'Action non autorisee.';

            return response()->json([
                'status' => false,
                'message' => $message,
            ], $status);
        }

        if ($e instanceof ModelNotFoundException) {
            $isAjax = $request->ajax() || $request->expectsJson() || $request->is('api/*');
            if ($isAjax) {
                $alreadyGone = in_array($request->method(), ['DELETE', 'POST', 'PUT', 'PATCH'], true);

                return response()->json([
                    'status' => $alreadyGone,
                    'message' => 'Cet élément est introuvable ou a déjà été supprimé.',
                ], $alreadyGone ? 200 : 404);
            }
        }

        return parent::render($request, $e);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api*')) {
            return response()->json(['error' => __('auth.unauthenticated')], 401);
        }

        return redirect()->guest(route('login'));
    }
}
