@extends('layouts.app')

@section('title', 'Новая закупка - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Новая закупка</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
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
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Сохранить черновик
                    </button>
                    <a href="{{ route('purchases.index') }}" class="text-gray-600 hover:text-gray-800 font-medium">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
