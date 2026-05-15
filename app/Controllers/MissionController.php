<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Session;
use App\Core\View;
use App\Services\MissionService;
use App\Core\Csrf;

final class MissionController
{
    public function show(string $id): void
    {
        AuthMiddleware::requireAuth();

        $missionId = (int) $id;
        $userId = (int) Session::get('user_id');

        $service = new MissionService();
        $mission = $service->getMissionForUser($userId, $missionId);

        if ($mission === null) {
            http_response_code(404);
            echo 'Mission introuvable';
            return;
        }

        View::render('missions.show', [
            'title' => $mission['title'],
            'mission' => $mission,
        ]);
    }

    public function discovery(string $id): void
    {
        AuthMiddleware::requireAuth();

        $missionId = (int) $id;
        $userId = (int) Session::get('user_id');

        $service = new MissionService();
        $mission = $service->getMissionForUser($userId, $missionId);

        if ($mission === null) {
            http_response_code(404);
            echo 'Mission introuvable';
            return;
        }

        View::render('missions.discovery', [
            'title' => 'Découverte — ' . $mission['title'],
            'mission' => $mission,
        ]);
    }

    public function completeDiscovery(string $id): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/missions/' . $id . '/discovery');
        }

        $missionId = (int) $id;
        $userId = (int) Session::get('user_id');

        $service = new MissionService();
        $service->completeDiscovery($userId, $missionId);

        redirect('/missions/' . $missionId);
    }
}