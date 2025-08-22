<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class SettingsController extends Controller
{
    /**
     * Отображение документации по исправлению ошибок OAuth
     *
     * @param string $docName Имя документации
     * @return \Illuminate\Http\Response
     */
    public function viewDocumentation($docName)
    {
        $path = base_path("docs/{$docName}.md");

        if (!File::exists($path)) {
            abort(404, "Документация не найдена");
        }

        $content = File::get($path);

        // Возвращаем содержимое с правильным Content-Type для Markdown
        return Response::make($content, 200, [
            "Content-Type" => "text/markdown",
            "Content-Disposition" => 'inline; filename="' . $docName . '.md"',
        ]);
    }
}
