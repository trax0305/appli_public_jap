<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\FreePracticeService;

final class FreePracticeController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();

        $service = new FreePracticeService();

        View::render('free-practice.index', [
            'title' => 'Mode libre',
            'page' => $service->getPageData(),
            'old' => [
                'alphabet' => 'hiragana',
                'quick_filters' => ['vowels'],
                'direction' => 'kana_to_romaji',
                'question_count' => '20',
                'custom_count' => '20',
                'include_wrong' => '0',
                'options_scope' => 'selected',
            ],
        ]);
    }

    public function start(): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/free-practice');
        }

        $service = new FreePracticeService();
        $userId = (int) Session::get('user_id');

        try {
            $sessionId = $service->startFreePracticeQuiz($userId, $_POST);
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/free-practice');
        }

        redirect('/quiz/' . $sessionId);
    }
}
