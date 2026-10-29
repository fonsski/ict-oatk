@extends('layouts.app')

@section('title', 'Новый пароль - ICT Help')

@section('content')
<div class="auth-wrap">
    <div class="auth-box">
        <h1 class="page-title">Новый пароль</h1>
        <p class="auth-lead">Придумайте новый пароль для вашей учётной записи.</p>

        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf

            @error('general')
                <div class="notice notice-error" role="alert">{{ $message }}</div>
            @enderror

            <div>
                <label for="password" class="form-label">Новый пароль</label>
                <input type="password" name="password" id="password" required autocomplete="new-password"
                       class="form-input @error('password') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                       placeholder="Введите новый пароль">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="form-label">Подтверждение пароля</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                       class="form-input"
                       placeholder="Повторите новый пароль">
            </div>

            <button type="submit" class="btn-primary w-full">Сохранить новый пароль</button>
        </form>
    </div>

    @include('auth.partials.aside')
</div>
@endsection
