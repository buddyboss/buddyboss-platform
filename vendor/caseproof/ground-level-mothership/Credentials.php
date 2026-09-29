<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership;

/**
 * Public read/write API for Mothership credentials.
 *
 * Reads resolve in order: environment variable, PHP constant, plugin storage.
 * Writes skip when env/const supplies the credential, since the database
 * value is unreachable in that case.
 */
class Credentials
{
    /**
     * The basename for the license key used in the License key authentication strategy.
     */
    public const LICENSE_KEY_BASENAME = 'license_key';
    /**
     * The basename for the domain used in the License key authentication strategy.
     */
    public const DOMAIN_BASENAME = 'domain';
    /**
     * The basename for the email used in the Email/Token authentication strategy.
     */
    public const EMAIL_BASENAME = 'email';
    /**
     * The basename for the API token used in the Token authentication strategy.
     */
    public const API_TOKEN_BASENAME = 'api_token';
    /**
     * The plugin connection.
     *
     * @var AbstractPluginConnection
     */
    private AbstractPluginConnection $plugin;
    /**
     * The utility instance.
     *
     * @var Util
     */
    private Util $util;
    /**
     * Constructor.
     *
     * @param AbstractPluginConnection $plugin The plugin connection.
     * @param Util                     $util   The utility instance.
     */
    public function __construct(AbstractPluginConnection $plugin, Util $util)
    {
        $this->plugin = $plugin;
        $this->util = $util;
    }
    /**
     * Get the mothership license key.
     *
     * @return string
     */
    public function getLicenseKey() : string
    {
        return $this->getCredential(self::LICENSE_KEY_BASENAME);
    }
    /**
     * Get the domain.
     *
     * @return string
     */
    public function getDomain() : string
    {
        $domain = $this->getCredential(self::DOMAIN_BASENAME);
        // No domains provided? Let's set something as the default domain via the $_SERVER data.
        if (!$domain && isset($_SERVER['HTTP_HOST'])) {
            $domain = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
        }
        return $domain;
    }
    /**
     * Get the email for the email/token strategy.
     *
     * @return string
     */
    public function getEmail() : string
    {
        return $this->getCredential(self::EMAIL_BASENAME);
    }
    /**
     * Get the API token for the email/token strategy.
     *
     * @return string
     */
    public function getApiToken() : string
    {
        return $this->getCredential(self::API_TOKEN_BASENAME);
    }
    /**
     * Set the license key.
     *
     * @param  string  $licenseKey The license key to store.
     * @param  boolean $silent     See {@see self::setCredential()}.
     * @return boolean
     *
     * @throws \Exception When $silent is false and env/const supplies the credential.
     */
    public function setLicenseKey(string $licenseKey, bool $silent = \true) : bool
    {
        return $this->setCredential(self::LICENSE_KEY_BASENAME, $licenseKey, $silent);
    }
    /**
     * Get credentials from environment variables, constants, or database in that order.
     *
     * @param  string $credentialName The base name of the credential to retrieve.
     * @return string
     */
    private function getCredential(string $credentialName) : string
    {
        $credential = $this->isCredentialSetInEnvironmentOrConstants($credentialName);
        if ($credential) {
            return $credential;
        }
        switch ($credentialName) {
            case self::LICENSE_KEY_BASENAME:
                return (string) $this->plugin->resolveLicenseKey();
            case self::DOMAIN_BASENAME:
                return (string) $this->plugin->resolveDomain();
            case self::EMAIL_BASENAME:
                return (string) $this->plugin->resolveEmail();
            case self::API_TOKEN_BASENAME:
                return (string) $this->plugin->resolveApiToken();
            default:
                return '';
        }
    }
    /**
     * Stores a credential to the database, honoring the env/const policy.
     *
     * When the credential is supplied via environment variable or constant the
     * write is skipped: subsequent reads resolve to the env/constant value via
     * {@see self::getCredential()}, so the database value is irrelevant.
     *
     * @param  string  $credentialName The base name of the credential to store.
     * @param  string  $value          The value to store.
     * @param  boolean $silent         When true, silently skip the write if env/const
     *                                 supplies the credential. When false, throws.
     * @return boolean Whether the underlying store succeeded. Returns false when
     *                 the write was skipped due to env/const override.
     *
     * @throws \Exception      When $silent is false and env/const supplies the credential.
     * @throws \LogicException When no store is configured for the given credential.
     */
    private function setCredential(string $credentialName, string $value, bool $silent) : bool
    {
        if (\false !== $this->isCredentialSetInEnvironmentOrConstants($credentialName)) {
            if (!$silent) {
                throw new \Exception(
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    "Cannot write the {$credentialName} credential: it is supplied via environment variable or constant."
                );
            }
            return \false;
        }
        switch ($credentialName) {
            case self::LICENSE_KEY_BASENAME:
                return $this->plugin->storeLicenseKey($value);
            default:
                throw new \LogicException("No store configured for the {$credentialName} credential.");
        }
    }
    /**
     * Checks and returns credentials if they are set in environment variables or constants otherwise returns false.
     *
     * The key used for the environment variable and constant is the $credentialName prefixed with
     * {@see \GroundLevel\Mothership\Util::composeConstantName()}.
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
    public function isCredentialSetInEnvironmentOrConstants(string $credentialName)
    {
        $constantKey = $this->util->composeConstantName($credentialName);
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
