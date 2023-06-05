<?php
/**
 * BuddyBoss Invites Functions.
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss\Invites\Functions
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns the name of the invite post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string The name of the invite post type.
 */
function bp_get_invite_post_type() {

	/**
	 * Filters the name of the invite post type.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value invite post type name.
	 */
	return apply_filters( 'bp_get_invite_post_type', buddypress()->invite_post_type );
}

/**
 * Return labels used by the invite post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_invite_post_type_labels() {

	/**
	 * Filters invite post type labels.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters(
		'bp_get_invite_post_type_labels',
		array(
			'add_new_item'       => __( 'Send Invite', 'buddyboss' ),
			'all_items'          => __( 'Sent Invites', 'buddyboss' ),
			'edit_item'          => __( 'Edit Sent Invite', 'buddyboss' ),
			'menu_name'          => __( 'Invites', 'buddyboss' ),
			'name'               => __( 'Email Invites', 'buddyboss' ),
			'new_item'           => __( 'New Sent Invite', 'buddyboss' ),
			'not_found'          => __( 'No Sent Invites found', 'buddyboss' ),
			'not_found_in_trash' => __( 'No Sent Invites found in trash', 'buddyboss' ),
			'search_items'       => __( 'Search Sent Invites', 'buddyboss' ),
			'singular_name'      => __( 'Sent Invite', 'buddyboss' ),
		)
	);

}

/**
 * Return array of features that the invite post type supports.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_invite_post_type_supports() {

	/**
	 * Filters the features that the invite post type supports.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Supported features.
	 */
	return apply_filters(
		'bp_get_invite_post_type_supports',
		array(
			'editor',
			'page-attributes',
			'title',
		)
	);
}

