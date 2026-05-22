<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\QuizCorrectionService;
use App\Services\QuizGeneratorService;
use App\Services\QuizResultService;

final class QuizController
{
    public function start(): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/dashboard');
        }

        $objectiveId = (int) ($_POST['objective_id'] ?? 0);
        $userId = (int) Session::get('user_id');

        $generator = new QuizGeneratorService();
        $sessionId = $generator->startObjectiveQuiz($userId, $objectiveId);

        redirect('/quiz/' . $sessionId);
    }

    public function play(string $id): void
    {
        $sessionId = (int) $id;

        $service = new QuizCorrectionService();
        $session = $service->getSession($sessionId);

        if (!$this->canAccessSession($session)) {
            $this->notFound();
            return;
        }

        $question = $service->getCurrentQuestion($sessionId);

        if ($question === null) {
            redirect('/quiz/' . $sessionId . '/results');
        }

        View::render('quiz.play', [
            'title' => 'Quiz',
            'session' => $session,
            'question' => $question,
            'options' => json_decode((string) $question['options_json'], true) ?: [],
            'hideBottomNav' => true,
        ]);
    }

    public function answer(string $id): void
    {
        $sessionId = (int) $id;

        $service = new QuizCorrectionService();
        $session = $service->getSession($sessionId);

        if (!$this->canAccessSession($session)) {
            $this->notFound();
            return;
        }

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/quiz/' . $sessionId);
        }

        $answerId = (int) ($_POST['answer_id'] ?? 0);
        $userAnswer = (string) ($_POST['answer'] ?? '');

        $service->answerQuestion($sessionId, $answerId, $userAnswer);

        redirect('/quiz/' . $sessionId . '/feedback/' . $answerId);
    }

    public function feedback(string $sessionId, string $answerId): void
    {
        $sessionIdInt = (int) $sessionId;
        $answerIdInt = (int) $answerId;

        $service = new QuizCorrectionService();
        $session = $service->getSession($sessionIdInt);

        if (!$this->canAccessSession($session)) {
            $this->notFound();
            return;
        }

        $answer = $service->getAnswerForFeedback($sessionIdInt, $answerIdInt);

        if ($answer === null) {
            redirect('/quiz/' . $sessionIdInt);
        }

        View::render('quiz.feedback', [
            'title' => 'Correction',
            'session' => $session,
            'answer' => $answer,
            'hideBottomNav' => true,
        ]);
    }

    public function results(string $id): void
    {
        $sessionId = (int) $id;

        $correctionService = new QuizCorrectionService();
        $session = $correctionService->getSession($sessionId);

        if (!$this->canAccessSession($session)) {
            $this->notFound();
            return;
        }

        $resultService = new QuizResultService();
        $resultContext = $session['user_id'] === null
            ? $resultService->buildGuestResultContext($sessionId)
            : $resultService->buildResultContext((int) $session['user_id'], $sessionId);

        if ($resultContext === null) {
            $this->notFound();
            return;
        }

        View::render('quiz.results', [
            'title' => 'Résultat',
            'resultContext' => $resultContext,
        ]);
    }

    public function retryErrors(string $id): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/quiz/' . $id . '/results');
        }

        $sourceSessionId = (int) $id;
        $userId = (int) Session::get('user_id');

        $generator = new QuizGeneratorService();

        try {
            $newSessionId = $generator->startErrorReviewQuiz($userId, $sourceSessionId);
        } catch (\Throwable $exception) {
            Session::flash('error', 'Aucune erreur à revoir.');
            redirect('/quiz/' . $sourceSessionId . '/results');
        }

        redirect('/quiz/' . $newSessionId);
    }

    private function canAccessSession(?array $session): bool
    {
        if ($session === null) {
            return false;
        }

        $currentUserId = Session::get('user_id');

        if ($currentUserId === null) {
            return $session['user_id'] === null;
        }

        return $session['user_id'] !== null && (int) $session['user_id'] === (int) $currentUserId;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo 'Quiz introuvable';
    }
}
