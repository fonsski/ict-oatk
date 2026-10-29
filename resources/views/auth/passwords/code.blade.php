@extends('layouts.app')

@section('title', 'Подтверждение кода - ICT Help')

@section('content')
<div class="auth-wrap">
    <div class="auth-box">
        <h1 class="page-title">Подтверждение кода</h1>
        <p class="auth-lead">Введите код, отправленный на ваш телефон.</p>

        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.code.check') }}" class="auth-form">
            @csrf

            <div>
                <label for="code" class="form-label">Код подтверждения</label>
                <input type="text" name="code" id="code" required autofocus inputmode="numeric" pattern="[0-9]*" minlength="6" maxlength="6"
                       class="form-input @error('code') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                       placeholder="Введите 6-значный код">
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full">Подтвердить код</button>
        </form>

        <p class="auth-alt">
            Не получили код?
            <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline underline-offset-2">Отправить повторно</a>
        </p>
    </div>

    @include('auth.partials.aside')
</div>
@endsection
