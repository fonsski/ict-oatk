<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AppOptimizer
{
    
     * Время хранения кэша по умолчанию (в минутах)

    protected int $defaultCacheLifetime = 60;

    
     * Включить оптимизацию запросов

    protected bool $optimizeQueries = true;

    
     * Включить кеширование

    protected bool $enableCaching = true;

    
     * Активировать кеширование моделей для указанного времени
     *
     * @param Model|string $model Модель или класс модели
     * @param string $key Ключ кэша
     * @param \Closure $callback Функция, возвращающая данные для кеширования
     * @param int|null $minutes Время хранения кэша в минутах (null = использовать значение по умолчанию)
     * @return mixed Кешированные данные

    public function cacheModel($model, string $key, \Closure $callback, ?int $minutes = null): mixed
    {
        if (!$this->enableCaching) {
            return $callback();
        }

        $modelName = is_string($model) ? $model : get_class($model);
        $cacheKey = "model:{$modelName}:{$key}";
        $cacheTime = $minutes ?? $this->defaultCacheLifetime;

        return Cache::remember($cacheKey, $cacheTime * 60, $callback);
    }

    
     * Инвалидация кеша для модели
     *
     * @param Model|string $model Модель или класс модели
     * @param string|null $key Конкретный ключ или null для очистки всего кеша модели

    public function invalidateModelCache($model, ?string $key = null): void
    {
        $modelName = is_string($model) ? $model : get_class($model);

        if ($key === null) {
            
            $cachePattern = "model:{$modelName}:*";
            $this->clearCacheByPattern($cachePattern);
        } else {
            
            $cacheKey = "model:{$modelName}:{$key}";
            Cache::forget($cacheKey);
        }
    }

    
     * Очистка кеша по паттерну (работает только с Redis или Memcached)
     *
     * @param string $pattern Паттерн ключей кеша

    protected function clearCacheByPattern(string $pattern): void
    {
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'redis') {
            
            try {
                $redis = Cache::getRedis();
                $keys = $redis->keys($pattern);

                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } catch (\Exception $e) {
                Log::error("Ошибка при очистке кеша Redis: " . $e->getMessage());
            }
        } elseif ($cacheDriver === 'memcached') {
            
            try {
                $memcached = Cache::getMemcached();
                $allKeys = $memcached->getAllKeys();

                foreach ($allKeys as $key) {
                    if (fnmatch($pattern, $key)) {
                        $memcached->delete($key);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Ошибка при очистке кеша Memcached: " . $e->getMessage());
            }
        } else {
            
            Log::warning("Очистка кеша по паттерну не поддерживается для драйвера {$cacheDriver}. Используйте Redis или Memcached.");
            Cache::flush();
        }
    }

    
     * Оптимизация запроса для пагинации
     *
     * @param Builder $query Запрос
     * @param int $perPage Количество элементов на странице
     * @param array $columns Колонки для выборки
     * @param string $pageName Имя параметра пагинации
     * @param int|null $page Номер страницы
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator

    public function optimizedPaginate(Builder $query, int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null)
    {
        if (!$this->optimizeQueries) {
            return $query->paginate($perPage, $columns, $pageName, $page);
        }

        
        $baseTable = $query->getModel()->getTable();

        
        $queryString = $query->toSql();
        $hasJoin = strpos(strtolower($queryString), 'join') !== false;

        
        if ($hasJoin && $columns[0] === '*') {
            $columns = array_map(function ($column) use ($baseTable) {
                return $baseTable . '.' . $column;
            }, Schema::getColumnListing($baseTable));
        }

        
        $countQuery = clone $query;

        
        $total = $countQuery->toBase()->getCountForPagination();

        
        $page = $page ?: request()->input($pageName, 1);
        $items = $query->forPage($page, $perPage)->get($columns);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]
        );
    }

    
     * Оптимизация выборки связанных моделей
     *
     * @param Builder|Model $query Запрос или модель
     * @param array $relations Массив связей для загрузки
     * @return Builder|Model

    public function optimizeEagerLoading($query, array $relations)
    {
        if (!$this->optimizeQueries) {
            return $query->with($relations);
        }

        
        $optimizedRelations = [];

        foreach ($relations as $relation) {
            
            if (is_string($relation)) {
                $optimizedRelations[$relation] = function ($query) {
                    $this->optimizeQuery($query);
                };
            } else {
                $optimizedRelations[] = $relation;
            }
        }

        return $query->with($optimizedRelations);
    }

    
     * Общая оптимизация запроса
     *
     * @param Builder $query Запрос для оптимизации
     * @return Builder

    public function optimizeQuery(Builder $query): Builder
    {
        if (!$this->optimizeQueries) {
            return $query;
        }

        
        $model = $query->getModel();
        $indexes = $this->getTableIndexes($model->getTable());

        
        $hasOrderBy = !empty($query->getQuery()->orders);

        
        if (!$hasOrderBy && !empty($indexes)) {
            $primaryIndex = $indexes[0] ?? null;
            if ($primaryIndex) {
                $query->orderBy($primaryIndex);
            }
        }

        return $query;
    }

    
     * Получить индексы таблицы
     *
     * @param string $table Имя таблицы
     * @return array Массив имен индексированных полей

    protected function getTableIndexes(string $table): array
    {
        static $tableIndexes = [];

        if (isset($tableIndexes[$table])) {
            return $tableIndexes[$table];
        }

        try {
            
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            $indexFields = [];

            foreach ($indexes as $index) {
                $indexFields[] = $index->Column_name;
            }

            $tableIndexes[$table] = array_unique($indexFields);
            return $tableIndexes[$table];
        } catch (\Exception $e) {
            Log::error("Ошибка при получении индексов таблицы {$table}: " . $e->getMessage());
            return [];
        }
    }

    
     * Кеширование результатов запроса API
     *
     * @param Request $request HTTP-запрос
     * @param string $prefix Префикс для ключа кеша
     * @param \Closure $callback Функция запроса
     * @param int|null $minutes Время кеширования в минутах
     * @return mixed

    public function cacheApiResponse(Request $request, string $prefix, \Closure $callback, ?int $minutes = null): mixed
    {
        if (!$this->enableCaching) {
            return $callback();
        }

        
        $params = $request->all();
        ksort($params); 

        $paramString = !empty($params) ? md5(json_encode($params)) : 'no-params';
        $cacheKey = "api:{$prefix}:{$request->path()}:{$paramString}";
        $cacheTime = $minutes ?? $this->defaultCacheLifetime;

        return Cache::remember($cacheKey, $cacheTime * 60, $callback);
    }

    
     * Инвалидация кеша API
     *
     * @param string $prefix Префикс для очистки

    public function invalidateApiCache(string $prefix): void
    {
        $this->clearCacheByPattern("api:{$prefix}:*");
    }

    
     * Получить настройки оптимизатора
     *
     * @return array

    public function getSettings(): array
    {
        return [
            'optimizeQueries' => $this->optimizeQueries,
            'enableCaching' => $this->enableCaching,
            'defaultCacheLifetime' => $this->defaultCacheLifetime,
        ];
    }

    
     * Установить настройки оптимизатора
     *
     * @param array $settings Массив настроек
     * @return self

    public function setSettings(array $settings): self
    {
        if (isset($settings['optimizeQueries'])) {
            $this->optimizeQueries = (bool) $settings['optimizeQueries'];
        }

        if (isset($settings['enableCaching'])) {
            $this->enableCaching = (bool) $settings['enableCaching'];
        }

        if (isset($settings['defaultCacheLifetime'])) {
            $this->defaultCacheLifetime = (int) $settings['defaultCacheLifetime'];
        }

        return $this;
    }
}
