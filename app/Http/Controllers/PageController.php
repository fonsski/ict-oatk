<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    
     * Отображение страницы условий использования
     *
     * @return \Illuminate\View\View

    public function terms()
    {
        return view('pages.terms');
    }

    
     * Отображение страницы политики конфиденциальности
     *
     * @return \Illuminate\View\View

    public function privacy()
    {
        return view('pages.privacy');
    }
}
