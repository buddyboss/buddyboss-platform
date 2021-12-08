<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_Elementor_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Elementor_Plugin_Compatibility {

    /**
     * The single instance of the class.
     *
     * @var self
     *
     * @since BuddyBoss [BBVERSION]
     */
    private static $instance = null;

    /**
     * BB_Elementor_Plugin_Compatibility constructor.
     * 
     * @since BuddyBoss [BBVERSION]
     */
    public function __construct() {

        $this->compatibility_init();
    }

    /**
     * Get the instance of this class.
     *
     * @since BuddyBoss [BBVERSION]
     *
     * @return Controller|null
     */
    public static function instance() {

        if ( null === self::$instance ) {
            $class_name     = __CLASS__;
            self::$instance = new $class_name();
        }

        return self::$instance;
    }

    /**
     * Register the compatibility hook for the plugin
     * 
     * @since BuddyBoss [BBVERSION]
     *
     * @return void
     */
    public function compatibility_init() {

        add_action( 'bp_core_set_uri_globals', array( $this, 'elementor_library_preview_permalink' ), 10, 2 );

    }

    /**
     * Update the current component and action for elementor saved library preview link.
     * 
     * @since BuddyBoss [BBVERSION]
     *
     * @param object $bp     BuddyPress object.
     * @param array  $bp_uri Array of URI.
     * 
     * @return void
     */
    public function elementor_library_preview_permalink( $bp, $bp_uri ) {

        if ( isset( $_GET['elementor_library'] ) ) {
            $bp->current_component = '';
            $bp->current_action    = '';
        }

    }

}

BB_Elementor_Plugin_Compatibility::instance();
