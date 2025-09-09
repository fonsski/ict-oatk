<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class PaginationService
{
    /**
     * Стандартные размеры страниц для разных типов данных
     */
    const PAGE_SIZES = [
        'tickets' => 10,
        'users' => 15,
        'equipment' => 15,
        'rooms' => 15,
        'knowledge_base' => 10,
        'equipment_categories' => 15,
        'drawing_canvas' => 10,
        'network_topology' => 10,
        'homepage_faq' => 15,
        'knowledge_categories' => 10,
        'equipment_service' => 15,
        'default' => 15,
    ];

    /**
     * Максимальный размер страницы
     */
    const MAX_PAGE_SIZE = 100;

    /**
     * Минимальный размер страницы
     */
    const MIN_PAGE_SIZE = 5;

    /**
     * Получить размер страницы для конкретного типа данных
     */
    public function getPageSize(string $type, ?int $requestedSize = null): int
    {
        $defaultSize = self::PAGE_SIZES[$type] ?? self::PAGE_SIZES['default'];
        
        if ($requestedSize === null) {
            return $defaultSize;
        }

        // Ограничиваем размер страницы
        return max(
            self::MIN_PAGE_SIZE,
            min(self::MAX_PAGE_SIZE, $requestedSize)
        );
    }

    /**
     * Создать пагинатор для коллекции
     */
    public function paginateCollection(Collection $items, int $perPage, int $currentPage, string $path = null): LengthAwarePaginator
    {
        $currentPage = max(1, $currentPage);
        $perPage = max(1, $perPage);
        
        $total = $items->count();
        $offset = ($currentPage - 1) * $perPage;
        
        $items = $items->slice($offset, $perPage)->values();
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $path ?: request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Получить параметры пагинации из запроса
     */
    public function getPaginationParams(\Illuminate\Http\Request $request, string $type = 'default'): array
    {
        $page = max(1, (int) $request->get('page', 1));
        $perPage = $this->getPageSize($type, (int) $request->get('per_page'));
        
        return [
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Добавить параметры пагинации к URL
     */
    public function appendPaginationParams(string $url, array $params = []): string
    {
        $query = http_build_query($params);
        return $url . ($query ? '?' . $query : '');
    }

    /**
     * Получить информацию о пагинации для представления
     */
    public function getPaginationInfo($paginator): array
    {
        if (!$paginator instanceof LengthAwarePaginator) {
            return [];
        }

        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
            'has_pages' => $paginator->hasPages(),
        ];
    }

    /**
     * Создать простую пагинацию для небольшого количества элементов
     */
    public function createSimplePagination(Collection $items, int $perPage, int $currentPage): array
    {
        $total = $items->count();
        $totalPages = ceil($total / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = $items->slice($offset, $perPage)->values();
        
        return [
            'items' => $paginatedItems,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'per_page' => $perPage,
            'has_next' => $currentPage < $totalPages,
            'has_prev' => $currentPage > 1,
        ];
    }

    /**
     * Получить доступные размеры страниц для селекта
     */
    public function getAvailablePageSizes(): array
    {
        return [
            5 => '5 на странице',
            10 => '10 на странице',
            15 => '15 на странице',
            25 => '25 на странице',
            50 => '50 на странице',
            100 => '100 на странице',
        ];
    }

    /**
     * Валидировать параметры пагинации
     */
    public function validatePaginationParams(array $params): array
    {
        $validated = [];
        
        if (isset($params['page'])) {
            $validated['page'] = max(1, (int) $params['page']);
        }
        
        if (isset($params['per_page'])) {
            $validated['per_page'] = max(
                self::MIN_PAGE_SIZE,
                min(self::MAX_PAGE_SIZE, (int) $params['per_page'])
            );
        }
        
        return $validated;
    }

    /**
     * Получить настройки пагинации для конкретного пользователя
     */
    public function getUserPaginationSettings(\App\Models\User $user, string $type = 'default'): array
    {
        // В будущем можно сохранять настройки пагинации в профиле пользователя
        // Пока возвращаем стандартные настройки
        return [
            'per_page' => $this->getPageSize($type),
            'show_pagination_info' => true,
            'show_page_sizes' => true,
        ];
    }

    /**
     * Создать пагинацию с дополнительными метаданными
     */
    public function createAdvancedPagination($query, int $perPage, int $currentPage, array $options = []): array
    {
        $paginator = $query->paginate($perPage, ['*'], 'page', $currentPage);
        
        $info = $this->getPaginationInfo($paginator);
        
        return [
            'data' => $paginator->items(),
            'pagination' => $info,
            'meta' => [
                'query_time' => microtime(true) - ($options['start_time'] ?? microtime(true)),
                'memory_usage' => memory_get_usage(true),
                'filters_applied' => $options['filters'] ?? [],
            ],
        ];
    }
}
