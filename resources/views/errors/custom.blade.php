@extends('layouts.app')

@section('title', $title ?? 'Ошибка')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 mb-8">
        <div class="text-center mb-8">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ $title ?? 'Ошибка' }}</h1>
            <p class="text-slate-600 text-lg">{{ $message ?? 'Произошла ошибка при обработке запроса.' }}</p>
        </div>

        @if(isset($error) && !empty($error))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($trace) && !empty($trace))
            <div class="mt-6 p-4 bg-gray-100 rounded-lg overflow-auto max-h-96">
                <p class="text-xs font-mono whitespace-pre-wrap">{{ $trace }}</p>
            </div>
        @endif

        <div class="flex justify-center mt-8">
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 mr-4">
                Вернуться назад
            </a>
            <a href="{{ route('home') }}" class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors duration-200">
                На главную
            </a>
        </div>
    </div>
</div>
@endsection
