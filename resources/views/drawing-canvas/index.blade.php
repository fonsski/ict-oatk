@extends('layouts.app')

@section('title', 'Инструмент для рисования')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Чертежи и диаграммы</h1>
            <p class="text-slate-600">Создавайте и редактируйте различные диаграммы и чертежи</p>
        </div>
        <a href="{{ route('drawing-canvas.create') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Создать новый чертёж
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    @if($drawings->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="2" width="20" height="20" rx="2" ry="2"></rect>
            <line x1="6" y1="6" x2="6" y2="18"></line>
            <line x1="10" y1="6" x2="10" y2="18"></line>
            <line x1="14" y1="6" x2="14" y2="18"></line>
            <line x1="18" y1="6" x2="18" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="6"></line>
            <line x1="6" y1="10" x2="18" y2="10"></line>
            <line x1="6" y1="14" x2="18" y2="14"></line>
            <line x1="6" y1="18" x2="18" y2="18"></line>
        </svg>
        <h3 class="text-lg font-semibold text-slate-700 mb-2">Ещё нет чертежей</h3>
        <p class="text-slate-500 mb-6">Создайте свой первый чертёж или диаграмму прямо сейчас!</p>
        <a href="{{ route('drawing-canvas.create') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Создать чертёж
        </a>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
            @foreach($drawings as $drawing)
            <div class="border border-slate-200 rounded-lg hover:shadow-md transition-shadow duration-200 overflow-hidden">
                <a href="{{ route('drawing-canvas.show', $drawing) }}" class="block">
                    <div class="h-48 bg-slate-100 border-b border-slate-200 flex items-center justify-center">
                        @if($drawing->type == 'network')
                        <svg class="w-16 h-16 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                            <line x1="6" y1="10" x2="6" y2="14"></line>
                            <line x1="12" y1="10" x2="12" y2="14"></line>
                            <line x1="18" y1="10" x2="18" y2="14"></line>
                        </svg>
                        @elseif($drawing->type == 'floorplan')
                        <svg class="w-16 h-16 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        @else
                        <svg class="w-16 h-16 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="2" ry="2"></rect>
                            <line x1="2" y1="7" x2="22" y2="7"></line>
                            <line x1="16" y1="2" x2="16" y2="22"></line>
                        </svg>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-slate-900 mb-1">{{ $drawing->title }}</h3>
                        @if($drawing->description)
                        <p class="text-slate-600 text-sm mb-3 line-clamp-2">{{ $drawing->description }}</p>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">{{ $drawing->created_at->format('d.m.Y') }}</span>
                            <span class="text-xs px-2 py-1 bg-slate-100 text-slate-700 rounded-full">{{ ucfirst($drawing->type) }}</span>
                        </div>
                    </div>
                </a>
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end space-x-2">
                    <a href="{{ route('drawing-canvas.edit', $drawing) }}" class="text-blue-600 hover:text-blue-800">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form action="{{ route('drawing-canvas.destroy', $drawing) }}" method="POST" class="inline-block" onsubmit="return confirm('Вы уверены, что хотите удалить этот чертёж?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6">
        {{ $drawings->links() }}
    </div>
    @endif
</div>
@endsection
