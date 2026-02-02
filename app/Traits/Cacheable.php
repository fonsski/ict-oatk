<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait Cacheable
{
    
     * Префикс для ключей кэша данной модели
     *
     * @var string|null

    protected static $cachePrefix = null;

    
     * Время жизни кэша в минутах
     *
     * @var int

    protected static $cacheLifetime = 60;

    
     * Флаг, указывающий использовать ли кэширование
     *
     * @var bool

    protected static $cacheEnabled = true;

    
     * Получить префикс ключа кэша для модели
     *
     * @return string

    public static function getCachePrefix(): string
    {
        if (static::$cachePrefix) {
            return static::$cachePrefix;
        }

        return strtolower(str_replace('\\', '_', static::class));
    }

    
     * Получить время жизни кэша
     *
     * @return int

    public static function getCacheLifetime(): int
    {
        
        $modelName = Str::snake(class_basename(static::class));
        $configLifetime = config("optimizer.cacheSettings.models.{$modelName}");

        if ($configLifetime) {
            return (int) $configLifetime;
        }

        return static::$cacheLifetime;
    }

    
     * Построить ключ кэша
     *
     * @param string $key
     * @return string

    public static function buildCacheKey(string $key): string
    {
        return static::getCachePrefix() . ':' . $key;
    }

    
     * Проверить, включено ли кэширование
     *
     * @return bool

    public static function isCacheEnabled(): bool
    {
        
        $globalCacheEnabled = config('optimizer.enableCaching', true);

        
        $excludedModels = config('optimizer.exclude.models', []);
        $modelName = class_basename(static::class);

        if (in_array($modelName, $excludedModels)) {
            return false;
        }

        return $globalCacheEnabled && static::$cacheEnabled;
    }

    
     * Получить модель по ID с использованием кэша
     *
     * @param int|string $id
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|null

    public static function findCached($id, array $columns = ['*'])
    {
        if (!static::isCacheEnabled()) {
            return static::find($id, $columns);
        }

        $cacheKey = static::buildCacheKey("find_{$id}_" . md5(serialize($columns)));

        return Cache::remember(
            $cacheKey,
            static::getCacheLifetime() * 60,
            function () use ($id, $columns) {
                return static::find($id, $columns);
            }
        );
    }

    
     * Получить все модели с использованием кэша
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection

    public static function allCached(array $columns = ['*'])
    {
        if (!static::isCacheEnabled()) {
            return static::all($columns);
        }

        $cacheKey = static::buildCacheKey('all_' . md5(serialize($columns)));

        return Cache::remember(
            $cacheKey,
            static::getCacheLifetime() * 60,
            function () use ($columns) {
                return static::all($columns);
            }
        );
    }

    
     * Выполнить запрос с использованием кэша
     *
     * @param \Closure $callback
     * @param string $keyName
     * @param int|null $lifetime
     * @return mixed

    public static function cached(\Closure $callback, string $keyName, ?int $lifetime = null)
    {
        if (!static::isCacheEnabled()) {
            return $callback();
        }

        $cacheKey = static::buildCacheKey($keyName);

        return Cache::remember(
            $cacheKey,
            ($lifetime ?? static::getCacheLifetime()) * 60,
            $callback
        );
    }

    
     * Очистить кэш для конкретного ключа
     *
     * @param string $key
     * @return bool

    public static function forgetCache(string $key): bool
    {
        return Cache::forget(static::buildCacheKey($key));
    }

    
     * Очистить весь кэш, связанный с моделью
     *
     * @return bool

    public static function flushCache(): bool
    {
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys(static::getCachePrefix() . ':*');

            if (!empty($keys)) {
                return $redis->del($keys) > 0;
            }
        } elseif ($cacheDriver === 'memcached') {
            
            
            return Cache::flush();
        } else {
            
            return Cache::flush();
        }

        return false;
    }

    
     * Boot the trait
     *
     * @return void

    protected static function bootCacheable()
    {
        if (static::isCacheEnabled() && config('optimizer.autoInvalidation', true)) {
            
            static::saved(function ($model) {
                static::flushCache();
            });

            static::deleted(function ($model) {
                static::flushCache();
            });
        }
    }

    
     * Scope для кеширования запроса
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $key
     * @param int|null $lifetime
     * @return \Illuminate\Database\Eloquent\Builder

    public function scopeCached(Builder $query, string $key, ?int $lifetime = null)
    {
        if (!static::isCacheEnabled()) {
            return $query;
        }

        $cacheKey = static::buildCacheKey($key);

        
        $query->macro('getCached', function () use ($query, $cacheKey, $lifetime) {
            return Cache::remember(
                $cacheKey,
                ($lifetime ?? static::getCacheLifetime()) * 60,
                function () use ($query) {
                    return $query->get();
                }
            );
        });

        
        $query->macro('firstCached', function () use ($query, $cacheKey, $lifetime) {
            return Cache::remember(
                $cacheKey . ':first',
                ($lifetime ?? static::getCacheLifetime()) * 60,
                function () use ($query) {
                    return $query->first();
                }
            );
        });

        
        $query->macro('paginateCached', function ($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null) use ($query, $cacheKey, $lifetime) {
            $page = $page ?: request()->input($pageName, 1);
            $paginationKey = "{$cacheKey}:paginate:{$perPage}:{$pageName}:{$page}";

            return Cache::remember(
                $paginationKey,
                ($lifetime ?? static::getCacheLifetime()) * 60,
                function () use ($query, $perPage, $columns, $pageName, $page) {
                    return $query->paginate($perPage, $columns, $pageName, $page);
                }
            );
        });

        return $query;
    }

    
     * Получить кешированную модель или создать новую
     *
     * @param array $attributes
     * @param array $values
     * @return \Illuminate\Database\Eloquent\Model

    public static function firstOrCreateCached(array $attributes, array $values = [])
    {
        if (!static::isCacheEnabled()) {
            return static::firstOrCreate($attributes, $values);
        }

        $cacheKey = static::buildCacheKey('firstOrCreate_' . md5(serialize($attributes)));

        $model = Cache::get($cacheKey);

        if (!$model) {
            $model = static::firstOrCreate($attributes, $values);
            Cache::put($cacheKey, $model, static::getCacheLifetime() * 60);
        }

        return $model;
    }
}
