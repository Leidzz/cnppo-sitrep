<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Reporting Personnel landing screen.
     *
     * Route middleware (auth + role:reporting-personnel) has already verified
     * the user before this runs, so this action only renders the view.
     */
    public function __invoke(): View
    {
        return view('reporting.dashboard');
    }
}
