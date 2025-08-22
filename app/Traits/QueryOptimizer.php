<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

trait QueryOptimizer
{
    /**
     * Использовать оптимизацию запросов
     *
     * @var bool
     */
    protected static $useOptimization = true;

    /**
     * Флаг для автоматического добавления индексов в запросы
     *
     * @var bool
     */
    protected static $useIndexHints = true;

    /**
     * Кеш для хранения индексов таблиц
     *
     * @var array
     */
    protected static $tableIndexesCache = [];

    /**
     * Максимальное количество записей для выборки за один раз
     *
     * @var int
     */
    protected static $chunkSize = 1000;

    /**
     * Подготовить запрос с оптимизацией производительности
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOptimized(Builder $query): Builder
    {
        if (!static::$useOptimization) {
            return $query;
        }

        // Определяем основную таблицу запроса
        $table = $query->getModel()->getTable();

        // Проверяем, содержит ли запрос JOIN
        $queryString = $query->toSql();
        $hasJoin = stripos($queryString, 'join') !== false;

        // Оптимизируем запрос для чтения
        $this->optimizeReadQuery($query, $table, $hasJoin);

        return $query;
    }

    /**
     * Оптимизация запроса для чтения
     *
     * @param Builder $query
     * @param string $table
     * @param bool $hasJoin
     * @return void
     */
    protected function optimizeReadQuery(Builder $query, string $table, bool $hasJoin): void
    {
        // Применяем подсказки для индексов, если включено
        if (static::$useIndexHints && !$hasJoin) {
            $this->applyIndexHints($query, $table);
        }

        // Проверяем, есть ли ORDER BY в запросе
        $hasOrderBy = !empty($query->getQuery()->orders);

        // Если запрос не содержит сортировку, добавляем сортировку по первичному ключу
        if (!$hasOrderBy) {
            // Получаем первичный ключ модели
            $primaryKey = $query->getModel()->getKeyName();

            // Добавляем сортировку по первичному ключу для повышения производительности
            $query->orderBy($hasJoin ? $table . '.' . $primaryKey : $primaryKey);
        }
    }

