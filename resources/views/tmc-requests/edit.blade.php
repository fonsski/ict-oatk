@extends('layouts.app')

@section('title', 'Редактирование заявки ТМЦ - ICT Help')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
    <div class="mb-8">
        <nav class="text-sm text-slate-500 mb-1">
            <a href="{{ route('tmc-requests.index') }}" class="hover:text-slate-700">Заявки ТМЦ</a>
            <span class="mx-1">/</span>
            <a href="{{ route('tmc-requests.show', $request) }}" class="hover:text-slate-700">{{ $request->number ?: '#'.$request->id }}</a>
            <span class="mx-1">/</span><span>Редактирование</span>
        </nav>
        <h1 class="text-3xl font-bold text-slate-900">Редактирование заявки ТМЦ</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <form action="{{ route('tmc-requests.update', $request) }}" method="POST">
            @csrf
            @method('PUT')
            @include('tmc-requests._form')
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('tmc-requests.show', $request) }}" class="text-slate-600 hover:text-slate-800 font-medium">Отмена</a>
                <button type="submit" class="btn-primary">Сохранить изменения</button>
            </div>
        </form>
    </div>
</div>
@endsection
