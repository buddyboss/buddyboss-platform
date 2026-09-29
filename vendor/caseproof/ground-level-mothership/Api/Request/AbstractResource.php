<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
/**
 * Base class for Mothership API resource classes.
 */
abstract class AbstractResource
{
    /**
     * The request instance.
     *
     * @var Request
     */
    protected Request $request;
    /**
     * Constructor.
     *
     * @param Request $request The request instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    /**
     * Returns a copy of this resource with caching disabled for subsequent calls.
     *
     * This is useful for making a one-off request that should be retrieved from the API, bypassing the cache whether
     * or not it is available.
     *
     * @see \GroundLevel\Mothership\Api\Request::fresh()
     *
     * @return static
     */
    public function fresh()
    {
        $clone = clone $this;
        $clone->request = $this->request->fresh();
        return $clone;
    }
    /**
     * Returns a copy of this resource with the cache TTL set to 0 for subsequent calls.
     *
     * This is useful for one-off, per-call requests that should not be stored in the cache.
     *
     * @see \GroundLevel\Mothership\Api\Request::noStore()
     *
     * @return static
     */
    public function noStore()
    {
        return $this->withCacheTtl(0);
    }
    /**
     * Returns a copy of this resource with the given header applied to subsequent calls.
     *
     * @param  string $name  The header name.
     * @param  string $value The header value.
     * @return static A cloned resource carrying the header.
     */
    public function withHeader(string $name, string $value)
    {
        $clone = clone $this;
        $clone->request = $this->request->withHeader($name, $value);
        return $clone;
    }
    /**
     * Returns a copy of this resource with the cache TTL set to the given value for subsequent calls.
     *
     * @see \GroundLevel\Mothership\Api\Request::withCacheTtl()
     *
     * @param  integer $ttl The cache TTL in seconds.
     * @return static
     */
    public function withCacheTtl(int $ttl)
    {
        $clone = clone $this;
        $clone->request = $this->request->withCacheTtl($ttl);
        return $clone;
    }
    /**
     * Returns a copy of this resource with the `X-Proxy-License-Key` header set.
     *
     * @param  string $licenseKey The user's license key to proxy as.
     * @return static A cloned resource carrying the header.
     */
    public function withProxyLicense(string $licenseKey)
    {
        return $this->withHeader('X-Proxy-License-Key', $licenseKey);
    }
}
