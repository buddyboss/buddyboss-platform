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
		'name'                  => _x( 'Invites Sent by Users', 'invite post type label', 'buddyboss' ),
		'new_item'              => _x( 'New Sent Invite', 'invite post type label', 'buddyboss' ),
		'not_found'             => _x( 'No Sent Invites found', 'invite post type label', 'buddyboss' ),
		'not_found_in_trash'    => _x( 'No Sent Invites found in trash', 'invite post type label', 'buddyboss' ),
		'search_items'          => _x( 'Search Sent Invites', 'invite post type label', 'buddyboss' ),
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
 * Is this the 'accept-invitation' page?
 *
 * @since BuddyBoss 3.1.1
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
 * Function for unlocking the registration if globally registrations disabled.
 *
 * @since BuddyBoss 3.1.1
 *
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
		add_filter( 'option_users_can_register', '__return_true' );
	}
}
add_action( 'wp', 'bp_invites_member_invite_remove_registration_lock', 1 );

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
		<div id="message" class="error"><p><?php _e( "It looks like you're trying to accept an invitation to join the site, but some information is missing. Please try again by clicking on the link in the invitation email.", 'buddyboss' ) ?></p></div>
	<?php endif; ?>

	<?php if ( $bp->signup->step == 'request-details' && ! empty( $email ) ) : ?>

		<?php do_action( 'accept_email_invite_before' ) ?>

		<script type="text/javascript">
			jQuery(document).ready( function() {
				jQuery("input#signup_email").val("<?php echo esc_js( str_replace( ' ', '+', $email ) ) ?>");
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
			$message = sprintf( _n( 'Welcome! You&#8217;ve been invited to join the site by the following user: %s. Please fill out the information below to create your account.', 'Welcome! You&#8217;ve been invited to join the site by the following users: %s. Please fill out the information below to create your account.', count( $inviters_names ), 'buddyboss' ), implode( ', ', $inviters_names ) );
		} else {
			$message = __( 'Welcome! You&#8217;ve been invited to join the site. Please fill out the information below to create your account.', 'buddyboss' );
		}

		echo '<aside class="bp-feedback bp-messages info"><span class="bp-icon" aria-hidden="true"></span><p>' . esc_html( $message ) . '</p></aside>';

		?>

	<?php endif; ?>
	<?php
}
add_action( 'bp_before_register_page', 'bp_invites_member_invite_register_screen_message' );


function bp_invites_member_invite_get_invitations_by_invited_email( $email ) {

	// If the url takes the form register/?bp-invites=accept-member-invitation&email=username+extra%40gmail.com,
	// urldecode returns a space in place of the +. (This is not typical,
	// but we can catch it.)
	$email = str_replace( ' ', '+', $email );

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

	return $bp_get_invitee_email;
}

function bp_get_member_invitation_subject() {
	global $bp;

	$site_name = get_bloginfo('name');

	$text = sprintf( __( 'An invitation to join the %s community.', 'buddyboss' ), $site_name );

	return apply_filters( 'bp_get_member_invitation_subject', stripslashes( $text ) );
}

function bp_get_member_invitation_message() {
	global $bp;

	$blogname = get_bloginfo('name');

	$text = sprintf( __( 'You have been invited by %%INVITERNAME%% to join the %s community.

Visit %%INVITERNAME%%\'s profile at %%INVITERURL%%.', 'buddyboss' ), $blogname ); /* Do not translate the strings embedded in %% ... %% ! */

	$text = bp_get_member_invites_wildcard_replace( $text );


	return apply_filters( 'bp_get_member_invitation_message', stripslashes( $text ) );
}

function bp_get_invites_member_invite_url() {

	$invite_link = apply_filters( 'bp_get_invites_member_invite_url', __( 'To accept this invitation, please visit %%ACCEPTURL%%', 'buddyboss' ) );

	return stripslashes( $invite_link );
}

function bp_get_member_invites_wildcard_replace( $text, $email = false ) {
	global $bp;

	$inviter_name = bp_core_get_user_displayname( bp_loggedin_user_id() );
	$site_name    = get_bloginfo( 'name' );
	$inviter_url  = bp_loggedin_user_domain();

	$email = urlencode( $email );

	$accept_link  = add_query_arg( array(
		'bp-invites' => 'accept-member-invitation',
		'email'    => $email,
	), bp_get_root_domain() . '/' . bp_get_signup_slug() . '/' );
	$accept_link  = apply_filters( 'bp_member_invitation_accept_url', $accept_link );


	$text = str_replace( '%%INVITERNAME%%', $inviter_name, $text );
	$text = str_replace( '%%INVITERURL%%', $inviter_url, $text );
	$text = str_replace( '%%SITENAME%%', $site_name, $text );
	$text = str_replace( '%%ACCEPTURL%%', $accept_link, $text );

	/* Adding single % replacements because lots of people are making the mistake */
	$text = str_replace( '%INVITERNAME%', $inviter_name, $text );
	$text = str_replace( '%INVITERURL%', $inviter_url, $text );
	$text = str_replace( '%SITENAME%', $site_name, $text );
	$text = str_replace( '%ACCEPTURL%', $accept_link, $text );

	return $text;
}

function bp_invites_member_invite_activate_user( $user_id, $key, $user ) {
	global $bp;

	$email = bp_core_get_user_email( $user_id );

	$inviters 	= array();

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

	if ( $bp_get_invitee_email->have_posts() ) {

		// From the posts returned by the query, get a list of unique inviters
		while ( $bp_get_invitee_email->have_posts() ) {
			$bp_get_invitee_email->the_post();

			$inviter_id	= get_the_author_meta( 'ID' );
			$inviters[] 	= $inviter_id;

			// Mark as accepted
			update_post_meta( get_the_ID(), '_bp_invitee_status', 1 );
			update_post_meta( get_the_ID(), '_bp_invitee_registered_date', date( 'Y-m-d H:i:s' ) );
		}

	}

}
add_action( 'bp_core_activated_user', 'bp_invites_member_invite_activate_user', 10, 3 );
