<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Session;
use App\Core\View;
use App\Services\PathService;

final class PathController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();

        $userId = (int) Session::get('user_id');

        $service = new PathService();

        View::render('paths.index', [
            'title' => 'Parcours',
            'paths' => $service->getPathsForUser($userId),
        ]);
    }

    public function show(string $id): void
    {
        AuthMiddleware::requireAuth();

        $userId = (int) Session::get('user_id');
        $pathId = (int) $id;

        $service = new PathService();
        $path = $service->getPathWithMissions($userId, $pathId);

        if ($path === null) {
            http_response_code(404);
            echo 'Parcours introuvable';
            return;
        }

        View::render('paths.show', [
            'title' => $path['title'],
            'path' => $path,
        ]);
    }
}