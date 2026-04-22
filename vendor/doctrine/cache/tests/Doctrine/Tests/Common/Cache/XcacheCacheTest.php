<?php

namespace BuddyBossPlatform\Doctrine\Tests\Common\Cache;

use BuddyBossPlatform\Doctrine\Common\Cache\CacheProvider;
use BuddyBossPlatform\Doctrine\Common\Cache\XcacheCache;
/**
 * @requires extension xcache
 */
class XcacheCacheTest extends CacheTest
{
    protected function getCacheDriver() : CacheProvider
    {
        return new XcacheCache();
    }
}
