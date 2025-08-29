<?php

declare(strict_types=1);

namespace GroundLevel\Mothership\Api\Request;

use GroundLevel\Mothership\Api\Request;
use GroundLevel\Mothership\Api\Response;

/**
 * Products API request class.
 */
class Products extends Request
{
    /**
     * List products/add-ons.
     *
     * @param array $args Query arguments.
     * @return Response The response object.
     */
    public static function list(array $args = []): Response
    {
        $request = new self();
        return $request->get('products', $args);
    }
}
