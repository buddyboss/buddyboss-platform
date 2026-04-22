<?php

namespace BuddyBossPlatform\Doctrine\Tests\Common\Cache;

use BuddyBossPlatform\Couchbase;
use BuddyBossPlatform\Doctrine\Common\Cache\CacheProvider;
use BuddyBossPlatform\Doctrine\Common\Cache\CouchbaseCache;
use Throwable;
/**
 * @requires extension couchbase >=1.0
 * @requires extension couchbase <2.0
 */
class CouchbaseCacheTest extends CacheTest
{
    /** @var Couchbase */
    private $couchbase;
    protected function setUp() : void
    {
        try {
            $this->couchbase = new Couchbase('127.0.0.1', 'Administrator', 'password', 'default');
        } catch (Throwable $ex) {
            $this->markTestSkipped('Could not instantiate the Couchbase cache because of: ' . $ex);
        }
    }
    protected function getCacheDriver() : CacheProvider
    {
        $driver = new CouchbaseCache();
        $driver->setCouchbase($this->couchbase);
        return $driver;
    }
}
