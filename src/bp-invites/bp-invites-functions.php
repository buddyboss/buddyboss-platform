<?php
/**
 * BuddyBoss Invites Functions.
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss
 * @subpackage InvitesFunctions
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * Returns the name of the invite post type.
 *
 * @since BuddyBoss 3.1.1
 *
 * @return string The name of the invite post type.
 */
function bp_get_invite_post_type() {

	/**
	 * Filters the name of the invite post type.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string $value invite post type name.
	 */
	return apply_filters( 'bp_get_invite_post_type', buddypress()->invite_post_type );
}

/**
 * Return labels used by the invite post type.
 *
 * @since BuddyBoss 3.1.1
 *
 * @return array
 */
function bp_get_invite_post_type_labels() {

	/**
	 * Filters invite post type labels.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters( 'bp_get_invite_post_type_labels', array(
		'add_new_item'          => _x( 'New Sent Invite', 'group type post type label', 'buddyboss' ),
		'all_items'             => _x( 'Sent Invites', 'invite post type label', 'buddyboss' ),
		'edit_item'             => _x( 'Edit Sent Invite', 'invite post type label', 'buddyboss' ),
		'menu_name'             => _x( 'Invites', 'invite post type name', 'buddyboss' ),
		'name'                  => _x( 'Sent Invites', 'invite post type label', 'buddyboss' ),
		'new_item'              => _x( 'New Sent Invite', 'invite post type label', 'buddyboss' ),
		'not_found'             => _x( 'No Sent Invites found', 'invite post type label', 'buddyboss' ),
		'not_found_in_trash'    => _x( 'No Sent Invites found in trash', 'invite post type label', 'buddyboss' ),
		'search_items'          => _x( 'Search Sent Invite', 'invite post type label', 'buddyboss' ),
		'singular_name'         => _x( 'Sent Invite', 'invite post type singular name', 'buddyboss' ),
	) );

}

/**
 * Return array of features that the invite post type supports.
 *
 * @since BuddyBoss 3.1.1
 *
 * @return array
 */
function bp_get_invite_post_type_supports() {

	/**
	 * Filters the features that the invite post type supports.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param array $value Supported features.
	 */
	return apply_filters( 'bp_get_invite_post_type_supports', array(
		'editor',
		'page-attributes',
		'title',
	) );
}

/**
 * Function for registering the email when the invite component active.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_get_invites_register_invite_email_message() {

	if ( bp_is_active( 'invites' ) ) {

		if ( ! function_exists( 'post_exists' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/post.php' );
		}

		// Do not create if it already exists and is not in the trash
		$post_exists = post_exists( '[{{{site.name}}}] New invitation from {{inviter.name}} to join the {{site.name}} community' );

		if ( $post_exists != 0 && get_post_status( $post_exists ) == 'publish' ) {
			return;
		}

		// Create post object
		$my_post = array(
			'post_title' => __( '[{{{site.name}}}] New invitation from {{inviter.name}} to join the {{site.name}} community',
				'buddypress' ),
			'post_content' => __( "<a href=\"{{invitee-register.url}}\">{{inviter.name}}</a> wants to add you to join the {{site.name}} community.\n\n<a href=\"{{invitee-register.url}}\">Click here</a> to join the {{site.name}} community.",
				'buddypress' ),  // HTML email content.
			'post_excerpt' => __( "<a href=\"{{invitee-register.url}}\">{{inviter.name}}</a> wants to add you to join the {{site.name}} community.\n\n<a href=\"{{invitee-register.url}}\">Click here</a> to join the {{site.name}} community.",
				'buddypress' ),  // Plain text email content.
			'post_status' => 'publish',
			'post_type' => bp_get_email_post_type() // this is the post type for emails
		);

		// Insert the email post into the database
		$post_id = wp_insert_post( $my_post );

		if ( $post_id ) {
			// add our email to the taxonomy term 'post_received_comment'
			// Email is a custom post type, therefore use wp_set_object_terms

			$tt_ids = wp_set_object_terms( $post_id, 'member_sent_invitation', bp_get_email_tax_type() );
			foreach ( $tt_ids as $tt_id ) {
				$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
				wp_update_term( (int) $term->term_id,
					bp_get_email_tax_type(),
					array(
						'description' => 'A member sent the registration invitation',
					) );
			}
		}
	}
}
add_action( 'bp_init', 'bp_get_invites_register_invite_email_message' );

/**
 * Function for unlocking the registration if globally registrations disabled.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_invites_member_invite_remove_registration_lock() {
	global $bp;

	if ( false === bp_is_active( 'invites' ) ) {
		return;
	}

	if ( ! isset( $_GET['email'] ) || ! $email = urldecode( $_GET['email'] ) ) {
		return;
	}

	$args = array(
		'post_type'  => bp_get_invite_post_type(),
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key'     => '_bp_invitee_email',
				'value'   => $email,
				'compare' => '=',
			),
		),
	);

	$bp_get_invitee_email = new WP_Query( $args );

	if ( !$bp_get_invitee_email->have_posts() ) {
		bp_core_add_message( __( "We couldn't find any invitations associated with this email address.", 'BuddyBoss' ), 'error' );
		return;
	}

	// To support old versions of BP, we have to force the overloaded
	// site_options property in some cases
	if ( is_multisite() ) {
		$site_options = $bp->site_options;
		if ( !empty( $bp->site_options['registration'] ) && $bp->site_options['registration'] == 'blog' ) {
			$site_options['registration'] = 'all';
		} else if ( !empty( $bp->site_options['registration'] ) && $bp->site_options['registration'] == 'none' ) {
			$site_options['registration'] = 'user';
		}
		$bp->site_options = $site_options;

		add_filter( 'bp_get_signup_allowed', '__return_true' );
	} else {
		add_filter( 'option_users_can_register', create_function( false, 'return true;' ) );
	}
}
//add_action( 'wp', 'bp_invites_member_invite_remove_registration_lock', 1 );
