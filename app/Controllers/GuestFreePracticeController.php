<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\GuestFreePracticeService;
use App\Services\QuizGeneratorService;

final class GuestFreePracticeController
{
    public function index(): void
    {
        $service = new GuestFreePracticeService();

        View::render('public.guest-free-practice', [
            'title' => 'Mode libre invité',
            'remainingCount' => $service->getRemainingCount(),
            'limit' => GuestFreePracticeService::LIMIT,
            'limitReached' => $service->hasReachedLimit(),
            'groups' => $service->groupOptions(),
        ], 'public');
    }

    public function start(): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/guest/free-practice');
        }

        $guestFreePractice = new GuestFreePracticeService();

        if ($guestFreePractice->hasReachedLimit()) {
            Session::flash('error', 'Tu as utilisé tes 3 quiz gratuits en mode libre.');
            redirect('/guest/free-practice');
        }

        $config = $guestFreePractice->normalizeConfig($_POST);
        $generator = new QuizGeneratorService();

        try {
            $sessionId = $generator->startGuestFreeQuiz($config);
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/guest/free-practice');
        }

        $guestFreePractice->incrementCount();

        redirect('/quiz/' . $sessionId);
    }
}
