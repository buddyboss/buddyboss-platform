<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the license activations API.
 *
 * @link https://licenses.caseproof.com/help/api-reference#license-activations
 */
class LicenseActivations extends AbstractResource
{
    /**
     * Activates the license.
     *
     * @param  string $product    The Product to Activate.
     * @param  string $licenseKey The license key to activate.
     * @param  string $domain     The domain to activate the license on.
     * @param  array  $params     Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function activate(string $product, string $licenseKey, string $domain, array $params = []) : Response
    {
        $body = \compact('domain', 'product');
        $endpoint = 'licenses/' . $licenseKey . '/activate';
        return $this->request->post($endpoint, $body, $params);
    }
    /**
     * Deactivate the license.
     *
     * @param  string $licenseKey The license key to deactivate.
     * @param  string $domain     The domain to deactivate the license on.
     * @param  array  $params     Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function deactivate(string $licenseKey, string $domain, array $params = []) : Response
    {
        $endpoint = 'licenses/' . $licenseKey . '/activations/' . \rawurlencode($domain) . '/deactivate';
        return $this->request->patch($endpoint, \compact('domain'), $params);
    }
    /**
     * Retrieve a license activation.
     *
     * @param string $licenseKey The license key to retrieve the activation for.
     * @param string $domain     The domain to retrieve the activation for.
     * @param array  $params     Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function retrieveLicenseActivation(string $licenseKey, string $domain, array $params = []) : Response
    {
        $endpoint = 'licenses/' . $licenseKey . '/activations/' . \rawurlencode($domain);
        return $this->request->get($endpoint, $params);
    }
    /**
     * Retrieve metadata about a license's activations.
     *
     * @param string $licenseKey The license key to retrieve the metadata for.
     * @param array  $params     Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function retrieveLicenseActivationsMeta(string $licenseKey, array $params = []) : Response
    {
        $endpoint = 'licenses/' . $licenseKey . '/activations/meta';
        return $this->request->get($endpoint, $params);
    }
    /**
     * List all activations for a license.
     *
     * @param string $licenseKey The license key to list activations for.
     * @param array  $params     Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function list(string $licenseKey, array $params = []) : Response
    {
        $endpoint = 'licenses/' . $licenseKey . '/activations';
        return $this->request->get($endpoint, $params);
    }
}
