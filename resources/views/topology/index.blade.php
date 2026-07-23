@extends('layouts.app')

@section('title', 'Топология сети - ICT')

@section('content')
<div class="container-width section-padding">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-1">Топология сети</h1>
            <p class="text-slate-600">Схемы сети с узлами, связями и привязкой к кабинетам</p>
        </div>
        <a href="{{ route('topology.create') }}" class="btn-primary">Новая схема</a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
        <p class="text-sm text-green-700">{{ session('success') }}</p>
    </div>
    @endif

    @if($diagrams->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($diagrams as $diagram)
        <div class="card p-6 flex flex-col">
            <a href="{{ route('topology.show', $diagram) }}" class="block flex-1">
                <h3 class="text-lg font-semibold text-slate-900 mb-1">{{ $diagram->name }}</h3>
                @if($diagram->description)
                <p class="text-sm text-slate-600 mb-3 line-clamp-2">{{ $diagram->description }}</p>
                @endif
                <p class="text-xs text-slate-500">
                    Узлов: {{ $diagram->nodes_count }} ·
                    {{ optional($diagram->author)->name ?? '—' }} ·
                    {{ format_datetime($diagram->created_at) }}
                </p>
            </a>
            <div class="mt-4 flex items-center gap-4 border-t border-slate-100 pt-3">
                <a href="{{ route('topology.show', $diagram) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Открыть</a>
                <a href="{{ route('topology.print', $diagram) }}" target="_blank" class="text-sm font-medium text-slate-600 hover:text-slate-800">Печать</a>
                <form action="{{ route('topology.destroy', $diagram) }}" method="POST" class="ml-auto"
                      onsubmit="return confirm('Удалить схему «{{ $diagram->name }}» со всеми узлами и связями?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Удалить</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $diagrams->links() }}
    </div>
    @else
    <div class="card p-16 text-center">
        <h3 class="text-lg font-medium text-slate-900 mb-2">Схем пока нет</h3>
        <p class="text-slate-600 mb-6">Создайте первую схему и постройте топологию сети</p>
        <a href="{{ route('topology.create') }}" class="btn-primary">Новая схема</a>
    </div>
    @endif
</div>
@endsection
