<?php

namespace Linecore\Cms;

use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Closure;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

class AuthenticateFrontend
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            if (!Sentinel::check()) {
                return $this->unauthorizedResponse($request);
            }
        } catch (\Cartalyst\Sentinel\Checkpoints\NotActivatedException $e) {
            Session::flash('login_not_found', 'Пользователь не активирован');
            //Sentinel::logout(); ошибка при неактивированном
            return $this->unauthorizedResponse($request);
        }

        return $next($request);
    }

    protected function unauthorizedResponse($request)
    {
        if (Request::ajax()) {
            return Response::json([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->view('admin::errors.401', [], 401);
    }
}
