<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Session;
use App\Core\View;
use App\Services\DashboardService;

final class DashboardController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();

        $userId = (int) Session::get('user_id');

        $service = new DashboardService();
        $data = $service->getDashboardData($userId);

        View::render('dashboard.index', [
            'title' => 'Dashboard',
            'dashboard' => $data,
        ]);
    }
}