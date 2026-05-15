<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class HomeController
{
    public function index(): void
    {
        View::render('public.home', [
            'title' => 'App Japonais — Apprendre les kana simplement',
        ], 'public');
    }

    public function guestEntry(): void
    {
        View::render('public.guest-entry', [
            'title' => 'Commencer gratuitement',
        ], 'public');
    }
}