@extends('layouts.app')

@section('title', 'Новая схема сети - ICT')

@section('content')
<div class="container-width section-padding">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-slate-900 mb-6">Новая схема сети</h1>

        <div class="card p-6 sm:p-8">
            <form action="{{ route('topology.store') }}" method="POST" class="space-y-6">
                @csrf

                @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4">
                    <ul class="list-disc pl-5 text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Название</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           minlength="2" maxlength="255" placeholder="Например: Главный корпус"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Описание <span class="text-gray-400">(необязательно)</span>
                    </label>
                    <textarea id="description" name="description" rows="3" maxlength="2000"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Короткое описание схемы">{{ old('description') }}</textarea>
                </div>

                <div class="flex justify-end gap-4 pt-2">
                    <a href="{{ route('topology.index') }}" class="btn-secondary">Отмена</a>
                    <button type="submit" class="btn-primary">Создать и открыть редактор</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
