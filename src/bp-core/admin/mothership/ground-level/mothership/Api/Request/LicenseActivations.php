<?php

declare(strict_types=1);

namespace GroundLevel\Mothership\Api\Request;

use GroundLevel\Mothership\Api\Request;
use GroundLevel\Mothership\Api\Response;

/**
 * License Activations API request class.
 */
class LicenseActivations extends Request
{
    /**
     * Activate a license.
     *
     * @param string $product     The product identifier.
     * @param string $licenseKey  The license key.
     * @param string $domain      The activation domain.
     * @return Response The response object.
     */
    public static function activate(string $product, string $licenseKey, string $domain): Response
    {
        $request = new self();
        return $request->post("licenses/{$licenseKey}/activate", [
            'product' => $product,
            'domain' => $domain,
        ]);
    }

    /**
     * Deactivate a license.
     *
     * @param string $licenseKey The license key.
     * @param string $domain     The activation domain.
     * @return Response The response object.
     */
    public static function deactivate(string $licenseKey, string $domain): Response
    {
        $request = new self();
        return $request->patch("licenses/{$licenseKey}/activations/{$domain}/deactivate", [
            'domain' => $domain,
        ]);
    }

    /**
     * Retrieve license activation status.
     *
     * @param string $licenseKey The license key.
     * @param string $domain     The activation domain.
     * @return Response The response object.
     */
    public static function retrieveLicenseActivation(string $licenseKey, string $domain): Response
    {
        $request = new self();
        return $request->get("licenses/{$licenseKey}/activations/{$domain}");
    }
}
