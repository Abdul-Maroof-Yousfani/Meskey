<?php

namespace App\Http\Middleware;

use App\Models\Acl\{LoginHistory};
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Closure;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }


    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);


        $user = $request->user();

        $previousLogin = LoginHistory::where('user_id', $user->id)
            ->where('header', $request->header('User-Agent'))
            ->exists();

        if (!$previousLogin) {
            if ($user && $user->security == 1 && ($request->path() !== 'verify' && $request->path() !== 'verification/resend')) {
                return redirect('/verify');
            } elseif ($user && $user->security == 2 && ($request->path() !== 'setup-google-authenticator' && $request->path() !== 'setup-google-authenticator-code')) {
                return redirect('/setup-google-authenticator');
            }
        }
        return $next($request);
    }

}
