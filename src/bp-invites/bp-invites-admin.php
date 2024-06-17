<?php
/**
 * BuddyBoss Invites component admin screen.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss\Invites
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


// Hook for register the invite admin action and filters.
add_action( 'bp_loaded', 'bp_register_invite_type_sections_filters_actions' );

/**
 * Registers the invite admin action and filters.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_invite_type_sections_filters_actions() {

	// add column
	add_filter( 'manage_' . bp_get_invite_post_type() . '_posts_columns', 'bp_invite_add_column' );

	// action for adding a sortable column name.
	add_action( 'manage_' . bp_get_invite_post_type() . '_posts_custom_column', 'bp_invite_show_data', 10, 2 );

	// sortable columns
	add_filter( 'manage_edit-' . bp_get_invite_post_type() . '_sortable_columns', 'bp_invite_add_sortable_columns' );

	// remove bulk actions
	add_filter( 'bulk_actions-edit-' . bp_get_invite_post_type(), 'bp_invites_remove_bulk_actions' );

	add_filter( 'handle_bulk_actions-edit-' . bp_get_invite_post_type(), 'bp_invites_bulk_action_handler', 10, 3 );

	add_action( 'admin_notices', 'bp_invite_bulk_action_notices' );

	add_action( 'admin_footer-edit.php', 'bp_invites_js_bulk_admin_footer' );

	// hide quick edit link on the custom post type list screen
	add_filter( 'post_row_actions', 'bp_invite_hide_quick_edit', 10, 2 );

	// Invites
	add_filter( 'bp_admin_menu_order', 'invites_admin_menu_order', 20 );

	add_filter( 'posts_distinct_request', 'bb_invites_modify_posts_distinct_request', 10, 2 );
	add_filter( 'posts_join_request', 'bb_invites_modify_posts_join_request', 10, 2 );
	add_filter( 'posts_where_request', 'bb_invites_modify_posts_where_request', 10, 2 );

}

/**
 * Add new columns to the post type list screen.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param type $columns
 * @return type
 */
function bp_invite_add_column( $columns ) {

	$columns['inviter']       = __( 'Sender', 'buddyboss' );
	$columns['invitee_name']  = __( 'Recipient Name', 'buddyboss' );
	$columns['invitee_email'] = __( 'Recipient Email', 'buddyboss' );
	$columns['date_invited']  = __( 'Date Invited', 'buddyboss' );
	$columns['status']        = __( 'Status', 'buddyboss' );

	unset( $columns['date'] );
	unset( $columns['title'] );

	return $columns;
}

/**
 * Display data by column and post id.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $column
 * @param $post_id
 */
