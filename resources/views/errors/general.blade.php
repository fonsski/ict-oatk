@extends('layouts.app')

@section('title', isset($title) ? $title : 'Ошибка ' . ($code ?? 'Unknown'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 mb-8">
        <div class="text-center mb-8">
            <div class="relative mx-auto mb-6">
                <svg class="w-24 h-24 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                @if(isset($code) && $code)
                <div class="absolute top-0 right-0 -mt-2 -mr-2 bg-gray-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                    {{ $code }}
                </div>
                @endif
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ isset($title) ? $title : 'Произошла ошибка' }}</h1>
            <p class="text-slate-600 text-lg max-w-2xl mx-auto">{{ isset($message) ? $message : 'К сожалению, при обработке вашего запроса произошла ошибка. Пожалуйста, попробуйте снова или свяжитесь с технической поддержкой.' }}</p>
        </div>

        @if(isset($details) && !empty($details))
            <div class="bg-gray-50 border-l-4 border-gray-400 p-4 mb-6 mx-auto max-w-2xl">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-700">{{ $details }}</p>
                    </div>
                </div>
            </div>
        @endif

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
                @if(isset($code) && $code)
                <p class="mt-1">Код ошибки: {{ $code }}</p>
                @endif
                @if(isset($id) && $id)
                <p class="mt-1">ID: {{ $id }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
