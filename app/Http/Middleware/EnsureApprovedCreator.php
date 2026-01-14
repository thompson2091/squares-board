<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedCreator
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canCreateBoards()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'You must be an approved creator to create boards. Please contact an administrator to request creator access.');
        }

        return $next($request);
    }
}