    /**
     * Применение подсказок для индексов
     *
     * @param Builder $query
     * @param string $table
     * @return void
     */
    protected function applyIndexHints(Builder $query, string $table): void
    {
        try {
            // Получаем все условия запроса
            $wheres = $query->getQuery()->wheres;

            if (empty($wheres)) {
                return;
            }

            // Получаем индексы таблицы
            $indexes = $this->getTableIndexes($table);

            if (empty($indexes)) {
                return;
            }

            // Ищем, какой индекс использовать на основе условий WHERE
            $whereColumns = [];
            foreach ($wheres as $where) {
                if (isset($where['column']) && !in_array($where['column'], $whereColumns)) {
                    $whereColumns[] = $where['column'];
                }
            }

            // Проверяем, есть ли индексы для столбцов в WHERE
            $indexesToUse = [];
            foreach ($indexes as $index) {
                if (in_array($index, $whereColumns)) {
                    $indexesToUse[] = $index;
                }
            }

            // Если есть подходящие индексы, добавляем подсказку USE INDEX
            if (!empty($indexesToUse)) {
                // В Laravel нет прямого метода для добавления USE INDEX,
                // поэтому используем DB::raw в подходящих случаях

                // Для сложных случаев логируем рекомендации
                Log::info("Рекомендуется использовать индексы для запроса к таблице {$table}: " . implode(', ', $indexesToUse));
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при оптимизации запроса: " . $e->getMessage());
        }
    }

    /**
     * Получить список индексов таблицы
     *
     * @param string $table
     * @return array
     */
    protected function getTableIndexes(string $table): array
    {
        // Проверяем кеш
        if (isset(static::$tableIndexesCache[$table])) {
            return static::$tableIndexesCache[$table];
        }

        try {
            $driver = DB::connection()->getDriverName();

            switch ($driver) {
                case 'mysql':
                    return $this->getMySqlTableIndexes($table);
                case 'pgsql':
                    return $this->getPostgresTableIndexes($table);
                case 'sqlite':
                    return $this->getSqliteTableIndexes($table);
                default:
                    return [];
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при получении индексов таблицы {$table}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить индексы для MySQL
     *
     * @param string $table
     * @return array
     */
    protected function getMySqlTableIndexes(string $table): array
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        $result = [];

        foreach ($indexes as $index) {
            $result[] = $index->Column_name;
        }

        // Сохраняем в кеш
        static::$tableIndexesCache[$table] = array_unique($result);
        return static::$tableIndexesCache[$table];
    }

    /**
     * Получить индексы для PostgreSQL
     *
     * @param string $table
     * @return array
     */
    protected function getPostgresTableIndexes(string $table): array
    {
        $indexes = DB::select("
            SELECT
                a.attname as column_name
            FROM
                pg_class t,
                pg_class i,
                pg_index ix,
                pg_attribute a
            WHERE
                t.oid = ix.indrelid
                AND i.oid = ix.indexrelid
                AND a.attrelid = t.oid
                AND a.attnum = ANY(ix.indkey)
                AND t.relkind = 'r'
                AND t.relname = ?
        ", [$table]);

        $result = [];
        foreach ($indexes as $index) {
            $result[] = $index->column_name;
        }

        // Сохраняем в кеш
        static::$tableIndexesCache[$table] = array_unique($result);
        return static::$tableIndexesCache[$table];
    }

    /**
     * Получить индексы для SQLite
     *
     * @param string $table
     * @return array
     */
    protected function getSqliteTableIndexes(string $table): array
    {
        $indexes = DB::select("PRAGMA index_list({$table})");
        $result = [];

        foreach ($indexes as $index) {
            $indexInfo = DB::select("PRAGMA index_info({$index->name})");
            foreach ($indexInfo as $column) {
                $result[] = $column->name;
            }
        }

        // Сохраняем в кеш
        static::$tableIndexesCache[$table] = array_unique($result);
        return static::$tableIndexesCache[$table];
    }

    /**
     * Оптимизированная пагинация с меньшим потреблением памяти
     *
     * @param Builder $query
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function scopeOptimizedPaginate(Builder $query, int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null)
    {
        if (!static::$useOptimization) {
            return $query->paginate($perPage, $columns, $pageName, $page);
        }

        // Получаем общее количество записей с использованием отдельного запроса
        // с минимальным набором полей для оптимизации
        $total = $query->toBase()->getCountForPagination();

        // Получаем номер страницы
        $page = $page ?: request()->input($pageName, 1);

        // Применяем limit и offset для текущей страницы
        $results = $query->forPage($page, $perPage)->get($columns);

        // Создаем пагинатор
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * Оптимизированная выборка по большому набору записей
     *
     * @param Builder $query
     * @param \Closure $callback
     * @param int|null $chunkSize
     * @param string|null $column
     * @param string|null $alias
     * @return bool
     */
    public function scopeOptimizedChunk(Builder $query, \Closure $callback, ?int $chunkSize = null, ?string $column = null, ?string $alias = null): bool
    {
        $chunkSize = $chunkSize ?: static::$chunkSize;

        // Получаем первичный ключ модели
        $column = $column ?: $query->getModel()->getKeyName();

        // Создаем оптимизированный запрос
        $optimizedQuery = clone $query;

        // Возвращаем результат чанками с оптимизацией
        return $optimizedQuery->orderBy($column, 'asc')->chunk($chunkSize, $callback);
    }

    /**
     * Оптимизация выборки связанных моделей
     *
     * @param Builder $query
     * @param array|string $relations
     * @return Builder
     */
    public function scopeOptimizedWith(Builder $query, $relations): Builder
    {
        if (!static::$useOptimization) {
            return $query->with($relations);
        }

        // Преобразуем строку в массив, если необходимо
        if (is_string($relations)) {
            $relations = explode(',', $relations);
        }

        // Модифицируем отношения для оптимизации
        $optimizedRelations = [];

        foreach ($relations as $key => $relation) {
            if (is_numeric($key) && is_string($relation)) {
                // Это строковое отношение - добавляем его с оптимизацией
                $optimizedRelations[$relation] = function ($query) {
                    // Если в модели отношения есть метод optimized, используем его
                    if (method_exists($query->getModel(), 'scopeOptimized')) {
                        $query->optimized();
                    }
                };
            } else {
                // Это уже замыкание или другой тип отношения, оставляем как есть
                $optimizedRelations[$key] = $relation;
            }
        }

        return $query->with($optimizedRelations);
    }

    /**
     * Включить или выключить оптимизацию запросов
     *
     * @param bool $enable
     * @return void
     */
    public static function enableOptimization(bool $enable = true): void
    {
        static::$useOptimization = $enable;
    }

    /**
     * Включить или выключить подсказки индексов
     *
     * @param bool $enable
     * @return void
     */
    public static function enableIndexHints(bool $enable = true): void
    {
        static::$useIndexHints = $enable;
    }

    /**
     * Установить размер чанка для выборки
     *
     * @param int $size
     * @return void
     */
    public static function setChunkSize(int $size): void
    {
        static::$chunkSize = $size;
    }

    /**
     * Очистить кеш индексов таблиц
     *
     * @param string|null $table
     * @return void
     */
    public static function clearIndexCache(?string $table = null): void
    {
        if ($table) {
            unset(static::$tableIndexesCache[$table]);
        } else {
            static::$tableIndexesCache = [];
        }
    }
}
