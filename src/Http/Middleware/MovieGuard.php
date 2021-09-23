<?php

namespace Haxibiao\Media\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MovieGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
		if (config('media.movie.enable',false)) {
			return abort(404);
		}
        return $next($request);
    }
}
