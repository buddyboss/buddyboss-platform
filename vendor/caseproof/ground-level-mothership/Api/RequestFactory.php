<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api;

use InvalidArgumentException;
/**
 * Factory for making {@see \GroundLevel\Mothership\Api\Request} calls.
 *
 * @method Response delete(string $endpoint, array $args = [])
 * @method Response get(string $endpoint, array $args = [])
 * @method Response patch(string $endpoint, array $args = [])
 * @method Response post(string $endpoint, array $args = [])
 * @method Response put(string $endpoint, array $args = [])
 */
class RequestFactory
{
    /**
     * Executes a {@see \GroundLevel\Mothership\Api\Request}.
     *
     * @param  string $method The request method to execute. One of get, post, patch, put, or delete.
     * @param  array  $args   The arguments to pass to the request.
     * @return Response The response from the request.
     * @throws \InvalidArgumentException If the method is not a valid request method.
     */
    public function __call(string $method, array $args) : Response
    {
        $method = \strtolower($method);
        $validMethods = ['delete', 'get', 'patch', 'post', 'put'];
        if (!\in_array($method, $validMethods, \true)) {
            throw new InvalidArgumentException(\sprintf('Invalid request method "%1$s", must be one of %2$s', $method, \implode('|', $validMethods)));
        }
        return Request::$method(...$args);
    }
}
