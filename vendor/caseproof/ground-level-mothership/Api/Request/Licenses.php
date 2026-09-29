<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the licenses API.
 *
 * @link https://licenses.caseproof.com/help/api-reference#licenses
 */
class Licenses extends AbstractResource
{
    /**
     * Create a new license.
     *
     * @param  array $body   The body of the request.
     * @param  array $params Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function create(array $body, array $params = []) : Response
    {
        return $this->request->post('licenses', $body, $params);
    }
    /**
     * Get all licenses.
     *
     * @param  array $params Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function list(array $params = []) : Response
    {
        return $this->request->get('licenses', $params);
    }
    /**
     * Get a license by license key.
     *
     * @param string $licenseKey The license key.
     * @param array  $params     Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function get(string $licenseKey, array $params = []) : Response
    {
        return $this->request->get('licenses/' . $licenseKey, $params);
    }
    /**
     * Update a license by license key.
     *
     * @param  string $licenseKey The license key.
     * @param  array  $body       The body of the request.
     * @param  array  $params     Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function update(string $licenseKey, array $body, array $params = []) : Response
    {
        return $this->request->patch('licenses/' . $licenseKey, $body, $params);
    }
    /**
     * Add or remove additional activations for a license.
     *
     * @param  string      $licenseKey The license key.
     * @param  integer     $change     The number of additional activations to add or remove. Accepts negative values.
     * @param  string|null $reference  An optional external reference ID, such as a subscription ID.
     * @param  array       $params     Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function updateAdditionalActivations(string $licenseKey, int $change, ?string $reference = null, array $params = []) : Response
    {
        $body = ['change' => $change];
        if (null !== $reference) {
            $body['reference'] = $reference;
        }
        return $this->request->post('licenses/' . $licenseKey . '/additional-activations', $body, $params);
    }
}
