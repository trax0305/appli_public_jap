<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $viewFile = view_path(str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php');
        $layoutFile = view_path('layouts' . DIRECTORY_SEPARATOR . $layout . '.php');

        if (!is_file($viewFile)) {
            throw new \RuntimeException("Vue introuvable : {$view}");
        }

        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout introuvable : {$layout}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }
}