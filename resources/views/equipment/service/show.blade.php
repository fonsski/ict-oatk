@extends('layouts.app')

@section('title', 'Просмотр записи об обслуживании - ICT')

@section('content')
<div class="container-width section-padding">
    <!-- Breadcrumbs -->
    <div class="mb-5">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                        Главная
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('equipment.index') }}" class="ml-1 text-sm text-slate-500 hover:text-slate-700 md:ml-2">Оборудование</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('equipment.show', $equipment) }}" class="ml-1 text-sm text-slate-500 hover:text-slate-700 md:ml-2">{{ $equipment->inventory_number }}</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('equipment.service.index', $equipment) }}" class="ml-1 text-sm text-slate-500 hover:text-slate-700 md:ml-2">История обслуживания</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-sm text-slate-500 md:ml-2">Запись от {{ $service->service_date->format('d.m.Y') }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Запись об обслуживании</h1>
            <p class="text-slate-600">
                {{ $equipment->name ?? 'Оборудование' }}
                <span class="font-semibold">({{ $equipment->inventory_number }})</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2">
            @if(auth()->user()->hasRole(['admin', 'master']))
                <a href="{{ route('equipment.service.edit', [$equipment, $service]) }}" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                    Редактировать
                </a>
                <form action="{{ route('equipment.service.destroy', [$equipment, $service]) }}" method="POST" class="inline" onsubmit="return confirm('Вы уверены, что хотите удалить эту запись?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Удалить
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Service Record Details -->
    <div class="card mb-8">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-xl font-semibold text-slate-900">Информация об обслуживании</h2>
        </div>

        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-8">
                <!-- Дата обслуживания -->
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-slate-500">Дата обслуживания</dt>
                    <dd class="mt-1 text-lg text-slate-900">{{ $service->service_date->format('d.m.Y H:i') }}</dd>
                </div>

                <!-- Тип обслуживания -->
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-slate-500">Тип обслуживания</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $service->service_type_name }}
                        </span>
                    </dd>
                </div>

                <!-- Исполнитель -->
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-slate-500">Исполнитель</dt>
                    <dd class="mt-1 text-lg text-slate-900">{{ $service->performedBy->name ?? 'Неизвестно' }}</dd>
                </div>

                <!-- Результат обслуживания -->
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-slate-500">Результат обслуживания</dt>
                    <dd class="mt-1">
                        @php
                            $resultClasses = [
                                'success' => 'bg-green-100 text-green-800',
                                'partial' => 'bg-yellow-100 text-yellow-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'pending' => 'bg-orange-100 text-orange-800',
                            ];
                            $resultClass = $resultClasses[$service->service_result] ?? 'bg-slate-100 text-slate-800';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $resultClass }}">
                            {{ $service->service_result_name }}
                        </span>
                    </dd>
                </div>

                <!-- Дата следующего обслуживания -->
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-slate-500">Дата следующего обслуживания</dt>
                    <dd class="mt-1 text-lg text-slate-900">
                        {{ $service->next_service_date ? $service->next_service_date->format('d.m.Y') : 'Не указана' }}
                    </dd>
                </div>

                <!-- Описание работ (на всю ширину) -->
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-slate-500">Описание выполненных работ</dt>
                    <dd class="mt-1 text-base text-slate-900 whitespace-pre-line">{{ $service->description }}</dd>
                </div>

                <!-- Обнаруженные проблемы (если есть) -->
                @if($service->problems_found)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">Обнаруженные проблемы</dt>
                        <dd class="mt-1 text-base text-slate-900 whitespace-pre-line">{{ $service->problems_found }}</dd>
                    </div>
                @endif

                <!-- Устраненные проблемы (если есть) -->
                @if($service->problems_fixed)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">Устраненные проблемы</dt>
                        <dd class="mt-1 text-base text-slate-900 whitespace-pre-line">{{ $service->problems_fixed }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Attachments -->
    @if(!empty($service->attachments) && count($service->attachments) > 0)
        <div class="card mb-8">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-xl font-semibold text-slate-900">Прикрепленные файлы</h2>
            </div>

            <div class="p-6">
                <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($service->attachments as $index => $attachment)
                        <li class="col-span-1 flex shadow-sm rounded-md">
                            @php
                                $extension = pathinfo($attachment['original_name'], PATHINFO_EXTENSION);
                                $iconClass = 'text-slate-500';
                                $bgClass = 'bg-slate-100';

                                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                    $iconClass = 'text-blue-500';
                                    $bgClass = 'bg-blue-50';
                                } elseif ($extension === 'pdf') {
                                    $iconClass = 'text-red-500';
                                    $bgClass = 'bg-red-50';
                                } elseif (in_array($extension, ['doc', 'docx'])) {
                                    $iconClass = 'text-blue-700';
                                    $bgClass = 'bg-blue-50';
                                } elseif (in_array($extension, ['xls', 'xlsx'])) {
                                    $iconClass = 'text-green-600';
                                    $bgClass = 'bg-green-50';
                                }
                            @endphp

                            <div class="flex-shrink-0 flex items-center justify-center w-16 {{ $bgClass }} text-white text-sm font-medium rounded-l-md">
                                @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif']))
                                    <svg class="w-8 h-8 {{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                    </svg>
                                @elseif($extension === 'pdf')
                                    <svg class="w-8 h-8 {{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                    </svg>
                                @elseif(in_array($extension, ['doc', 'docx']))
                                    <svg class="w-8 h-8 {{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                    </svg>
                                @elseif(in_array($extension, ['xls', 'xlsx']))
                                    <svg class="w-8 h-8 {{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="w-8 h-8 {{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>

                            <div class="flex-1 flex items-center justify-between border-t border-r border-b border-slate-200 bg-white rounded-r-md truncate">
                                <div class="flex-1 px-4 py-2 text-sm truncate">
                                    <a href="{{ route('equipment.service.attachment', [$equipment, $service, $index]) }}" class="text-slate-900 font-medium hover:text-blue-600">
                                        {{ $attachment['original_name'] }}
                                    </a>
                                    <p class="text-slate-500">
                                        {{ isset($attachment['size']) ? formatBytes($attachment['size']) : '' }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0 pr-2">
                                    <a href="{{ route('equipment.service.attachment', [$equipment, $service, $index]) }}" class="w-8 h-8 inline-flex items-center justify-center text-slate-400 rounded-full bg-transparent hover:text-slate-500 focus:outline-none">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <a href="{{ route('equipment.service.index', $equipment) }}" class="btn-outline w-full sm:w-auto">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Вернуться к истории
        </a>
        <a href="{{ route('equipment.service.create', $equipment) }}" class="btn-primary w-full sm:w-auto">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Добавить новую запись
        </a>
    </div>
</div>
@endsection

@php
function formatBytes($bytes, $precision = 2) {
    $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}
@endphp
