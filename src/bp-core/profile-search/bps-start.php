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

require 'bps-admin.php';
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

add_action( 'init', 'bp_profile_search_register_post_type' );
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
		'show_ui'         => true,
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

/******* post.php, post-new.php */

add_filter( 'post_updated_messages', 'bp_profile_search_form_updated_messages' );
/**
 * Returns BuddyBoss Profile Search message after form update.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_form_updated_messages( $messages ) {
	$messages['bp_ps_form'] = array(
		0  => 'message 0',
		1  => __( 'Form updated.', 'buddyboss' ),
		2  => 'message 2',
		3  => 'message 3',
		4  => 'message 4',
		5  => 'message 5',
		6  => __( 'Form created.', 'buddyboss' ),
		7  => 'message 7',
		8  => 'message 8',
		9  => 'message 9',
		10 => 'message 10',
	);
	return $messages;
}

/**
 * Check if we are on the BuddyBoss Profile Search screen.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_screen() {
	global $current_screen;
	return isset( $current_screen->post_type ) && $current_screen->post_type == 'bp_ps_form';
}

add_action( 'admin_head', 'bp_profile_search_admin_head' );
/**
 * Output BuddyBoss Profile Search admin styling.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_admin_head() {
	global $current_screen;
	if ( ! bp_profile_search_screen() ) {
		return;
	}

	if ( $current_screen->id == 'bp_ps_form' ) {
		_bp_profile_search_admin_js();
	}
	?>
	<style>
		.search-box, .actions, .view-switch {display: none;}
		.bulkactions {display: block;}
		#minor-publishing {display: none;}
		.fixed .column-fields {width: 8%;}
		.fixed .column-template {width: 15%;}
		.fixed .column-action {width: 12%;}
		.fixed .column-directory {width: 12%;}
		.fixed .column-widget {width: 12%;}
		.fixed .column-shortcode {width: 15%;}
		.bp_ps_col1 {display: inline-block; width: 2%; cursor: move;}
		.bp_ps_col2 {display: inline-block; width: 20%;}
		.bp_ps_col3 {display: inline-block; width: 16%;}
		.bp_ps_col4 {display: inline-block; width: 32%;}
		.bp_ps_col5 {display: inline-block; width: 16%;}
		a.delete {color: #aa0000;}
		a.delete:hover {color: #ef3e46;}
	</style>
	<?php
}

/**
 * Enqueue BuddyBoss Profile Search JS.
 *
 * @since BuddyBoss 1.0.0
 */
function _bp_profile_search_admin_js() {
	$translations = array(
		'drag'   => __( 'Drag & drop to reorder fields', 'buddyboss' ),
		'field'  => __( 'Select field', 'buddyboss' ),
		'remove' => __( 'Remove', 'buddyboss' ),
	);
	wp_enqueue_script( 'bp-profile-search-admin', buddypress()->plugin_url . 'bp-core/profile-search/bp-ps-admin.js', array( 'jquery-ui-sortable' ), bp_get_version() );
	wp_localize_script( 'bp-profile-search-admin', 'bp_ps_strings', $translations );
}

/**
 * Returns BuddyBoss Profile Search form ID?
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_main_form() {
	return (int) bp_get_option( 'bp_profile_search_main_form' );
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

/**
 * Added Navigation tab on top of the page BuddyBoss > Group Types
 *
 * @since BuddyBoss 1.0.0
 */
function bp_users_admin_profile_search_listing_add_users_tab() {
	global $pagenow ,$post;

	// Check profile search enabled.
	$is_profile_search_enabled = bp_disable_advanced_profile_search();

	if ( false === $is_profile_search_enabled ) {

		if ( ( isset( $post->post_type ) && $post->post_type == 'bp_ps_form' && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp_ps_form' && $pagenow == 'post-new.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp_ps_form' && $pagenow == 'post.php' ) ) {
			?>
			<div class="wrap">
				<?php
				$users_tab = count( bp_core_get_users_admin_tabs() );
				if ( $users_tab > 1 ) {
					?>
					<h2 class="nav-tab-wrapper"><?php bp_core_admin_users_tabs( __( 'Profile Search', 'buddyboss' ) ); ?></h2>
																				<?php
				}
				?>
			</div>
			<?php
		}
	}
}
add_action( 'admin_notices', 'bp_users_admin_profile_search_listing_add_users_tab' );

add_filter( 'parent_file', 'bp_profile_search_set_platform_tab_submenu_active' );
/**
 * Highlights the submenu item using WordPress native styles.
 *
 * @param string $parent_file The filename of the parent menu.
 *
 * @return string $parent_file The filename of the parent menu.
 */
function bp_profile_search_set_platform_tab_submenu_active( $parent_file ) {
	global $pagenow, $current_screen, $post;

	if ( false === bp_disable_advanced_profile_search() ) {
		if ( ( isset( $post->post_type ) && $post->post_type == 'bp_ps_form' && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp_ps_form' && $pagenow == 'post-new.php' ) || ( isset( $post->post_type ) && $post->post_type == 'bp_ps_form' && $pagenow == 'post.php' ) ) {
			$parent_file = 'buddyboss-platform';
		}
	}
	return $parent_file;
}
