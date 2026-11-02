<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sitrep;
use App\Models\Unit;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'userCount' => User::count(),
            'unitCount' => Unit::count(),
            'sitrepCount' => Sitrep::count(),
            'roles' => Role::withCount('users')->get(),
            'latestUsers' => User::latest()->limit(5)->get(),
        ]);
    }
}
