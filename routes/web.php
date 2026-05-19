<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\OnboardingController;
use App\Core\Router;
use App\Controllers\MissionController;
use App\Controllers\PathController;
use App\Controllers\QuizController;
use App\Controllers\StatsController;
use App\Controllers\ReviewController;
use App\Controllers\FreePracticeController;
use App\Controllers\ProfileController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/guest', [HomeController::class, 'guestEntry']);

$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/onboarding', [OnboardingController::class, 'index']);
$router->post('/onboarding', [OnboardingController::class, 'save']);

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'handleLogin']);

$router->get('/register', [AuthController::class, 'register']);
$router->post('/register', [AuthController::class, 'handleRegister']);

$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/paths', [PathController::class, 'index']);
$router->get('/paths/{id}', [PathController::class, 'show']);

$router->get('/missions/{id}', [MissionController::class, 'show']);
$router->get('/missions/{id}/discovery', [MissionController::class, 'discovery']);
$router->post('/missions/{id}/discovery/complete', [MissionController::class, 'completeDiscovery']);

$router->post('/quiz/start', [QuizController::class, 'start']);
$router->get('/quiz/{id}', [QuizController::class, 'play']);
$router->post('/quiz/{id}/answer', [QuizController::class, 'answer']);
$router->get('/quiz/{id}/results', [QuizController::class, 'results']);
$router->post('/quiz/{id}/retry-errors', [QuizController::class, 'retryErrors']);

$router->get('/free-practice', [FreePracticeController::class, 'index']);
$router->post('/free-practice/start', [FreePracticeController::class, 'start']);

$router->get('/review', [ReviewController::class, 'index']);
$router->post('/review/start', [ReviewController::class, 'start']);

$router->get('/stats', [StatsController::class, 'index']);

$router->get('/profile', [ProfileController::class, 'index']);
$router->post('/profile', [ProfileController::class, 'update']);

return $router;