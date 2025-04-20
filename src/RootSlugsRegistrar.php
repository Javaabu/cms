<?php

namespace Javaabu\Cms;

use Exception;
use DateInterval;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Cache\Repository;
use Javaabu\Cms\Models\PostType;
use function array_key_exists;

class RootSlugsRegistrar
{
    /** @var DateInterval|int */
    public static $cache_expiration_time;
    /** @var string */
    public static $cache_key;
    /** @var Repository */
    protected $cache;
    /** @var CacheManager */
    protected $cache_manager;
    /** @var Collection */
    protected $slugs;

    /**
     * PermissionRegistrar constructor.
     *
     * @param CacheManager $cache_manager
     */
    public function __construct(CacheManager $cache_manager)
    {
        $this->cache_manager = $cache_manager;

        $this->initializeCache();
    }

    /**
     * Initialize the languages cache
     */
    protected function initializeCache()
    {
        self::$cache_expiration_time = config('rootslugs.cache.expiration_time');

        if (app()->version() <= '5.5') {
            if (self::$cache_expiration_time instanceof DateInterval) {
                $interval = self::$cache_expiration_time;
                self::$cache_expiration_time = $interval->m * 30 * 60 * 24 + $interval->d * 60 * 24 + $interval->h * 60 + $interval->i;
            }
        }

        self::$cache_key = config('rootslugs.cache.key');

        $this->cache = $this->getCacheStoreFromConfig();
    }

    /**
     * Get the cache store driver
     *
     * @return Repository
     */
    protected function getCacheStoreFromConfig(): Repository
    {
        // the 'default' fallback here is from the translation.php config file, where 'default' means to use config(cache.default)
        $cache_driver = config('rootslugs.cache.store', 'default');

        // when 'default' is specified, no action is required since we already have the default instance
        if ($cache_driver === 'default') {
            return $this->cache_manager->store();
        }

        // if an undefined cache store is specified, fallback to 'array' which is Laravel's closest equiv to 'none'
        if (! array_key_exists($cache_driver, config('cache.stores'))) {
            $cache_driver = 'array';
        }

        return $this->cache_manager->store($cache_driver);
    }

    /**
     * Flush the cache.
     */
    public function forgetCachedRootSlugs()
    {
        $this->slugs = null;
        $this->cache->forget(self::$cache_key);
    }

    /**
     * Get the root slugs based on the passed params.
     *
     * @return array
     */
    public function getSlugs()
    {
        if ($this->slugs === null) {
            $this->slugs = $this->cache->remember(self::$cache_key, self::$cache_expiration_time, function () {

                try {
                    $slugs = [
                        'post_type' => PostType::query()->whereJsonDoesntContain('features', ['root-page' => true])->get(),
                    ];
                } catch (Exception $e) {
                    return null;
                }

                return $slugs;
            });
        }

        return $this->slugs;
    }

    /**
     * Get the instance of the Cache Store.
     *
     * @return Store
     */
    public function getCacheStore(): Store
    {
        return $this->cache->getStore();
    }
}
