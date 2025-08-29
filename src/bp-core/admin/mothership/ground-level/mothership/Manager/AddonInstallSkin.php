<?php

declare(strict_types=1);

namespace GroundLevel\Mothership\Manager;

/**
 * Custom skin for addon installation.
 */
if (! class_exists( 'WP_Upgrader_Skin' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
}

class AddonInstallSkin extends \WP_Upgrader_Skin
{
    /**
     * Constructor.
     *
     * @param array $args Arguments.
     */
    public function __construct( $args = array() ) {
        parent::__construct( $args );
    }

    /**
     * Empty header function.
     */
    public function header() {}

    /**
     * Empty footer function.
     */
    public function footer() {}

    /**
     * Empty error function.
     *
     * @param string|WP_Error $errors Errors.
     */
    public function error( $errors ) {}

    /**
     * Empty feedback function.
     *
     * @param string $string Feedback string.
     * @param mixed  ...$args Additional arguments.
     */
    public function feedback( $string, ...$args ) {}
}
