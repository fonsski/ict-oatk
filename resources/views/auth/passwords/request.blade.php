@extends('layouts.app')

@section('title', 'Сброс пароля - ICT Help')

@section('content')
<div class="auth-wrap">
    <div class="auth-box">
        <h1 class="page-title">Сброс пароля</h1>
        <p class="auth-lead">Введите email, мы отправим код для сброса пароля.</p>

        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.send') }}" class="auth-form">
            @csrf

            <div>
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                       class="form-input @error('email') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                       placeholder="Введите ваш email">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full">Отправить код сброса</button>
        </form>

        <p class="auth-alt">
            Вспомнили пароль?
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline underline-offset-2">Войти</a>
        </p>
    </div>

    @include('auth.partials.aside')
</div>
@endsection
