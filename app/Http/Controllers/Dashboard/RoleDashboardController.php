<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RoleDashboardController extends Controller
{
    public function customer(): Response
    {
        return Inertia::render('Dashboards/Customer');
    }

    public function performer(): Response
    {
        return Inertia::render('Dashboards/Performer');
    }

    public function moderator(): Response
    {
        return Inertia::render('Dashboards/Moderator');
    }

    public function admin(): Response
    {
        return Inertia::render('Dashboards/Admin');
    }
}