/**
 * Check if the current page is 'accept-invitation'?
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_invites_member_invite_invitation_page() {
	$retval = false;

	if ( bp_is_register_page() && ! empty( $_GET['bp-invites'] ) && 'accept-member-invitation' === urldecode( $_GET['bp-invites'] ) ) {
		$retval = true;
	}

	return apply_filters( 'invite_anyone_is_accept_invitation_page', $retval );
}

/**
 * Allows invited users to register even if registration is disabled.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_member_invite_remove_registration_lock() {
	global $bp;

	if ( ! bp_invites_member_invite_invitation_page() ) {
		return;
	}

	if ( false === bp_is_active( 'invites' ) ) {
		return;
	}

	if ( ! isset( $_GET['email'] ) || ! $email = urldecode( $_GET['email'] ) ) {
		return;
	}

	// If the url takes the form register/?bp-invites=accept-member-invitation&email=username+extra%40gmail.com,
	// urldecode returns a space in place of the +. (This is not typical,
	// but we can catch it.)
	$email = str_replace( ' ', '+', $email );

	$args = array(
		'post_type'      => bp_get_invite_post_type(),
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_bp_invitee_email',
				'value'   => $email,
				'compare' => '=',
			),
			array(
				'key'     => '_bp_invitee_status',
				'value'   => 0,
				'compare' => '=',
			),
		),
	);

	$bp_get_invitee_email = new WP_Query( $args );

	if ( ! $bp_get_invitee_email->have_posts() ) {
		bp_core_add_message( __( "We couldn't find any invitations associated with the provided email address.", 'buddyboss' ), 'error' );
		return;
	}

	// To support old versions of BP, we have to force the overloaded
	// site_options property in some cases.
	if ( is_multisite() ) {
		$site_options = $bp->site_options;
		if ( ! empty( $bp->site_options['registration'] ) && 'blog' === $bp->site_options['registration'] ) {
			$site_options['registration'] = 'all';
		} elseif ( ! empty( $bp->site_options['registration'] ) && 'none' === $bp->site_options['registration'] ) {
			$site_options['registration'] = 'user';
		}
		$bp->site_options = $site_options;

		add_filter( 'bp_get_signup_allowed', '__return_true' );
	} else {
		add_filter( 'option_users_can_register', '__return_true' );
	}
}
add_action( 'wp', 'bp_invites_member_invite_remove_registration_lock', 1 );

/**
 * Checks if the email is connected to an active invite, populates the email address
 * and shows the welcome message on the register page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_member_invite_register_screen_message() {
	global $bp;

	if ( ! bp_invites_member_invite_invitation_page() ) {
		return;
	}

	if ( isset( $_GET['email'] ) ) {
		$email = urldecode( $_GET['email'] );
	} else {
		$email = '';
	}

	?>
	<?php if ( empty( $email ) ) : ?>
		<div id="message" class="error"><p><?php _e( "It looks like you're trying to accept an invitation to join the site, but some information is missing. Please try again by clicking on the link in the invitation email.", 'buddyboss' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $bp->signup->step == 'request-details' && ! empty( $email ) ) : ?>

		<?php do_action( 'accept_email_invite_before' ); ?>

		<script>
			jQuery(document).ready( function() {
				jQuery("input#signup_email").val("<?php echo esc_js( str_replace( ' ', '+', $email ) ); ?>");
			});
		</script>


		<?php
		$bp_get_invitee_email = bp_invites_member_invite_get_invitations_by_invited_email( $email );

		$inviters = array();
		if ( $bp_get_invitee_email->have_posts() ) {
			while ( $bp_get_invitee_email->have_posts() ) {
				$bp_get_invitee_email->the_post();
				$inviters[] = get_the_author_meta( 'ID' );
			}
		}
		$inviters = array_unique( $inviters );

		$inviters_names = array();
		foreach ( $inviters as $inviter ) {
			$inviters_names[] = bp_core_get_user_displayname( $inviter );
		}

		if ( ! empty( $inviters_names ) ) {
			$message = sprintf( _n( 'Welcome! You\'ve been invited to join the site by the following user: %s. Please fill out the information below to create your account.', 'Welcome! You\'ve been invited to join the site by the following users: %s. Please fill out the information below to create your account.', count( $inviters_names ), 'buddyboss' ), implode( ', ', $inviters_names ) );

			echo '<aside class="bp-feedback bp-messages info"><span class="bp-icon" aria-hidden="true"></span><p>' . esc_html( $message ) . '</p></aside>';
		}

		if ( isset( $_GET['inviter'] ) ) {
			$args = array(
				'post_type'      => bp_get_invite_post_type(),
				'posts_per_page' => -1,
				'posts_author'   => base64_decode( $_GET['inviter'] ),
				'meta_query'     => array(
					array(
						'key'     => '_bp_invitee_email',
						'value'   => $email,
						'compare' => '=',
					),
				),
			);

			$bp_get_invitee_email_new = new WP_Query( $args );
			$posts                    = $bp_get_invitee_email_new->posts;
			$post_id                  = $posts[0]->ID;
			$get_invite_profile_type  = get_post_meta( $post_id, '_bp_invitee_member_type', true );
			if ( isset( $get_invite_profile_type ) && '' !== $get_invite_profile_type ) {
				$member_type_post_id = bp_member_type_post_by_type( $get_invite_profile_type );
				?>
				<script>
					jQuery(document).ready(function () {
						if ( jQuery(".field_type_membertypes").length) {
							jQuery(".field_type_membertypes fieldset select").val("<?php echo esc_js( $member_type_post_id ); ?>");
							jQuery(".field_type_membertypes fieldset select").attr('disabled', 'disabled');
						}
					});
				</script>
				<?php
			}
		}
	endif;
	?>
	<?php
}
add_action( 'bp_before_register_page', 'bp_invites_member_invite_register_screen_message' );

/**
 * Get all the invited records for a member by email.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $email
 *
 * @return WP_Query
 */
function bp_invites_member_invite_get_invitations_by_invited_email( $email ) {

	// If the url takes the form register/?bp-invites=accept-member-invitation&email=username+extra%40gmail.com,
	// urldecode returns a space in place of the +. (This is not typical,
	// but we can catch it.)
	$email = str_replace( ' ', '+', $email );

	$args = array(
		'post_type'      => bp_get_invite_post_type(),
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_bp_invitee_email',
				'value'   => $email,
				'compare' => '=',
			),
		),
	);

	$bp_get_invitee_email = new WP_Query( $args );

	return $bp_get_invitee_email;
}

/**
 * Returns the subject of invite email.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_get_member_invitation_subject() {
	global $bp;

	$query = bb_get_member_invitation_query();

	$title = bp_get_member_invites_wildcard_replace( ( $query->posts ? $query->posts[0]->post_title : '' ) );

	return apply_filters( 'bp_get_member_invitation_subject', stripslashes( $title ) );
}

/**
 * Returns the body content of invite email.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_get_member_invitation_message() {
	global $bp;

	$query = bb_get_member_invitation_query();

	$wp_html_emails    = null;
	$is_default_wpmail = null;

	// Has wp_mail() been filtered to send HTML emails?
	if ( is_null( $wp_html_emails ) ) {
		/** This filter is documented in wp-includes/pluggable.php */
		$wp_html_emails = apply_filters( 'wp_mail_content_type', 'text/plain' ) === 'text/html';
	}

	// Since wp_mail() is a pluggable function, has it been re-defined by another plugin?
	if ( is_null( $is_default_wpmail ) ) {
		try {
			$mirror            = new ReflectionFunction( 'wp_mail' );
			$is_default_wpmail = substr( $mirror->getFileName(), -strlen( 'pluggable.php' ) ) === 'pluggable.php';
		} catch ( Exception $e ) {
			$is_default_wpmail = true;
		}
	}

	$must_use_wpmail = apply_filters( 'bp_email_use_wp_mail', $wp_html_emails || ! $is_default_wpmail );

	$text = '';

	if ( ! empty( $query->posts ) ) {
		if ( $must_use_wpmail ) {
			$text = $query->posts[0]->post_excerpt;
		} else {
			$text = $query->posts[0]->post_content;
		}
	}

	return apply_filters( 'bp_get_member_invitation_message', stripslashes( $text ) );
}