function bp_invite_show_data( $column, $post_id ) {

	switch ( $column ) {

		case 'inviter':
			$author_id    = get_post_field( 'post_author', $post_id );
			$inviter_link = bp_core_get_user_domain( $author_id );
			$inviter_name = bp_core_get_user_displayname( $author_id );
			printf(
				'<strong>%s<a href="%s">%s</a></strong>',
				get_avatar( $author_id, '32' ),
				esc_url( $inviter_link ),
				$inviter_name
			);

			break;

		case 'invitee_name':
			echo get_post_meta( $post_id, '_bp_invitee_name', true );

			break;

		case 'invitee_email':
			echo get_post_meta( $post_id, '_bp_invitee_email', true );

			break;

		case 'date_invited':
			$date = get_the_date( '', $post_id );
			echo $date;

			break;

		case 'status':
			$title = ( '1' === get_post_meta( $post_id, '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss' ) : __( 'Pending &ndash; Revoke Invite', 'buddyboss' );
			if ( '1' === get_post_meta( $post_id, '_bp_invitee_status', true ) ) {
				printf(
					'%s',
					$title
				);
			} else {
				$allow_custom_registration = bp_allow_custom_registration();
				if ( $allow_custom_registration && '' !== bp_custom_register_page_url() ) {
					echo esc_html( __( 'Invited', 'buddyboss' ) );
				} else {
					$redirect_link = admin_url( 'edit.php?post_type=' . bp_get_invite_post_type() );
					$revoke_link   = bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_invites_slug() . '/revoke-invite-admin/?id=' . $post_id . '&redirect=' . $redirect_link;
					$confirm_title = __( 'Are you sure you want to revoke this invitation?', 'buddyboss' );
					?>
                    <a onclick="return confirm('<?php echo esc_attr( $confirm_title ); ?>')" href="<?php echo esc_url( $revoke_link ); ?>"><?php echo esc_html( $title ); ?></a>
					<?php
                }
			}

			break;
	}

}

/**
 * Sets up a column on admin view on invite post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $columns
 *
 * @return array
 */
function bp_invite_add_sortable_columns( $columns ) {

	$columns['inviter']       = 'inviter';
	$columns['invitee_name']  = 'invitee_name';
	$columns['invitee_email'] = 'invitee_email';
	$columns['date_invited']  = 'date_invited';
	$columns['status']        = 'status';

	return $columns;
}

/**
 * Adds a filter to invite sort items.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invite_add_request_filter() {

	add_filter( 'request', 'bp_invite_sort_items' );

}

/**
 * Sort list of invite post types.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param type $qv
 * @return string
 */
function bp_invite_sort_items( $qv ) {

	if ( ! isset( $qv['post_type'] ) || $qv['post_type'] != bp_get_invite_post_type() ) {
		return $qv;
	}

	if ( ! isset( $qv['orderby'] ) ) {
		return $qv;
	}

	switch ( $qv['orderby'] ) {

		case 'inviter':
			$qv['meta_key'] = '_bp_invites_inviter_name';
			$qv['orderby']  = 'meta_value';

			break;

		case 'invitee_name':
			$qv['meta_key'] = '_bp_invites_invitee_name';
			$qv['orderby']  = 'meta_value';

			break;

		case 'invitee_email':
			$qv['meta_key'] = '_bp_invites_invitee_email';
			$qv['orderby']  = 'meta_value';

			break;

		case 'date_invited':
			$qv['meta_key'] = '_bp_invites_date_invited';
			$qv['orderby']  = 'meta_value_num';

			break;

		case 'status':
			$qv['meta_key'] = '_bp_invites_status';
			$qv['orderby']  = 'meta_value_num';

			break;

	}

	return $qv;
}

/**
 * Hide quick edit link.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param type $actions
 * @param type $post
 * @return type
 */
function bp_invite_hide_quick_edit( $actions, $post ) {

	if ( empty( $post ) ) {
		global $post;
	}

	if ( bp_get_invite_post_type() == $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
	}

	if ( bp_get_invite_post_type() == $post->post_type ) {

		// Sender author id
		$author_id = get_post_field( 'post_author', $post->ID );

		// Build edit links URL.
		$edit_url = admin_url( 'user-edit.php?user_id=' . $author_id );

		// Maybe put in some extra arguments based on the post status.
		$edit_link = add_query_arg( array( 'action' => 'edit' ), $edit_url );

		$inviter_link = bp_core_get_user_domain( $author_id );

		$actions = array(
			'edit' => sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $edit_link ),
				esc_html( __( 'Edit', 'buddyboss' ) )
			),
			'view' => sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $inviter_link ),
				esc_html( __( 'View', 'buddyboss' ) )
			),
		);
	}

	return $actions;
}

/**
 * Remove the bulk actions of bp-invite post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $actions
 *
 * @return mixed
 */
function bp_invites_remove_bulk_actions( $actions ) {

	unset( $actions['edit'] );
	unset( $actions['trash'] );
	$actions['revoke_action'] = 'Revoke Invitations';
	return $actions;
}

/**
 * Add Invites menu item to custom menus array.
 *
 * Several BuddyPress components have top-level menu items in the Dashboard,
 * which all appear together in the middle of the Dashboard menu. This function
 * adds the Invites screen to the array of these menu items.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $custom_menus The list of top-level BP menu items.
 * @return array $custom_menus List of top-level BP menu items, with Invites added.
 */
function invites_admin_menu_order( $custom_menus = array() ) {

	array_push( $custom_menus, 'edit.php?post_type=' . bp_get_invite_post_type() );

	if ( is_network_admin() && bp_is_network_activated() ) {
		array_push(
			$custom_menus,
			get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_invite_post_type() )
		);
	}

	return $custom_menus;
}

