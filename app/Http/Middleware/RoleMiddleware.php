<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
  /**
   * Handle an incoming request.
   *
   * Usage in routes:  ->middleware('role:Admin,HR')
   *
   * Auth::user()->role returns a Role MODEL (belongsTo relationship),
   * NOT a string — must access ->name to compare against the role strings
   * passed as middleware parameters.
   */
  public function handle(Request $request, Closure $next, ...$roles): Response
  {
    if (! Auth::check()) {
      return redirect()->route('home');
    }

    // ->role is a Role model object; ->role?->name is the string e.g. 'Admin'
    $userRole = Auth::user()->role?->name;

    if (! in_array($userRole, $roles)) {
      abort(403, 'Unauthorized. You do not have permission to access this page.');
    }

    return $next($request);
  }
}