/**
 * Get email invite instructions text.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_get_invites_member_invite_url() {

	$invite_link = apply_filters( 'bp_get_invites_member_invite_url', __( 'To accept this invitation, please visit %%ACCEPTURL%%', 'buddyboss' ) );

	return stripslashes( $invite_link );
}

/**
 * Replaces the token, {{ }}, to it's appropriate content dynamically.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $text
 * @param bool $email
 *
 * @return string
 */
function bp_get_member_invites_wildcard_replace( $text, $email = false ) {
	global $bp;

	$inviter_name = bp_core_get_user_displayname( bp_loggedin_user_id() );
	$site_name    = get_bloginfo( 'name' );
	$inviter_url  = bp_loggedin_user_domain();

	$email = urlencode( $email );

	$accept_link = add_query_arg(
		array(
			'bp-invites' => 'accept-member-invitation',
			'email'      => $email,
			'inviter'    => base64_encode( bp_loggedin_user_id() ),
		),
		bp_get_root_domain() . '/' . bp_get_signup_slug() . '/'
	);
	$accept_link = apply_filters( 'bp_member_invitation_accept_url', $accept_link );

	/**
	 * @todo why are we using %% instead of {{ }} or {{{ }}}?
	 * Also, why are we using all caps, also why aren't we using . as separators?
	 */

	$text = str_replace( '{{inviter.name}}', $inviter_name, $text );
	$text = str_replace( '[{{{site.name}}}]', get_bloginfo( 'name' ), $text );
	$text = str_replace( '{{{site.url}}}', site_url(), $text );
	$text = str_replace( '%%INVITERNAME%%', $inviter_name, $text );
	$text = str_replace( '%%INVITERURL%%', $inviter_url, $text );
	// @todo Don't we already have site.name above?
	$text = str_replace( '%%SITENAME%%', $site_name, $text );
	$text = str_replace( '%%ACCEPTURL%%', $accept_link, $text );

	/* Adding single % replacements because lots of people are making the mistake */
	$text = str_replace( '%INVITERNAME%', $inviter_name, $text );
	$text = str_replace( '%INVITERURL%', $inviter_url, $text );
	$text = str_replace( '%SITENAME%', $site_name, $text );
	$text = str_replace( '%ACCEPTURL%', $accept_link, $text );

	return $text;
}

/**
 * Mark the invited user as registered.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $user_id
 * @param $key
 * @param $user
 */
function bp_invites_member_invite_activate_user( $user_id, $key, $user ) {
	global $bp;

	$email = bp_core_get_user_email( $user_id );

	$inviters = array();

	$args = array(
		'post_type'      => bp_get_invite_post_type(),
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_bp_invitee_email',
				'value'   => $email,
				'compare' => '=',
			),
		),
	);

	$bp_get_invitee_email = new WP_Query( $args );

	if ( $bp_get_invitee_email->have_posts() ) {

		// From the posts returned by the query, get a list of unique inviters
		while ( $bp_get_invitee_email->have_posts() ) {
			$bp_get_invitee_email->the_post();

			$inviter_id = get_the_author_meta( 'ID' );
			$inviters[] = $inviter_id;

			// Mark as accepted
			update_post_meta( get_the_ID(), '_bp_invitee_status', 1 );
			update_post_meta( get_the_ID(), '_bp_invitee_registered_date', date( 'Y-m-d H:i:s' ) );

			$member_type = get_post_meta( get_the_ID(), '_bp_invitee_member_type', true );
			if ( isset( $member_type ) && ! empty( $member_type ) ) {
				bp_set_member_type( $user_id, '' );
				bp_set_member_type( $user_id, $member_type );

				$member_type_id                = bp_member_type_post_by_type( $member_type );
				$selected_member_type_wp_roles = get_post_meta( $member_type_id, '_bp_member_type_wp_roles', true );

				if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
					$bp_user = new WP_User( $user_id );
					foreach ( $bp_user->roles as $role ) {
						// Remove role.
						$bp_user->remove_role( $role );
					}
					// Add role.
					$bp_user->add_role( $selected_member_type_wp_roles[0] );
				}
			}

			$post_id = get_the_ID();

			/**
			 * Fires after invitee activate his/her account.
			 *
			 * @param int $user_id    Invitee user id.
			 * @param int $inviter_id Inviter user id.
			 * @param int $post_id 	  Invitation id.
			 *
			 * @since BuddyBoss 1.4.7
			 */
			do_action( 'bp_invites_member_invite_activate_user', $user_id, $inviter_id, $post_id );
		}
	}

}
add_action( 'bp_core_activated_user', 'bp_invites_member_invite_activate_user', 10, 3 );