/**
 * Bulk revoke invitations.
 *
 * @param $redirect
 * @param $doaction
 * @param $object_ids
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_invites_bulk_action_handler( $redirect, $doaction, $object_ids ) {

	$redirect = remove_query_arg( array( 'revoke_action' ), $redirect );

	if ( 'revoke_action' === $doaction ) {

		foreach ( $object_ids as $post_id ) {

			if ( isset( $post_id ) && '' !== $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		// do not forget to add query args to URL because we will show notices later
		$redirect = add_query_arg(
			'revoke_action',
			count( $object_ids ), // parameter value - how much posts have been affected
			$redirect
		);

	}

	return $redirect;
}

/**
 * Revoke invitation success message.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invite_bulk_action_notices() {

	if ( ! empty( $_REQUEST['revoke_action'] ) ) {

		// depending on ho much posts were changed, make the message different
		printf(
			'<div id="message" class="updated notice is-dismissible"><p>' .
			_n( 'Invite %s has been revoked.', 'Invites of %s has been revoked..', intval( $_REQUEST['revoke_action'] ), 'buddyboss' ) .
			'</p></div>',
			intval( $_REQUEST['revoke_action'] )
		);

	}
}

/**
 * Javascript popup to confirm bulk revoke invitations.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_js_bulk_admin_footer() {

	global $post_type;

	if ( 'bp-invite' === $post_type ) {
		$confirm_title = __( 'Are you sure you want to revoke all selected invitation?', 'buddyboss' );
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				var selector = jQuery( '#doaction' );
				if ( selector.length ) {
					var confirm_message = '<?php echo $confirm_title; ?>';
					selector.click(function () {
						if (!confirm(confirm_message)) {
							return false;
						}
					});
				}
			});
		</script>
		<?php
	}
}

/**
 * Register the Invites component in BuddyBoss > Invites admin screen.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_add_admin_menu() {

	if ( ! is_network_admin() && ! bp_is_network_activated() ) {
		$invites_url = 'edit.php?post_type=' . bp_get_invite_post_type();
		// Add our screen.
		$hook = add_submenu_page(
			'buddyboss-platform',
			__( 'Invites', 'buddyboss' ),
			__( 'Invites', 'buddyboss' ),
			'bp_moderate',
			$invites_url,
			''
		);
	}

}
add_action( bp_core_admin_hook(), 'bp_invites_add_admin_menu', 65 );

function bp_invites_add_sub_menu_page_admin_menu() {

	if ( is_multisite() && bp_is_network_activated() ) {
		$invites_url = 'edit.php?post_type=' . bp_get_invite_post_type(); // buddyboss-settings
		// Add our screen.
		$hook = add_submenu_page( 'buddyboss-platform',
			__( 'Invites', 'buddyboss' ),
			__( 'Invites', 'buddyboss' ),
			'bp_moderate',
			$invites_url,
			'' );
	}
}
add_action( 'admin_menu', 'bp_invites_add_sub_menu_page_admin_menu', 10 );

/**
 * Function to modify the distinct query.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param string   $distinct The DISTINCT clause of the query.
 * @param WP_Query $query    The WP_Query instance (passed by reference).
 *
 * @return string
 */
function bb_invites_modify_posts_distinct_request( $distinct, $query ) {
	global $wpdb;

	if (
		! is_admin() ||
		! $query->is_main_query() ||
		! isset( $query->query ) ||
		! isset( $query->query['post_type'] ) ||
		bp_get_invite_post_type() !== $query->query['post_type']
	) {
		return $distinct;
	}

	$search_term = $query->query_vars['s'];
	if ( ! empty( $search_term ) ) {
		$distinct .= " DISTINCT({$wpdb->posts}.ID) as unique_id, ";
	}

	return $distinct;
}

/**
 * Function to modify the join query.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param string   $join  The JOIN clause of the query.
 * @param WP_Query $query The WP_Query instance (passed by reference).
 *
 * @return string
 */
function bb_invites_modify_posts_join_request( $join, $query ) {
	global $wpdb;

	if (
		! is_admin() ||
		! $query->is_main_query() ||
		! isset( $query->query ) ||
		! isset( $query->query['post_type'] ) ||
		bp_get_invite_post_type() !== $query->query['post_type']
	) {
		return $join;
	}

	$search_term = $query->query_vars['s'];
	if ( ! empty( $search_term ) ) {
		$join .= " INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )";
	}

	return $join;
}

/**
 * Function to modify the join query.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param string   $where The WHERE clause of the query.
 * @param WP_Query $query The WP_Query instance (passed by reference).
 *
 * @return string
 */
function bb_invites_modify_posts_where_request( $where, $query ) {
	global $wpdb;

	if (
		! is_admin() ||
		! $query->is_main_query() ||
		! isset( $query->query ) ||
		! isset( $query->query['post_type'] ) ||
		bp_get_invite_post_type() !== $query->query['post_type']
	) {
		return $where;
	}

	$search_term = $query->query_vars['s'];
	if ( ! empty( $search_term ) ) {

		$invitee_name_meta_query  = $wpdb->prepare( "( {$wpdb->postmeta}.meta_key = '_bp_invitee_name' AND {$wpdb->postmeta}.meta_value LIKE %s )", '%' . $wpdb->esc_like( $search_term ) . '%' );
		$invitee_email_meta_query = $wpdb->prepare( "( {$wpdb->postmeta}.meta_key = '_bp_invitee_email' AND {$wpdb->postmeta}.meta_value LIKE %s )", '%' . $wpdb->esc_like( $search_term ) . '%' );

		$where .= " OR ( $invitee_name_meta_query OR $invitee_email_meta_query )";
	}

	return $where;
}
