@extends('layouts.app')

@section('title', 'Новая заявка ТМЦ - ICT Help')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
    <div class="mb-8">
        <nav class="text-sm text-slate-500 mb-1">
            <a href="{{ route('tmc-requests.index') }}" class="hover:text-slate-700">Заявки ТМЦ</a>
            <span class="mx-1">/</span><span>Новая</span>
        </nav>
        <h1 class="text-3xl font-bold text-slate-900">Новая заявка на приобретение ТМЦ</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <form action="{{ route('tmc-requests.store') }}" method="POST">
            @csrf
            @include('tmc-requests._form')
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('tmc-requests.index') }}" class="text-slate-600 hover:text-slate-800 font-medium">Отмена</a>
                <button type="submit" class="btn-primary">Сохранить заявку</button>
            </div>
        </form>
    </div>
</div>
@endsection