/**
 * Mark the invited user as registered via custom URL.
 *
 * @since BuddyBoss 1.2.8
 *
 * @param $user_id
 *
 */
function bp_invites_member_invite_mark_register_user( $user_id ) {
	global $bp;

	$allow_custom_registration = bp_allow_custom_registration();
	if ( ! $allow_custom_registration ) {
	    return;
	}

	$email = bp_core_get_user_email( $user_id );

	$inviters = array();

	$args = array(
		'post_type'      => bp_get_invite_post_type(),
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_bp_invitee_email',
				'value'   => $email,
				'compare' => '=',
			),
		),
	);

	$bp_get_invitee_email = new WP_Query( $args );

	if ( $bp_get_invitee_email->have_posts() ) {

		// From the posts returned by the query, get a list of unique inviters
		while ( $bp_get_invitee_email->have_posts() ) {
			$bp_get_invitee_email->the_post();

			$inviter_id = get_the_author_meta( 'ID' );
			$inviters[] = $inviter_id;

			// Mark as accepted
			update_post_meta( get_the_ID(), '_bp_invitee_status', 1 );
			update_post_meta( get_the_ID(), '_bp_invitee_registered_date', date( 'Y-m-d H:i:s' ) );

			$member_type = get_post_meta( get_the_ID(), '_bp_invitee_member_type', true );
			if ( isset( $member_type ) && ! empty( $member_type ) ) {
				bp_set_member_type( $user_id, '' );
				bp_set_member_type( $user_id, $member_type );

				$member_type_id                = bp_member_type_post_by_type( $member_type );
				$selected_member_type_wp_roles = get_post_meta( $member_type_id, '_bp_member_type_wp_roles', true );

				if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
					$bp_user = new WP_User( $user_id );
					foreach ( $bp_user->roles as $role ) {
						// Remove role
						$bp_user->remove_role( $role );
					}
					// Add role
					$bp_user->add_role( $selected_member_type_wp_roles[0] );
				}
			}

			$post_id = get_the_ID();

			/**
			 * Fires after invitee registered.
			 *
			 * @param int $user_id    Invitee user id.
			 * @param int $inviter_id Inviter user id.
			 * @param int $post_id 	  Invitation id.
			 *
			 * @since BuddyBoss 1.4.7
			 */
			do_action( 'bp_invites_member_invite_mark_register_user', $user_id, $inviter_id, $post_id );
		}
	}

}
add_action( 'user_register', 'bp_invites_member_invite_mark_register_user', 10, 1 );

/**
 * Set the font in Send Invites TinyMCE editor to Arial
 *
 * @since BuddyBoss 1.2.9
 *
 * @param $user_id
 *
 */
function bp_nouveau_send_invite_content_css( $mceInit ) {
	$styles = 'body.mce-content-body { font-family: Arial, sans-serif;}';
	if ( isset( $mceInit['content_style'] ) ) {
		$mceInit['content_style'] .= ' ' . $styles . ' ';
	} else {
		$mceInit['content_style'] = $styles . ' ';
	}
	return $mceInit;
}

/**
 * Query to fetch email data for invite member.
 *
 * @since BuddyBoss 1.9.0
 *
 * @return object $query
 */
function bb_get_member_invitation_query() {
	static $cache = null;
	if ( null === $cache ) {
		$term = get_term_by( 'name', 'invites-member-invite', bp_get_email_tax_type() );

		$args = array(
			'post_type' => bp_get_email_post_type(),
			'tax_query' => array(
				array(
					'taxonomy' => bp_get_email_tax_type(),
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		);

		$cache = new WP_Query( $args );
	}

	return apply_filters( 'bb_get_member_invitation_query', $cache );
}
