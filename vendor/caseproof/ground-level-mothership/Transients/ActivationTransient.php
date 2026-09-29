<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Transients;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Support\Models\Transient;
/**
 * Activation Transient
 *
 * Stores activation details from the Mothership API as a WordPress transient.
 * All properties are public and fully typed for IDE autocomplete and type safety.
 */
class ActivationTransient extends Transient
{
    /**
     * Constructs a new activation transient.
     *
     * @param AbstractPluginConnection $plugin The plugin connection to get the prefix from.
     */
    public function __construct(AbstractPluginConnection $plugin)
    {
        parent::__construct('activation', DAY_IN_SECONDS, $plugin->pluginId, \true, \false);
    }
    /**
     * Download URL for the licensed product.
     *
     * @var string
     */
    public string $downloadUrl = '';
    /**
     * Version number of the licensed product.
     *
     * @var string
     */
    public string $versionNumber = '';
    /**
     * License key for the product.
     *
     * @var string
     */
    public string $licenseKey = '';
    /**
     * Current status of the license.
     *
     * @var string
     */
    public string $licenseStatus = '';
    /**
     * Expiration date of the license in ISO 8601 format.
     *
     * @var string
     */
    public string $licenseExpiresAt = '';
    /**
     * Unique slug identifier for the product.
     *
     * @var string
     */
    public string $productSlug = '';
    /**
     * Full name of the product.
     *
     * @var string
     */
    public string $productName = '';
    /**
     * Email address of the license holder.
     *
     * @var string
     */
    public string $userEmail = '';
    /**
     * Number of production activations used.
     *
     * @var integer
     */
    public int $prodActivationsUsed = 0;
    /**
     * Number of production activations available.
     *
     * @var integer
     */
    public int $prodActivationsFree = 0;
    /**
     * Total number of production activations allowed.
     *
     * @var integer
     */
    public int $prodActivationsAllowed = 0;
    /**
     * Number of test activations used.
     *
     * @var integer
     */
    public int $testActivationsUsed = 0;
    /**
     * Number of test activations available.
     *
     * @var integer
     */
    public int $testActivationsFree = 0;
    /**
     * Total number of test activations allowed.
     *
     * @var integer
     */
    public int $testActivationsAllowed = 0;
}
