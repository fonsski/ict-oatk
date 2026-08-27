@extends('layouts.app')

@section('title', 'Новая закупка - ICT Help')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Новая закупка</h1>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('purchases.store') }}" method="POST">
                @csrf
                @include('purchases._form', ['consumables' => $consumables, 'categories' => $categories])

                <div class="flex items-center justify-between">
                    <button type="submit" class="btn-primary">
                        Сохранить черновик
                    </button>
                    <a href="{{ route('purchases.index') }}" class="text-slate-600 hover:text-slate-800 font-medium">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
