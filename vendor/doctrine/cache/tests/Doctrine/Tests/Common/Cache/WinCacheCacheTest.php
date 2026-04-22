<?php

namespace BuddyBossPlatform\Doctrine\Tests\Common\Cache;

use BuddyBossPlatform\Doctrine\Common\Cache\CacheProvider;
use BuddyBossPlatform\Doctrine\Common\Cache\WinCacheCache;
/**
 * @requires extension wincache
 */
class WinCacheCacheTest extends CacheTest
{
    protected function getCacheDriver() : CacheProvider
    {
        return new WinCacheCache();
    }
}
