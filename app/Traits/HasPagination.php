<?php

namespace App\Traits;

use App\Services\PaginationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasPagination
{
    protected PaginationService $paginationService;

    
     * Инициализация сервиса пагинации

    protected function initPaginationService(): void
    {
        if (!isset($this->paginationService)) {
            $this->paginationService = app(PaginationService::class);
        }
    }

    
     * Применить пагинацию к запросу

    protected function paginateQuery(Builder $query, Request $request, string $type = 'default'): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->initPaginationService();
        
        $params = $this->paginationService->getPaginationParams($request, $type);
        
        return $query->paginate($params['per_page'], ['*'], 'page', $params['page'])
                    ->withQueryString();
    }

    
     * Получить размер страницы для типа данных

    protected function getPageSize(string $type, ?int $requestedSize = null): int
    {
        $this->initPaginationService();
        
        return $this->paginationService->getPageSize($type, $requestedSize);
    }

    
     * Создать пагинацию для коллекции

    protected function paginateCollection(\Illuminate\Support\Collection $items, Request $request, string $type = 'default'): \Illuminate\Pagination\LengthAwarePaginator
    {
        $this->initPaginationService();
        
        $params = $this->paginationService->getPaginationParams($request, $type);
        
        return $this->paginationService->paginateCollection(
            $items,
            $params['per_page'],
            $params['page'],
            $request->url()
        );
    }

    
     * Получить информацию о пагинации для представления

    protected function getPaginationInfo($paginator): array
    {
        $this->initPaginationService();
        
        return $this->paginationService->getPaginationInfo($paginator);
    }

    
     * Получить доступные размеры страниц

    protected function getAvailablePageSizes(): array
    {
        $this->initPaginationService();
        
        return $this->paginationService->getAvailablePageSizes();
    }

    
     * Создать простую пагинацию

    protected function createSimplePagination(\Illuminate\Support\Collection $items, int $perPage, int $currentPage): array
    {
        $this->initPaginationService();
        
        return $this->paginationService->createSimplePagination($items, $perPage, $currentPage);
    }

    
     * Создать расширенную пагинацию с метаданными

    protected function createAdvancedPagination(Builder $query, Request $request, string $type = 'default', array $options = []): array
    {
        $this->initPaginationService();
        
        $params = $this->paginationService->getPaginationParams($request, $type);
        
        return $this->paginationService->createAdvancedPagination(
            $query,
            $params['per_page'],
            $params['page'],
            $options
        );
    }

    
     * Получить параметры пагинации из запроса

    protected function getPaginationParams(Request $request, string $type = 'default'): array
    {
        $this->initPaginationService();
        
        return $this->paginationService->getPaginationParams($request, $type);
    }

    
     * Валидировать параметры пагинации

    protected function validatePaginationParams(array $params): array
    {
        $this->initPaginationService();
        
        return $this->paginationService->validatePaginationParams($params);
    }

    
     * Получить настройки пагинации для пользователя

    protected function getUserPaginationSettings(\App\Models\User $user, string $type = 'default'): array
    {
        $this->initPaginationService();
        
        return $this->paginationService->getUserPaginationSettings($user, $type);
    }
}
