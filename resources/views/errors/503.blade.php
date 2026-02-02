@extends('layouts.app')

@section('title', 'Сервис недоступен - Ошибка 503')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 mb-8">
        <div class="text-center mb-8">
            <div class="relative mx-auto mb-6">
                <svg class="w-24 h-24 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="8" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="6" y="4" width="12" height="16" rx="2" ry="2"></rect>
                    <line x1="12" y1="4" x2="12" y2="20"></line>
                </svg>
                <div class="absolute top-0 right-0 -mt-2 -mr-2 bg-purple-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                    503
                </div>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Сервис временно недоступен</h1>
            <p class="text-slate-600 text-lg max-w-2xl mx-auto">В настоящий момент сервис находится на техническом обслуживании или испытывает высокую нагрузку. Пожалуйста, попробуйте снова через несколько минут.</p>
        </div>

        <div class="bg-purple-50 border-l-4 border-purple-400 p-4 mb-6 mx-auto max-w-2xl">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-purple-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-purple-700">
                        Наша команда работает над восстановлением сервиса. Обычно это занимает не более 15-30 минут. Благодарим за терпение!
                    </p>
                </div>
            </div>
        </div>

        <div id="countdown" class="text-center mb-8 hidden">
            <p class="text-gray-600">Повторная попытка через: <span id="timer" class="font-medium">30</span> сек</p>
            <div class="w-full max-w-xs mx-auto bg-gray-200 rounded-full h-2.5 mt-2">
                <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 100%"></div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mt-8">
            <button id="refresh-btn" class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 text-center">
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Обновить страницу
                </span>
            </button>
        </div>

        <div class="mt-10 border-t border-gray-200 pt-8">
            <div class="text-center text-gray-500 text-sm">
                <p>Время: {{ now()->format('d.m.Y H:i:s') }}</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const refreshBtn = document.getElementById('refresh-btn');
        const countdown = document.getElementById('countdown');
        const timer = document.getElementById('timer');
        const progressBar = document.getElementById('progress-bar');

        refreshBtn.addEventListener('click', function() {
            refreshBtn.disabled = true;
            refreshBtn.classList.add('opacity-50', 'cursor-not-allowed');
            countdown.classList.remove('hidden');

            let seconds = 30;
            const interval = setInterval(() => {
                seconds--;
                timer.textContent = seconds;
                progressBar.style.width = (seconds / 30 * 100) + '%';

                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.reload();
                }
            }, 1000);
        });
    });
</script>
@endsection
