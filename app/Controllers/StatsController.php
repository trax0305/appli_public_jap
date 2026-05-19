<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Session;
use App\Core\View;
use App\Services\StatsService;

final class StatsController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();

        $userId = (int) Session::get('user_id');

        $service = new StatsService();

        View::render('stats.index', [
            'title' => 'Mes stats',
            'stats' => $service->getStatsPageData($userId),
        ]);
    }
}
