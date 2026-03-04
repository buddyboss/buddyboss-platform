<?php
/**
 * BuddyBoss Profile Search Loader
 *
 * @package BuddyBoss\Core\ProfileSearch
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

define( 'BP_PS_FORM', 'bp_profile_search' );

// Legacy admin metabox UI removed — now managed via Settings 2.0.
require 'bps-directory.php';
require 'bps-fields.php';
require 'bps-form.php';
require 'bps-search.php';
require 'bps-templates47.php';
require 'bps-xprofile.php';

/**
 * Return BuddyBoss Profile Search meta options.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_meta( $form ) {
	static $options;
	if ( isset( $options[ $form ] ) ) {
		return $options[ $form ];
	}

	$default                = array();
	$default['field_code']  = array();
	$default['field_label'] = array();
	$default['field_desc']  = array();
	$default['field_mode']  = array();
	$default['method']      = 'POST';
	$default['action']      = 0;
	$default['directory']   = 'No';

	$meta             = get_post_meta( $form );
	$options[ $form ] = isset( $meta['bp_ps_options'] ) ? unserialize( $meta['bp_ps_options'][0] ) : $default;

	return $options[ $form ];
}

add_action( 'init', 'bp_pro   hi file_search_register_post_type' );
/**
 * Register BuddyBoss Profile Search post type.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_register_post_type() {
	$args = array(
		'labels'          => array(
			'name'               => __( 'Profile Search Forms', 'buddyboss' ),
			'singular_name'      => __( 'Profile Search Form', 'buddyboss' ),
			'all_items'          => __( 'Profile Search', 'buddyboss' ),
			'add_new'            => __( 'Add New', 'buddyboss' ),
			'add_new_item'       => __( 'Add New Form', 'buddyboss' ),
			'edit_item'          => __( 'Profile Search', 'buddyboss' ),
			'not_found'          => __( 'No forms found', 'buddyboss' ),
			'not_found_in_trash' => __( 'No forms found in trash', 'buddyboss' ),
		),
		'show_ui'         => false,
		'show_in_menu'    => '',
		'supports'        => array( 'title' ),
		'rewrite'         => false,
		'map_meta_cap'    => true,
		'capability_type' => 'bp_ps_form',
		'query_var'       => false,
	);

	register_post_type( 'bp_ps_form', $args );

	$form_caps = array(
		'administrator' => array(
			'edit_bp_ps_forms',
			'edit_others_bp_ps_forms',
			'edit_published_bp_ps_forms',
		),
	);

	$form_caps = apply_filters( 'bp_ps_form_caps', $form_caps );
	foreach ( $form_caps as $key => $caps ) {
		$role = get_role( $key );
		if ( ! empty( $role ) ) {
			foreach ( $caps as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}
}

/**
 * Remove capability to delete BuddyBoss Profile Search form post type.
 *
 * Prevents accidental deletion of search forms via WP-CLI or direct access.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_prevent_delete() {
	global $wp_roles;
	$wp_roles->remove_cap( 'administrator', 'delete_bp_ps_forms' );
	$wp_roles->remove_cap( 'administrator', 'delete_published_bp_ps_forms' );
	$wp_roles->remove_cap( 'editor', 'delete_bp_ps_forms' );
	$wp_roles->remove_cap( 'editor', 'delete_published_bp_ps_forms' );
}
add_action( 'init', 'bp_profile_search_prevent_delete' );

// Legacy admin UI functions removed (form_updated_messages, admin_head CSS,
// admin JS enqueue, admin tab rendering, submenu highlight)
// — Profile Search CPT admin UI is now managed via Settings 2.0.

/**
 * Set a Profile Search option.
 *
 * Stored in the serialized `bp_ps_settings` option.
 * Moved from bps-admin.php — still needed by frontend search code.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $name  Option name.
 * @param mixed  $value Option value.
 */
function bp_ps_set_option( $name, $value ) {
	$settings = get_option( 'bp_ps_settings' );
	if ( false === $settings ) {
		$settings = new stdClass();
	}

	$settings->{$name} = $value;
	update_option( 'bp_ps_settings', $settings );
}

/**
 * Get a Profile Search option.
 *
 * Reads from the serialized `bp_ps_settings` option.
 * Moved from bps-admin.php — still needed by frontend search code.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $name    Option name.
 * @param mixed  $default Default value if not set.
 *
 * @return mixed Option value or default.
 */
function bp_ps_get_option( $name, $default ) {
	$settings = get_option( 'bp_ps_settings' );
	return isset( $settings->{$name} ) ? $settings->{$name} : $default;
}

/**
 * Returns BuddyBoss Profile Search form ID?
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_main_form() {
	/**
	 * Filters the BuddyBoss Profile Search form ID.
	 *
	 * @since BuddyBoss 2.4.60
	 *
	 * @param int $form_id BuddyBoss Profile Search form ID.
	 */
	return (int) apply_filters( 'bp_profile_search_main_form', bp_get_option( 'bp_profile_search_main_form' ) );
}

/**
 * Add BuddyBoss Profile Search form.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_add_main_form() {
	$post_args = array(
		'post_title'  => __( 'Filter Results', 'buddyboss' ),
		'post_type'   => 'bp_ps_form',
		'post_status' => 'publish',
	);

	$post_id = wp_insert_post( $post_args, true );
	if ( ! is_wp_error( $post_id ) ) {
		bp_update_option( 'bp_profile_search_main_form', $post_id );
	}
}

// Legacy admin tab and submenu highlight functions removed — now managed via Settings 2.0.
