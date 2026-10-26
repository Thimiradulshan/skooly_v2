<?php

namespace App\Http\Middleware;

use App\Academic\ActiveAcademicYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAcademicYearIsConfigured
{
    public function __construct(private readonly ActiveAcademicYear $activeAcademicYear) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->activeAcademicYear->configured() === null) {
            return redirect()
                ->route('school-settings.edit')
                ->with('error', 'Choose an active academic year before starting a year-scoped workflow.');
        }

        return $next($request);
    }
}
