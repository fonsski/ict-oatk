@extends('layouts.app')

@section('title', 'Тестирование страниц ошибок')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 mb-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Тестирование страниц ошибок</h1>
            <p class="text-slate-600 text-lg">Эта страница позволяет протестировать отображение различных страниц ошибок</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 text-red-500 rounded-lg mb-4 mx-auto">
                    <span class="font-bold">404</span>
                </div>
                <h3 class="font-semibold text-lg text-center mb-2">Страница не найдена</h3>
                <p class="text-gray-600 text-sm mb-4 text-center">Not Found</p>
                <a href="{{ route('test.404') }}" class="block w-full text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Тестировать</a>
            </div>

            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 text-red-500 rounded-lg mb-4 mx-auto">
                    <span class="font-bold">403</span>
                </div>
                <h3 class="font-semibold text-lg text-center mb-2">Доступ запрещен</h3>
                <p class="text-gray-600 text-sm mb-4 text-center">Forbidden</p>
                <a href="{{ route('test.403') }}" class="block w-full text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Тестировать</a>
            </div>

            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 text-red-500 rounded-lg mb-4 mx-auto">
                    <span class="font-bold">401</span>
                </div>
                <h3 class="font-semibold text-lg text-center mb-2">Неавторизован</h3>
                <p class="text-gray-600 text-sm mb-4 text-center">Unauthorized</p>
                <a href="{{ route('test.401') }}" class="block w-full text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Тестировать</a>
            </div>

            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 text-red-500 rounded-lg mb-4 mx-auto">
                    <span class="font-bold">500</span>
                </div>
                <h3 class="font-semibold text-lg text-center mb-2">Внутренняя ошибка сервера</h3>
                <p class="text-gray-600 text-sm mb-4 text-center">Internal Server Error</p>
                <a href="{{ route('test.500') }}" class="block w-full text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Тестировать</a>
            </div>

            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 text-red-500 rounded-lg mb-4 mx-auto">
                    <span class="font-bold">503</span>
                </div>
                <h3 class="font-semibold text-lg text-center mb-2">Сервис недоступен</h3>
                <p class="text-gray-600 text-sm mb-4 text-center">Service Unavailable</p>
                <a href="{{ route('test.503') }}" class="block w-full text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Тестировать</a>
            </div>

            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 text-red-500 rounded-lg mb-4 mx-auto">
                    <span class="font-bold">419</span>
                </div>
                <h3 class="font-semibold text-lg text-center mb-2">CSRF-токен истек</h3>
                <p class="text-gray-600 text-sm mb-4 text-center">Page Expired</p>
                <a href="{{ route('test.419') }}" class="block w-full text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Тестировать</a>
            </div>

            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 text-red-500 rounded-lg mb-4 mx-auto">
                    <span class="font-bold">429</span>
                </div>
                <h3 class="font-semibold text-lg text-center mb-2">Слишком много запросов</h3>
                <p class="text-gray-600 text-sm mb-4 text-center">Too Many Requests</p>
                <a href="{{ route('test.429') }}" class="block w-full text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Тестировать</a>
            </div>
        </div>

        <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="font-semibold text-lg mb-4">Тестировать произвольный код ошибки</h3>
            <form action="{{ route('test.custom') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1">
                    <input type="number" name="code" min="400" max="599" value="418" class="w-full rounded-lg border-gray-300 shadow-sm" placeholder="Код ошибки (400-599)">
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Тестировать</button>
            </form>
            <p class="mt-2 text-sm text-gray-500">Пример: 418 (I'm a teapot)</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
        <h2 class="text-xl font-bold text-slate-900 mb-4">О страницах ошибок</h2>
        <p class="text-slate-600 mb-4">
            Для Laravel 12 были созданы пользовательские страницы ошибок, которые дают посетителям понятную информацию о причине ошибки и предлагают дальнейшие действия.
        </p>
        <ul class="list-disc pl-5 space-y-2 text-gray-600">
            <li><strong>404 (Not Found)</strong> - запрашиваемый ресурс не найден</li>
            <li><strong>403 (Forbidden)</strong> - доступ к ресурсу запрещен</li>
            <li><strong>401 (Unauthorized)</strong> - требуется аутентификация</li>
            <li><strong>500 (Internal Server Error)</strong> - внутренняя ошибка сервера</li>
            <li><strong>503 (Service Unavailable)</strong> - сервис временно недоступен</li>
            <li><strong>419 (Page Expired)</strong> - CSRF-токен устарел или отсутствует</li>
            <li><strong>429 (Too Many Requests)</strong> - превышен лимит запросов</li>
        </ul>
    </div>
</div>
@endsection
