<?php

namespace Vis\Builder;

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
            if (! Sentinel::check()) {
                if (Request::ajax()) {
                    $data = [
                        'status'  => 'error',
                        'code'    => '401',
                        'message' => 'Unauthorized',
                    ];

                    return Response::json($data, '401');
                } else {
                    return  response()->view('admin::errors.401', [], 401);
                }
            }
        } catch (\Cartalyst\Sentinel\Checkpoints\NotActivatedException $e) {
            Session::flash('login_not_found', 'Пользователь не активирован');
            //Sentinel::logout(); //Если неактивен, то будет ошибка

            return  response()->view('admin::errors.401', [], 401);
        }

        return $next($request);
    }
}
