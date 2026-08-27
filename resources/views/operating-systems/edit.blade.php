@extends('layouts.app')

@section('title', 'Изменить операционную систему - ICT Help')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Изменить «{{ $operatingSystem->name }}»</h1>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('operating-systems.update', $operatingSystem) }}" method="POST">
                @csrf
                @method('PUT')
                @include('operating-systems._form', ['operatingSystem' => $operatingSystem])

                <div class="flex items-center justify-between">
                    <button type="submit" class="btn-primary">
                        Сохранить
                    </button>
                    <a href="{{ route('operating-systems.index') }}" class="text-slate-600 hover:text-slate-800 font-medium">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
