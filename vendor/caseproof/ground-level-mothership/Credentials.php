<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
use BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;
/**
 * The Credentials class provides the interface for storing and retrieving credentials
 * based on environment variables, constants, and database (WordPress Database).
 */
class Credentials implements StaticContainerAwareness
{
    use HasStaticContainer;
    /**
     * Get the mothership license key.
     *
     * @return string
     */
    public static function getLicenseKey() : string
    {
        return self::getCredential(MothershipService::LICENSE_KEY_BASENAME);
    }
    /**
     * Get the activation domain.
     *
     * @return string
     */
    public static function getActivationDomain() : string
    {
        $domain = self::getCredential(MothershipService::DOMAIN_BASENAME);
        // No domains provided? Let's set something as the default domain via the $_SERVER data.
        if (!$domain) {
            $domain = $_SERVER['HTTP_HOST'];
        }
        return $domain;
    }
    /**
     * Get the email for the email/token strategy.
     *
     * @return string
     */
    public static function getEmail() : string
    {
        return self::getCredential(MothershipService::EMAIL_BASENAME);
    }
    /**
     * Get the API token for the email/token strategy.
     *
     * @return string
     */
    public static function getApiToken() : string
    {
        return self::getCredential(MothershipService::API_TOKEN_BASENAME);
    }
    /**
     * Store License Key credentials in the database using the plugin's way of storing dbOptions.
     *
     * @param  string $licenseKey The mothership license key.
     * @throws \Exception If the credentials are already stored in environment variables or constants.
     * @return void
     */
    public static function storeLicenseKey(string $licenseKey) : void
    {
        if (self::isCredentialSetInEnvironmentOrConstants(MothershipService::LICENSE_KEY_BASENAME)) {
            throw new \Exception(esc_html__('Cannot store credentials in database; found in environment variables or constants.', 'caseproof-mothership'));
        }
        self::getContainer()->get(AbstractPluginConnection::class)->updateLicenseKey($licenseKey);
    }
    /**
     * Get credentials from environment variables, constants, or database in that order.
     *
     * @param  string $credentialName The base name of the credential to retrieve.
     * @return string
     */
    private static function getCredential(string $credentialName) : string
    {
        $credential = self::isCredentialSetInEnvironmentOrConstants($credentialName);
        if ($credential) {
            return $credential;
        }
        switch ($credentialName) {
            case MothershipService::LICENSE_KEY_BASENAME:
                return (string) self::getContainer()->get(AbstractPluginConnection::class)->getLicenseKey();
            case MothershipService::DOMAIN_BASENAME:
                return (string) self::getContainer()->get(AbstractPluginConnection::class)->getDomain();
            case MothershipService::EMAIL_BASENAME:
                return (string) self::getContainer()->get(AbstractPluginConnection::class)->getEmail();
            case MothershipService::API_TOKEN_BASENAME:
                return (string) self::getContainer()->get(AbstractPluginConnection::class)->getApiToken();
            default:
                return '';
        }
    }
    /**
     * Checks and returns credentials if they are set in environment variables or constants otherwise returns false.
     *
     * The key used for the environment variable and constant is a string that is the concatenation of the
     * {@see \GroundLevel\Mothership\AbstractPluginConnection::pluginPrefix} and the $credentialName parameter.
     *
     * - An underscore is used as a separtor between the two strings
     * - The whole string is converted to uppercase
     * - Any dashes, dots, or spaces are converted to underscores
     *
     * For example, MemberCore uses the "MECO_" prefix, resulting in the following keys: MECO_LICENSE_KEY or MECO_DOMAIN
     *
     * @param  string $credentialName The credential name to check.
     * @return false|string String of credentials if stored in environment variables or constants, otherwise false.
     */
    public static function isCredentialSetInEnvironmentOrConstants(string $credentialName)
    {
        $prefix = self::getContainer()->get(AbstractPluginConnection::class)->pluginPrefix;
        $prefix = '_' === \substr($prefix, -1) ? $prefix : $prefix . '_';
        $constantKey = \strtoupper(\str_replace(['-', '.', ' '], '_', $prefix . $credentialName));
        // Check if $constantKey is an environment variable.
        $envValue = \getenv($constantKey);
        if (\false !== $envValue) {
            return (string) $envValue;
        }
        // Check if $constantKey is a constant.
        if (\defined($constantKey)) {
            return (string) \constant($constantKey);
        }
        return \false;
    }
}
