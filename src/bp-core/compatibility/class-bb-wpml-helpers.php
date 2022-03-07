<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_WPML_Helpers Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 */
class BB_WPML_Helpers {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_WPML_Helpers constructor.
	 */
	public function __construct() {

		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
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
	 * Register the compatibility hooks for the plugin.
	 */
	public function compatibility_init() {

        add_action( 'wp_loaded', array( $this, 'remove_filter_for_the_content' ) );

	}

    /**
     * Remove the_content filter for WPML. This filter is added inside a class which has no/untraceable instances
     * So we will loop $wp_filters and remove it from there and only for the Group & Member profile page.
     * This filter is added inside this class: WPML_Fix_Links_In_Display_As_Translated_Content and mothod name: fix_fallback_links.
     *
     * @since BuddyBoss [BBVERSION]
     */
    function remove_filter_for_the_content() {
        global $wp_filter;

        if ( bp_is_user() || bp_is_group() ) {
            
            if ( isset( $wp_filter['the_content'] ) && isset( $wp_filter['the_content'][99] ) ) {
                // New filters array.
                $new_filters = array();
                // Loop through 'the_content' filters which has priority 99.
                foreach ( $wp_filter['the_content'][99] as $key => $value ) {
                    // Find the exact filter and remove it from array.
                    if ( 
                        strpos( $key, 'fix_fallback_links' ) !== false && 
                        isset( $value['function'][0] ) && 
                        $value['function'][0] instanceof WPML_Fix_Links_In_Display_As_Translated_Content 
                    ) {
                        continue;   
                    }
                    $new_filters[$key] = $value;
                }
                
                $wp_filter['the_content'][99] = $new_filters;

            }
        }
    }

}

BB_WPML_Helpers::instance();
