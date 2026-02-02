@extends('layouts.app')

@section('title', 'Слишком много запросов - Ошибка 429')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 mb-8">
        <div class="text-center mb-8">
            <div class="relative mx-auto mb-6">
                <svg class="w-24 h-24 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <div class="absolute top-0 right-0 -mt-2 -mr-2 bg-indigo-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                    429
                </div>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Слишком много запросов</h1>
            <p class="text-slate-600 text-lg max-w-2xl mx-auto">Вы отправили слишком много запросов за короткий период времени. Пожалуйста, подождите некоторое время перед следующей попыткой.</p>
        </div>

        <div class="bg-indigo-50 border-l-4 border-indigo-400 p-4 mb-6 mx-auto max-w-2xl">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-indigo-700">
                        Эта мера защищает сервер от перегрузки и обеспечивает стабильную работу для всех пользователей. Пожалуйста, повторите попытку через минуту.
                    </p>
                </div>
            </div>
        </div>

        <div id="countdown" class="text-center mb-8">
            <p class="text-gray-600">Повторная попытка будет доступна через: <span id="timer" class="font-medium">60</span> сек</p>
            <div class="w-full max-w-xs mx-auto bg-gray-200 rounded-full h-2.5 mt-2">
                <div id="progress-bar" class="bg-indigo-600 h-2.5 rounded-full" style="width: 100%"></div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mt-8">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 text-center">
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                    </svg>
                    Вернуться назад
                </span>
            </a>
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors duration-200 text-center">
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-7-7v14" />
                    </svg>
                    На главную
                </span>
            </a>
        </div>

        <div class="mt-10 border-t border-gray-200 pt-8">
            <div class="text-center text-gray-500 text-sm">
                <p>Время: {{ now()->format('d.m.Y H:i:s') }}</p>
                <p class="mt-1">Если эта ошибка возникает регулярно, пожалуйста, свяжитесь с технической поддержкой.</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let seconds = 60;
        const timer = document.getElementById('timer');
        const progressBar = document.getElementById('progress-bar');

        const interval = setInterval(() => {
            seconds--;
            timer.textContent = seconds;
            progressBar.style.width = (seconds / 60 * 100) + '%';

            if (seconds <= 0) {
                clearInterval(interval);
                window.location.reload();
            }
        }, 1000);
    });
</script>
@endsection
