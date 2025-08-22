<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CacheService
{
    /**
     * Default cache expiration time in seconds (24 hours)
     */
    protected const DEFAULT_TTL = 86400;

    /**
     * Cache tag prefix for model instances
     */
    protected const MODEL_TAG_PREFIX = 'model.';

    /**
     * Cache tag prefix for collections
     */
    protected const COLLECTION_TAG_PREFIX = 'collection.';

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param string $key
     * @param int|null $ttl
     * @param \Closure $callback
     * @param array|null $tags
     * @return mixed
     */
    public function remember(string $key, ?int $ttl, \Closure $callback, ?array $tags = null)
    {
        $ttl = $ttl ?: self::DEFAULT_TTL;

        if ($tags) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Store a model in the cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param int|null $ttl
     * @return bool
     */
    public function storeModel(Model $model, ?int $ttl = null): bool
    {
        $modelClass = get_class($model);
        $modelId = $model->getKey();
        $key = $this->generateModelCacheKey($modelClass, $modelId);
        $tags = $this->generateModelCacheTags($modelClass);

        return Cache::tags($tags)->put($key, $model, $ttl ?: self::DEFAULT_TTL);
    }

    /**
     * Get a model from the cache.
     *
     * @param string $modelClass
     * @param mixed $modelId
     * @param \Closure|null $callback
     * @param int|null $ttl
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getModel(string $modelClass, $modelId, ?\Closure $callback = null, ?int $ttl = null): ?Model
    {
        $key = $this->generateModelCacheKey($modelClass, $modelId);
        $tags = $this->generateModelCacheTags($modelClass);

        if ($callback) {
            return Cache::tags($tags)->remember($key, $ttl ?: self::DEFAULT_TTL, $callback);
        }

        return Cache::tags($tags)->get($key);
    }

    /**
     * Remove a model from the cache.
     *
     * @param string $modelClass
     * @param mixed $modelId
     * @return bool
     */
    public function forgetModel(string $modelClass, $modelId): bool
    {
        $key = $this->generateModelCacheKey($modelClass, $modelId);
        $tags = $this->generateModelCacheTags($modelClass);

        return Cache::tags($tags)->forget($key);
    }

    /**
     * Store a collection in the cache.
     *
     * @param string $collectionKey
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param string|null $modelClass
     * @param int|null $ttl
     * @return bool
     */
    public function storeCollection(string $collectionKey, Collection $collection, ?string $modelClass = null, ?int $ttl = null): bool
    {
        $key = $this->generateCollectionCacheKey($collectionKey);
        $tags = $this->generateCollectionCacheTags($collectionKey, $modelClass);

        return Cache::tags($tags)->put($key, $collection, $ttl ?: self::DEFAULT_TTL);
    }

    /**
     * Get a collection from the cache.
     *
     * @param string $collectionKey
     * @param \Closure|null $callback
     * @param string|null $modelClass
     * @param int|null $ttl
     * @return \Illuminate\Database\Eloquent\Collection|mixed
     */
    public function getCollection(string $collectionKey, ?\Closure $callback = null, ?string $modelClass = null, ?int $ttl = null)
    {
        $key = $this->generateCollectionCacheKey($collectionKey);
        $tags = $this->generateCollectionCacheTags($collectionKey, $modelClass);

        if ($callback) {
            return Cache::tags($tags)->remember($key, $ttl ?: self::DEFAULT_TTL, $callback);
        }

        return Cache::tags($tags)->get($key);
    }

    /**
     * Remove a collection from the cache.
     *
     * @param string $collectionKey
     * @param string|null $modelClass
     * @return bool
     */
    public function forgetCollection(string $collectionKey, ?string $modelClass = null): bool
    {
        $key = $this->generateCollectionCacheKey($collectionKey);
        $tags = $this->generateCollectionCacheTags($collectionKey, $modelClass);

        return Cache::tags($tags)->forget($key);
    }

    /**
     * Flush all cached models of a given class.
     *
     * @param string $modelClass
     * @return bool
     */
    public function flushModelCache(string $modelClass): bool
    {
        $tag = self::MODEL_TAG_PREFIX . $this->getShortClassName($modelClass);
        return Cache::tags([$tag])->flush();
    }

    /**
     * Flush all cached collections of a given model class.
     *
     * @param string|null $modelClass
     * @return bool
     */
    public function flushCollectionCache(?string $modelClass = null): bool
    {
        $tags = [self::COLLECTION_TAG_PREFIX . '*'];

        if ($modelClass) {
            $tags[] = self::MODEL_TAG_PREFIX . $this->getShortClassName($modelClass);
        }

        return Cache::tags($tags)->flush();
    }

    /**
     * Generate a cache key for a model.
     *
     * @param string $modelClass
     * @param mixed $modelId
     * @return string
     */
    protected function generateModelCacheKey(string $modelClass, $modelId): string
    {
        $shortClassName = $this->getShortClassName($modelClass);
        return self::MODEL_TAG_PREFIX . strtolower($shortClassName) . '.' . $modelId;
    }

    /**
     * Generate cache tags for a model.
     *
     * @param string $modelClass
     * @return array
     */
    protected function generateModelCacheTags(string $modelClass): array
    {
        $shortClassName = $this->getShortClassName($modelClass);
        return [self::MODEL_TAG_PREFIX . $shortClassName];
    }

    /**
     * Generate a cache key for a collection.
     *
     * @param string $collectionKey
     * @return string
     */
    protected function generateCollectionCacheKey(string $collectionKey): string
    {
        return self::COLLECTION_TAG_PREFIX . strtolower(Str::slug($collectionKey));
    }

    /**
     * Generate cache tags for a collection.
     *
     * @param string $collectionKey
     * @param string|null $modelClass
     * @return array
     */
    protected function generateCollectionCacheTags(string $collectionKey, ?string $modelClass = null): array
    {
        $tags = [self::COLLECTION_TAG_PREFIX . Str::slug($collectionKey)];

        if ($modelClass) {
            $tags[] = self::MODEL_TAG_PREFIX . $this->getShortClassName($modelClass);
        }

        return $tags;
    }

    /**
     * Get the short class name from a fully qualified class name.
     *
     * @param string $class
     * @return string
     */
    protected function getShortClassName(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }
}
