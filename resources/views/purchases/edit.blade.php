@extends('layouts.app')

@section('title', 'Изменить закупку - ICT Help')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Изменить закупку {{ $purchase->number }}</h1>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('purchases.update', $purchase) }}" method="POST">
                @csrf
                @method('PUT')
                @include('purchases._form', ['consumables' => $consumables, 'categories' => $categories, 'purchase' => $purchase])

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Сохранить
                    </button>
                    <a href="{{ route('purchases.show', $purchase) }}" class="text-gray-600 hover:text-gray-800 font-medium">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
