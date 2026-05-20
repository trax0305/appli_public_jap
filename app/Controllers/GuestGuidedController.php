<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\GuestGuidedService;

final class GuestGuidedController
{
    public function index(): void
    {
        View::render('public.guest-guided', [
            'title' => 'Parcours guidé invité',
        ], 'public');
    }

    public function start(): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/guest/guided');
        }

        $service = new GuestGuidedService();
        $pathCode = $service->normalizePathCode((string) ($_POST['path_code'] ?? 'hiragana_base'));

        try {
            $progress = $service->start($pathCode);
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/guest/guided');
        }

        redirect('/guest/missions/' . (int) $progress['mission_id']);
    }

    public function mission(string $id): void
    {
        $missionId = (int) $id;
        $mission = (new GuestGuidedService())->getMissionForGuest($missionId);

        if ($mission === null) {
            redirect('/guest/guided');
        }

        if (!empty($mission['is_completed'])) {
            View::render('public.guest-guided-success', [
                'title' => 'Mission terminée',
                'mission' => $mission,
            ], 'public');
            return;
        }

        View::render('public.guest-mission', [
            'title' => (string) $mission['title'],
            'mission' => $mission,
        ], 'public');
    }

    public function discovery(string $id): void
    {
        $missionId = (int) $id;
        $mission = (new GuestGuidedService())->getMissionForGuest($missionId);

        if ($mission === null) {
            redirect('/guest/guided');
        }

        View::render('public.guest-mission-discovery', [
            'title' => 'Découverte',
            'mission' => $mission,
        ], 'public');
    }

    public function completeDiscovery(string $id): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/guest/missions/' . (int) $id . '/discovery');
        }

        $missionId = (int) $id;

        try {
            (new GuestGuidedService())->completeDiscovery($missionId);
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/guest/guided');
        }

        redirect('/guest/missions/' . $missionId);
    }

    public function startQuiz(): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/guest/guided');
        }

        $objectiveId = (int) ($_POST['objective_id'] ?? 0);

        try {
            $sessionId = (new GuestGuidedService())->startQuiz($objectiveId);
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/guest/guided');
        }

        redirect('/quiz/' . $sessionId);
    }
}
