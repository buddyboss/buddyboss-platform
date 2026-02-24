<?php
/**
 * BuddyBoss Groups component admin screen.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss\Groups
 * @since BuddyPress 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// The per_page screen option. Has to be hooked in extremely early.
if ( is_admin() && ! empty( $_REQUEST['page'] ) && 'bp-groups' == $_REQUEST['page'] ) {
	add_filter( 'set-screen-option', 'bp_groups_admin_screen_options', 10, 3 );
}

/**
 * Register the Groups component in BuddyBoss > Groups admin screen.
 *
 * @since BuddyPress 1.7.0
 */
function bp_groups_add_admin_menu() {

	if ( true === bp_disable_group_type_creation() ) {

		// Add our screen.
		$hooks[] = add_submenu_page(
			'buddyboss-platform',
			__( 'Groups', 'buddyboss' ),
			__( 'Groups', 'buddyboss' ),
			'bp_moderate',
			'bp-groups',
			'bp_groups_admin'
		);

	} else {
		// Add our screen.
		$hooks[] = add_submenu_page(
			'buddyboss-platform',
			__( 'Groups', 'buddyboss' ),
			__( 'Groups', 'buddyboss' ),
			'bp_moderate',
			'bp-groups',
			'bp_groups_admin'
		);
	}

	foreach ( $hooks as $hook ) {
		// Hook into early actions to load custom CSS and our init handler.
		add_action( "load-$hook", 'bp_groups_admin_load' );
	}
}
add_action( bp_core_admin_hook(), 'bp_groups_add_admin_menu', 60 );

/**
 * Add groups component to custom menus array.
 *
 * This ensures that the Groups menu item appears in the proper order on the
 * main Dashboard menu.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $custom_menus Array of BP top-level menu items.
 * @return array Menu item array, with Groups added.
 */
function bp_groups_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-groups' );
	return $custom_menus;
}
add_filter( 'bp_admin_menu_order', 'bp_groups_admin_menu_order' );

/**
 * Set up the Groups admin page.
 *
 * Loaded before the page is rendered, this function does all initial setup,
 * including: processing form requests, registering contextual help, and
 * setting up screen options.
 *
 * @since BuddyPress 1.7.0
 *
 * @global BP_Groups_List_Table $bp_groups_list_table Groups screen list table.
 */
function bp_groups_admin_load() {
	global $bp_groups_list_table;

	// Build redirection URL.
	$redirect_to = remove_query_arg( array( 'action', 'action2', 'gid', 'deleted', 'error', 'updated', 'success_new', 'error_new', 'success_modified', 'error_modified' ), $_SERVER['REQUEST_URI'] );

	$doaction = bp_admin_list_table_current_bulk_action();
	$min      = bp_core_get_minified_asset_suffix();

	/**
	 * Fires at top of groups admin page.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $doaction Current $_GET action being performed in admin screen.
	 */
	do_action( 'bp_groups_admin_load', $doaction );

	// Edit screen.
	if ( 'do_delete' == $doaction && ! empty( $_GET['gid'] ) ) {

		check_admin_referer( 'bp-groups-delete' );

		$group_ids = wp_parse_id_list( $_GET['gid'] );

		$gf_ids = isset( $_GET['gfid'] ) ? wp_parse_id_list( $_GET['gfid'] ) : array();

		// Delete groups forums.
		if ( ! empty( $gf_ids ) ) {
			foreach ( $gf_ids as $gf_id ) {
				$forum_ids = function_exists( 'bbp_get_group_forum_ids' ) ? bbp_get_group_forum_ids( $gf_id ) : array();
				foreach ( $forum_ids as $forum_id ) {
					wp_delete_post( $forum_id, true );
				}
			}
		}

		$count = 0;
		foreach ( $group_ids as $group_id ) {
			if ( groups_delete_group( $group_id ) ) {
				$count++;
			}
		}

		$redirect_to = add_query_arg( 'deleted', $count, $redirect_to );

		bp_core_redirect( $redirect_to );

	} elseif ( 'edit' == $doaction && ! empty( $_GET['gid'] ) ) {
		// Columns screen option.
		add_screen_option(
			'layout_columns',
			array(
				'default' => 2,
				'max'     => 2,
			)
		);

		get_current_screen()->add_help_tab(
			array(
				'id'      => 'bp-group-edit-overview',
				'title'   => __( 'Overview', 'buddyboss' ),
				'content' =>
					'<p>' . __( 'This page is a convenient way to edit the details associated with one of your groups.', 'buddyboss' ) . '</p>' .
					'<p>' . __( 'The Name and Description box is fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to hide or unhide, or to choose a 1- or 2-column layout for this screen.', 'buddyboss' ) . '</p>',
			)
		);

		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
		);

		// Register metaboxes for the edit screen.
		add_meta_box( 'submitdiv', __( 'Save', 'buddyboss' ), 'bp_groups_admin_edit_metabox_status', get_current_screen()->id, 'side', 'high' );
		add_meta_box( 'bp_group_settings', __( 'Settings', 'buddyboss' ), 'bp_groups_admin_edit_metabox_settings', get_current_screen()->id, 'side', 'core' );
		add_meta_box( 'bp_group_add_members', __( 'Add New Members', 'buddyboss' ), 'bp_groups_admin_edit_metabox_add_new_members', get_current_screen()->id, 'normal', 'core' );
		add_meta_box( 'bp_group_members', __( 'Manage Members', 'buddyboss' ), 'bp_groups_admin_edit_metabox_members', get_current_screen()->id, 'normal', 'core' );

		if ( bp_enable_group_hierarchies() ) {
			add_meta_box(
				'bp_groups_admin_group_parent',
				__( 'Group Parent', 'buddyboss' ),
				'bp_groups_admin_edit_metabox_group_parent',
				get_current_screen()->id,
				'side',
				'core'
			);
		}

		/**
		 * Fires after the registration of all of the default group meta boxes.
		 *
		 * @since BuddyPress 1.7.0
		 */
		do_action( 'bp_groups_admin_meta_boxes' );

		// Enqueue JavaScript files.
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'dashboard' );

		// Index screen.
	} else {
		// Create the Groups screen list table.
		$bp_groups_list_table = new BP_Groups_List_Table();

		// The per_page screen option.
		add_screen_option( 'per_page', array( 'label' => __( 'Groups', 'buddyboss' ) ) );

		// Help panel - overview text.
		get_current_screen()->add_help_tab(
			array(
				'id'      => 'bp-groups-overview',
				'title'   => __( 'Overview', 'buddyboss' ),
				'content' =>
					'<p>' . __( 'You can manage groups much like you can manage comments and other content. This screen is customizable in the same ways as other management screens, and you can act on groups by using the on-hover action links or the Bulk Actions.', 'buddyboss' ) . '</p>',
			)
		);

		get_current_screen()->add_help_tab(
			array(
				'id'      => 'bp-groups-overview-actions',
				'title'   => __( 'Group Actions', 'buddyboss' ),
				'content' =>
					'<p>' . __( 'Clicking "Visit" will take you to the group\'s public page. Use this link to see what the group looks like on the front end of your site.', 'buddyboss' ) . '</p>' .
					'<p>' . __( 'Clicking "Edit" will take you to a Dashboard panel where you can manage various details about the group, such as its name and description, its members, and other settings.', 'buddyboss' ) . '</p>' .
					'<p>' . __( 'If you click "Delete" under a specific group, or select a number of groups and then choose Delete from the Bulk Actions menu, you will be led to a page where you\'ll be asked to confirm the permanent deletion of the group(s).', 'buddyboss' ) . '</p>',
			)
		);

		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
		);

		// Add accessible hidden heading and text for Groups screen pagination.
		get_current_screen()->set_screen_reader_content(
			array(
				/* translators: accessibility text */
				'heading_pagination' => __( 'Groups list navigation', 'buddyboss' ),
			)
		);
	}

	$bp = buddypress();

	$group_localize_arr = array(
		'add_member_placeholder' => __( 'Start typing a username to add a new member.', 'buddyboss' ),
		'confirm_button'         => __( 'Confirm', 'buddyboss' ),
		'cancel_button'          => __( 'Cancel', 'buddyboss' ),
		'warn_on_leave'          => __( 'If you leave this page, you will lose any unsaved changes you have made to the group.', 'buddyboss' ),
		'warn_on_attach_forum'   => __( 'Members cannot subscribe individually to forums inside a group, only to the group itself. By moving this forum into a group, all existing subscriptions to the forum will be removed.', 'buddyboss' ),
	);

	if ( isset( $_GET['page'], $_GET['gid'] ) && 'bp-groups' === $_GET['page'] && ! empty( $_GET['gid'] ) ) {
		$connected_forum_id  = 0;
		$requested_group_id  = (int) sanitize_text_field( wp_unslash( $_GET['gid'] ) );
		$connected_forum_ids = function_exists( 'bbp_get_group_forum_ids' ) ? bbp_get_group_forum_ids( $requested_group_id ) : array();

		// Get the first forum ID.
		if ( ! empty( $connected_forum_ids ) ) {
			$connected_forum_id = (int) is_array( $connected_forum_ids ) ? $connected_forum_ids[0] : $connected_forum_ids;
		}

		$group_localize_arr['group_connected_forum_id'] = $connected_forum_id;
	}

	// Enqueue CSS and JavaScript.
	wp_enqueue_script( 'bp_groups_admin_js', $bp->plugin_url . "bp-groups/admin/js/admin{$min}.js", array( 'jquery', 'wp-ajax-response', 'jquery-ui-autocomplete' ), bp_get_version(), true );
	wp_localize_script(
		'bp_groups_admin_js',
		'BP_Group_Admin',
		$group_localize_arr
	);
	wp_enqueue_style( 'bp_groups_admin_css', $bp->plugin_url . "bp-groups/admin/css/admin{$min}.css", array(), bp_get_version() );

	wp_style_add_data( 'bp_groups_admin_css', 'rtl', true );
	if ( $min ) {
		wp_style_add_data( 'bp_groups_admin_css', 'suffix', $min );
	}

	if ( $doaction && 'save' === $doaction ) {
		// Get group ID.
		$group_id = isset( $_REQUEST['gid'] ) ? (int) $_REQUEST['gid'] : '';

		$redirect_to = add_query_arg(
			array(
				'gid'    => (int) $group_id,
				'action' => 'edit',
			),
			$redirect_to
		);

		// Check this is a valid form submission.
		check_admin_referer( 'edit-group_' . $group_id );

		// Get the group from the database.
		$group = groups_get_group( $group_id );

		// If the group doesn't exist, just redirect back to the index.
		if ( empty( $group->slug ) ) {
			wp_safe_redirect( $redirect_to );
			exit;
		}

		// Check the form for the updated properties.
		// Store errors.
		$error       = 0;
		$success_new = $error_new = $success_modified = $error_modified = array();

		// Name, description and slug must not be empty.
		if ( empty( $_POST['bp-groups-name'] ) ) {
			$error = $error - 1;
		}

		if ( empty( $_POST['bp-groups-slug'] ) ) {
			$error = $error - 4;
		}

		/*
		 * Group name, slug, and description are handled with
		 * groups_edit_base_group_details().
		 */
		if ( ! $error && ! groups_edit_base_group_details(
			array(
				'group_id'       => $group_id,
				'name'           => $_POST['bp-groups-name'],
				'slug'           => $_POST['bp-groups-slug'],
				'description'    => $_POST['bp-groups-description'],
				'parent_id'      => isset( $_POST['bp-groups-parent'] ) ? $_POST['bp-groups-parent'] : 0,
				'notify_members' => false,
			)
		) ) {
			$error = $group_id;
		}

		// Enable discussion forum.
		$enable_forum = ( isset( $_POST['group-show-forum'] ) ) ? 1 : 0;

		/**
		 * Filters the allowed status values for the group.
		 *
		 * @since BuddyPress 1.0.2
		 *
		 * @param array $value Array of allowed group statuses.
		 */
		$allowed_status = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
		$status         = ( in_array( $_POST['group-status'], (array) $allowed_status ) ) ? $_POST['group-status'] : 'public';

		/**
		 * Filters the allowed invite status values for the group.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param array $value Array of allowed invite statuses.
		 */
		$allowed_invite_status = bb_groups_get_settings_status( 'invite' );
		$invite_status         = in_array( $_POST['group-invite-status'], (array) $allowed_invite_status ) ? $_POST['group-invite-status'] : bb_groups_settings_default_fallback( 'invite', current( $allowed_invite_status ) );

		/**
		 * Filters the allowed activity feed status values for the group.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $value Array of allowed activity feed statuses.
		 */
		$allowed_activity_feed_status = bb_groups_get_settings_status( 'activity_feed' );
		$activity_feed_status         = in_array( $_POST['group-activity-feed-status'], (array) $allowed_activity_feed_status ) ? $_POST['group-activity-feed-status'] : bb_groups_settings_default_fallback( 'activity_feed', current( $allowed_activity_feed_status ) );

		/**
		 * Filters the allowed media status values for the group.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $value Array of allowed media statuses.
		 */
		$allowed_media_status = bb_groups_get_settings_status( 'media' );
		$media_status         = isset( $_POST['group-media-status'] ) && in_array( $_POST['group-media-status'], (array) $allowed_media_status ) ? $_POST['group-media-status'] : bb_groups_settings_default_fallback( 'media', current( $allowed_media_status ) );

		/**
		 * Filters the allowed document status values for the group.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $value Array of allowed media statuses.
		 */
		$allowed_document_status = bb_groups_get_settings_status( 'document' );
		$document_status         = isset( $_POST['group-document-status'] ) && in_array( $_POST['group-document-status'], (array) $allowed_document_status ) ? $_POST['group-document-status'] : bb_groups_settings_default_fallback( 'document', current( $allowed_document_status ) );

		/**
		 * Filters the allowed video status values for the group.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $value Array of allowed media statuses.
		 */
		$allowed_video_status    = bb_groups_get_settings_status( 'video' );
		$post_group_video_status = bb_filter_input_string( INPUT_POST, 'group-video-status' );
		$video_status            = ! empty( $post_group_video_status ) && in_array( $post_group_video_status, (array) $allowed_video_status, true ) ? $post_group_video_status : bb_groups_settings_default_fallback( 'video', current( $allowed_video_status ) );

		/**
		 * Filters the allowed album status values for the group.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $value Array of allowed album statuses.
		 */
		$allowed_album_status    = bb_groups_get_settings_status( 'album' );
		$post_group_album_status = bb_filter_input_string( INPUT_POST, 'group-album-status' );
		$album_status            = ! empty( $post_group_album_status ) && in_array( $post_group_album_status, (array) $allowed_album_status, true ) ? $post_group_album_status : bb_groups_settings_default_fallback( 'album', current( $allowed_album_status ) );

		/**
		 * Filters the allowed album status values for the group.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $value Array of allowed album statuses.
		 */
		$allowed_message_status = bb_groups_get_settings_status( 'message' );
		$message_status         = isset( $_POST['group-message-status'] ) && in_array( $_POST['group-message-status'], (array) $allowed_message_status ) ? $_POST['group-message-status'] : bb_groups_settings_default_fallback( 'message', current( $allowed_message_status ) );

		if ( ! groups_edit_group_settings( $group_id, $enable_forum, $status, $invite_status, $activity_feed_status, false, $media_status, $document_status, $video_status, $album_status, $message_status ) ) {
			$error = $group_id;
		}

		// Process new members.
		$user_names = array();

		if ( ! empty( $_POST['bp-groups-new-members'] ) ) {
			$user_names = array_merge( $user_names, explode( ',', $_POST['bp-groups-new-members'] ) );
		}

		if ( ! empty( $user_names ) ) {

			foreach ( array_values( $user_names ) as $user_name ) {
				$un = trim( $user_name );

				// Make sure the user exists before attempting
				// to add to the group.
				$user = get_user_by( 'slug', $un );

				if ( empty( $user ) ) {
					$error_new[] = $un;
				} else {
					if ( ! groups_join_group( $group_id, $user->ID ) ) {
						$error_new[] = $un;
					} else {
						$success_new[] = $un;
					}
				}
			}
		}

		// Process member role changes.
		if ( ! empty( $_POST['bp-groups-role'] ) && ! empty( $_POST['bp-groups-existing-role'] ) ) {

			// Before processing anything, make sure you're not
			// attempting to remove the all user admins.
			$admin_count = 0;
			foreach ( (array) $_POST['bp-groups-role'] as $new_role ) {
				if ( 'admin' == $new_role ) {
					$admin_count++;
					break;
				}
			}

			if ( ! $admin_count ) {

				$redirect_to = add_query_arg( 'no_admins', 1, $redirect_to );
				$error       = $group_id;

			} else {

				// Process only those users who have had their roles changed.
				foreach ( (array) $_POST['bp-groups-role'] as $user_id => $new_role ) {
					$user_id = (int) $user_id;

					$existing_role = isset( $_POST['bp-groups-existing-role'][ $user_id ] ) ? $_POST['bp-groups-existing-role'][ $user_id ] : '';

					if ( $existing_role != $new_role ) {
						$result = false;

						switch ( $new_role ) {
							case 'mod':
								// Admin to mod is a demotion. Demote to
								// member, then fall through.
								if ( 'admin' == $existing_role ) {
									groups_demote_member( $user_id, $group_id );
								}

							case 'admin':
								// If the user was banned, we must
								// unban first.
								if ( 'banned' == $existing_role ) {
									groups_unban_member( $user_id, $group_id );
								}

								// At this point, each existing_role
								// is a member, so promote.
								$result = groups_promote_member( $user_id, $group_id, $new_role );

								break;

							case 'member':
								if ( 'admin' == $existing_role || 'mod' == $existing_role ) {
									$result = groups_demote_member( $user_id, $group_id );
								} elseif ( 'banned' == $existing_role ) {
									$result = groups_unban_member( $user_id, $group_id );
								}

								break;

							case 'banned':
								$result = groups_ban_member( $user_id, $group_id );

								break;

							case 'remove':
								$result = groups_remove_member( $user_id, $group_id );

								break;
						}

						// Store the success or failure.
						if ( $result ) {
							$success_modified[] = $user_id;
						} else {
							$error_modified[] = $user_id;
						}
					}
				}
			}
		}

		/**
		 * Fires before redirect so plugins can do something first on save action.
		 *
		 * @since BuddyPress 1.6.0
		 *
		 * @param int $group_id ID of the group being edited.
		 */
		do_action( 'bp_group_admin_edit_after', $group_id );

		// Create the redirect URL.
		if ( $error ) {
			// This means there was an error updating group details.
			$redirect_to = add_query_arg( 'error', (int) $error, $redirect_to );
		} else {
			// Group details were update successfully.
			$redirect_to = add_query_arg( 'updated', 1, $redirect_to );
		}

		if ( ! empty( $success_new ) ) {
			$success_new = implode( ',', array_filter( $success_new, 'urlencode' ) );
			$redirect_to = add_query_arg( 'success_new', $success_new, $redirect_to );
		}

		if ( ! empty( $error_new ) ) {
			$error_new   = implode( ',', array_filter( $error_new, 'urlencode' ) );
			$redirect_to = add_query_arg( 'error_new', $error_new, $redirect_to );
		}

		if ( ! empty( $success_modified ) ) {
			$success_modified = implode( ',', array_filter( $success_modified, 'urlencode' ) );
			$redirect_to      = add_query_arg( 'success_modified', $success_modified, $redirect_to );
		}

		if ( ! empty( $error_modified ) ) {
			$error_modified = implode( ',', array_filter( $error_modified, 'urlencode' ) );
			$redirect_to    = add_query_arg( 'error_modified', $error_modified, $redirect_to );
		}

		/**
		 * Filters the URL to redirect to after successfully editing a group.
		 *
		 * @since BuddyPress 1.7.0
		 *
		 * @param string $redirect_to URL to redirect user to.
		 */
		wp_safe_redirect( apply_filters( 'bp_group_admin_edit_redirect', $redirect_to ) );
		exit;

		// If a referrer and a nonce is supplied, but no action, redirect back.
	} elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
		wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}
}

/**
 * Handle save/update of screen options for the Groups component admin screen.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $value     Will always be false unless another plugin filters it first.
 * @param string $option    Screen option name.
 * @param string $new_value Screen option form value.
 * @return string|int Option value. False to abandon update.
 */
function bp_groups_admin_screen_options( $value, $option, $new_value ) {
	if ( 'buddyboss_page_bp_groups_per_page' != $option && 'buddyboss_page_bp_groups_network_per_page' != $option ) {
		return $value;
	}

	// Per page.
	$new_value = (int) $new_value;
	if ( $new_value < 1 || $new_value > 999 ) {
		return $value;
	}

	return $new_value;
}

/**
 * Select the appropriate Groups admin screen, and output it.
 *
 * @since BuddyPress 1.7.0
 */
function bp_groups_admin() {

	// Added navigation tab on top.
	if ( bp_core_get_groups_admin_tabs() ) { ?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_groups_tabs( __( 'All Groups', 'buddyboss' ) ); ?></h2>
		</div>
		<?php
	}
	// Decide whether to load the index or edit screen.
	$doaction = bp_admin_list_table_current_bulk_action();

	// Display the single group edit screen.
	if ( 'edit' == $doaction && ! empty( $_GET['gid'] ) ) {
		bp_groups_admin_edit();

		// Create the group from admin
	} elseif ( 'edit' == $doaction && ! empty( $_GET['create'] ) ) {
		bp_groups_admin_create();

		// Display the group deletion confirmation screen.
	} elseif ( 'delete' == $doaction && ! empty( $_GET['gid'] ) ) {
		bp_groups_admin_delete();

		// Otherwise, display the groups index screen.
	} else {
		bp_groups_admin_index();
	}
}

/**
 * Display the single groups edit screen.
 *
 * @since BuddyPress 1.7.0
 */
function bp_groups_admin_edit() {

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		die( '-1' );
	}

	$messages = array();

	// If the user has just made a change to a group, build status messages.
	if ( ! empty( $_REQUEST['no_admins'] ) || ! empty( $_REQUEST['error'] ) || ! empty( $_REQUEST['updated'] ) || ! empty( $_REQUEST['error_new'] ) || ! empty( $_REQUEST['success_new'] ) || ! empty( $_REQUEST['error_modified'] ) || ! empty( $_REQUEST['success_modified'] ) ) {
		$no_admins        = ! empty( $_REQUEST['no_admins'] ) ? 1 : 0;
		$errors           = ! empty( $_REQUEST['error'] ) ? $_REQUEST['error'] : '';
		$updated          = ! empty( $_REQUEST['updated'] ) ? $_REQUEST['updated'] : '';
		$error_new        = ! empty( $_REQUEST['error_new'] ) ? explode( ',', $_REQUEST['error_new'] ) : array();
		$success_new      = ! empty( $_REQUEST['success_new'] ) ? explode( ',', $_REQUEST['success_new'] ) : array();
		$error_modified   = ! empty( $_REQUEST['error_modified'] ) ? explode( ',', $_REQUEST['error_modified'] ) : array();
		$success_modified = ! empty( $_REQUEST['success_modified'] ) ? explode( ',', $_REQUEST['success_modified'] ) : array();

		if ( ! empty( $no_admins ) ) {
			$messages[] = __( 'This group must have at least one organizer.', 'buddyboss' );
		}

		if ( ! empty( $errors ) ) {
			if ( $errors < 0 ) {
				$messages[] = __( 'Group name, slug, and description are all required fields.', 'buddyboss' );
			} else {
				$messages[] = __( 'An error occurred when trying to update your group details.', 'buddyboss' );
			}
		} elseif ( ! empty( $updated ) ) {
			$messages[] = __( 'The group has been updated successfully.', 'buddyboss' );
		}

		if ( ! empty( $error_new ) ) {
			$messages[] = sprintf( __( 'The following users could not be added to the group: %s', 'buddyboss' ), '<em>' . esc_html( implode( ', ', $error_new ) ) . '</em>' );
		}

		if ( ! empty( $success_new ) ) {
			$messages[] = sprintf( __( 'The following users were successfully added to the group: %s', 'buddyboss' ), '<em>' . esc_html( implode( ', ', $success_new ) ) . '</em>' );
		}

		if ( ! empty( $error_modified ) ) {
			$error_modified = bp_groups_admin_get_usernames_from_ids( $error_modified );
			$messages[]     = sprintf( __( 'An error occurred when trying to modify the following members: %s', 'buddyboss' ), '<em>' . esc_html( implode( ', ', $error_modified ) ) . '</em>' );
		}

		if ( ! empty( $success_modified ) ) {
			$success_modified = bp_groups_admin_get_usernames_from_ids( $success_modified );
			$messages[]       = sprintf( __( 'The following members were successfully modified: %s', 'buddyboss' ), '<em>' . esc_html( implode( ', ', $success_modified ) ) . '</em>' );
		}
	}

	$is_error = ! empty( $no_admins ) || ! empty( $errors ) || ! empty( $error_new ) || ! empty( $error_modified );

	// Get the group from the database.
	$group = groups_get_group( (int) $_GET['gid'] );

	$group_name = isset( $group->name ) ? bp_get_group_name( $group ) : '';

	// Construct URL for form.
	$form_url = remove_query_arg( array( 'action', 'deleted', 'no_admins', 'error', 'error_new', 'success_new', 'error_modified', 'success_modified' ), $_SERVER['REQUEST_URI'] );
	$form_url = add_query_arg( 'action', 'save', $form_url );

	/**
	 * Fires before the display of the edit form.
	 *
	 * Useful for plugins to modify the group before display.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param BP_Groups_Group $this Instance of the current group being edited. Passed by reference.
	 */
	do_action_ref_array( 'bp_groups_admin_edit', array( &$group ) );
	?>

	<div class="wrap">
		<?php if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) : ?>

			<h1 class="wp-heading-inline"><?php _e( 'Edit Group', 'buddyboss' ); ?></h1>

			<?php if ( is_user_logged_in() && bp_user_can_create_groups() ) : ?>
				<a class="page-title-action" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page'   => 'bp-groups',
							'create' => 'create-from-admin',
							'action' => 'edit',
						),
						bp_get_admin_url( 'admin.php' )
					)
				);
				?>
				"><?php _e( 'New Group', 'buddyboss' ); ?></a>
			<?php endif; ?>

			<hr class="wp-header-end">

		<?php else : ?>

			<h1><?php _e( 'Edit Group', 'buddyboss' ); ?>

				<?php if ( is_user_logged_in() && bp_user_can_create_groups() ) : ?>
					<a class="add-new-h2" href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'page'   => 'bp-groups',
								'create' => 'create-from-admin',
								'action' => 'edit',
							),
							bp_get_admin_url( 'admin.php' )
						)
					);
					?>
					"><?php _e( 'New Group', 'buddyboss' ); ?></a>
				<?php endif; ?>

			</h1>

		<?php endif; ?>

		<?php // If the user has just made a change to an group, display the status messages. ?>
		<?php if ( ! empty( $messages ) ) : ?>
			<div id="moderated" class="<?php echo ( $is_error ) ? 'error' : 'updated'; ?>"><p><?php echo implode( '</p><p>', $messages ); ?></p></div>
		<?php endif; ?>

		<?php if ( $group->id ) : ?>

			<form action="<?php echo esc_url( $form_url ); ?>" id="bp-groups-edit-form" method="post">
				<div id="poststuff">

					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
						<div id="post-body-content">
							<div id="postdiv">
								<div id="bp_groups_name" class="postbox">
									<h2><?php _e( 'Name and Description', 'buddyboss' ); ?></h2>
									<div class="inside">
										<label for="bp-groups-name" class="screen-reader-text">
										<?php
											/* translators: accessibility text */
											_e( 'Group Name', 'buddyboss' );
										?>
										</label>
										<input type="text" name="bp-groups-name" id="bp-groups-name" value="<?php echo esc_attr( stripslashes( $group_name ) ); ?>" />
										<div id="bp-groups-permalink-box">
											<strong><?php esc_html_e( 'Permalink:', 'buddyboss' ); ?></strong>
											<span id="bp-groups-permalink">
												<?php bp_groups_directory_permalink(); ?> <input type="text" id="bp-groups-slug" name="bp-groups-slug" value="<?php echo rawurldecode( bp_get_group_slug( $group ) ); ?>" autocomplete="off"> /
											</span>
											<a href="<?php echo bp_group_permalink( $group ); ?>" class="button button-small" id="bp-groups-visit-group"><?php esc_html_e( 'Visit Group', 'buddyboss' ); ?></a>
										</div>

										<label for="bp-groups-description" class="screen-reader-text">
										<?php
											/* translators: accessibility text */
											_e( 'Group Description', 'buddyboss' );
										?>
										</label>
										<?php
										wp_editor(
											stripslashes( $group->description ),
											'bp-groups-description',
											array(
												'media_buttons' => false,
												'teeny' => true,
												'textarea_rows' => 5,
												'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' ),
											)
										);
										?>
									</div>
								</div>
							</div>
						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">
							<?php do_meta_boxes( get_current_screen()->id, 'side', $group ); ?>
						</div>

						<div id="postbox-container-2" class="postbox-container">
							<?php do_meta_boxes( get_current_screen()->id, 'normal', $group ); ?>
							<?php do_meta_boxes( get_current_screen()->id, 'advanced', $group ); ?>
						</div>
					</div><!-- #post-body -->

				</div><!-- #poststuff -->
				<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
				<?php wp_nonce_field( 'edit-group_' . $group->id ); ?>
			</form>

		<?php else : ?>

			<p>
			<?php
				printf(
					'%1$s <a href="%2$s">%3$s</a>',
					__( 'No group found with this ID.', 'buddyboss' ),
					esc_url( bp_get_admin_url( 'admin.php?page=bp-groups' ) ),
					__( 'Go back and try again.', 'buddyboss' )
				);
			?>
			</p>

		<?php endif; ?>

	</div><!-- .wrap -->

	<?php
}

/**
 * Display the single groups create screen.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_groups_admin_create() {

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		die( '-1' );
	}

	?>

	<div class="wrap">
		<?php if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) : ?>

			<h1 class="wp-heading-inline"><?php _e( 'Create New Group', 'buddyboss' ); ?></h1>

			<hr class="wp-header-end">

		<?php else : ?>

			<h1><?php _e( 'Create New Group', 'buddyboss' ); ?> </h1>

		<?php endif; ?>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="bp-groups-edit-form" method="post">
			<div id="poststuff">

				<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
					<div id="post-body-content">
						<div id="postdiv">
							<div id="bp_groups_name" class="postbox">
								<h2><?php _e( 'Name and Description', 'buddyboss' ); ?></h2>
								<div class="inside">
									<label for="bp-groups-name" class="screen-reader-text">
									<?php
										/* translators: accessibility text */
										_e( 'Group Name', 'buddyboss' );
									?>
										</label>
									<input required type="text" name="bp-groups-name" id="bp-groups-name" value="" />


									<label for="bp-groups-description" class="screen-reader-text">
									<?php
										/* translators: accessibility text */
										_e( 'Group Description', 'buddyboss' );
									?>
										</label>
									<?php
									wp_editor(
										'',
										'bp-groups-description',
										array(
											'media_buttons' => false,
											'teeny'     => true,
											'textarea_rows' => 5,
											'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' ),
										)
									);
									?>
								</div>
							</div>
						</div>
					</div><!-- #post-body-content -->

					<input type="hidden" name="action" value="bp_create_group_admin">
					<?php wp_nonce_field( 'bp_create_group_form_nonce' ); ?>

					<div id="postbox-container-1" class="postbox-container">
						<div id="submitdiv" class="postbox">
							<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Publish', 'buddyboss' ); ?></span></h2>
							<div class="inside">
								<div id="submitcomment" class="submitbox">
									<div id="major-publishing-actions">
										<div id="publishing-action">
											<input type="submit" name="save" id="save" class="button button-primary" value="<?php esc_attr_e( 'Publish', 'buddyboss' ); ?>">			</div>
										<div class="clear"></div>
									</div><!-- #major-publishing-actions -->
								</div><!-- #submitcomment -->
							</div>
						</div>
					</div>


				</div><!-- #post-body -->

			</div><!-- #poststuff -->

		</form>


	</div><!-- .wrap -->

	<?php
}

// action for saving the data of newly create group from the backend.
add_action( 'admin_post_bp_create_group_admin', 'bp_process_create_group_admin' );

/**
 * Saving the data of newly create group from the backend.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_process_create_group_admin() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not allowed to be on this page.' );
	}
	// Check that nonce field
	check_admin_referer( 'bp_create_group_form_nonce' );

	$new_group_id = groups_create_group(
		array(
			'group_id'     => 0,
			'name'         => $_POST['bp-groups-name'],
			'description'  => $_POST['bp-groups-description'],
			'slug'         => groups_check_slug( sanitize_title( esc_attr( $_POST['bp-groups-name'] ) ) ),
			'date_created' => bp_core_current_time(),
			'status'       => 'public',
		)
	);

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'   => 'bp-groups',
				'gid'    => $new_group_id,
				'action' => 'edit',
			),
			bp_get_admin_url( 'admin.php' )
		)
	);
	exit();
}

/**
 * Display the Group delete confirmation screen.
 *
 * We include a separate confirmation because group deletion is truly
 * irreversible.
 *
 * @since BuddyPress 1.7.0
 */
function bp_groups_admin_delete() {

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		die( '-1' );
	}

	$group_ids = isset( $_REQUEST['gid'] ) ? $_REQUEST['gid'] : 0;
	if ( ! is_array( $group_ids ) ) {
		$group_ids = explode( ',', $group_ids );
	}
	$group_ids = wp_parse_id_list( $group_ids );

	add_filter( 'bp_groups_get_where_conditions', 'bb_groups_get_where_conditions', 10, 2 );
	$groups    = groups_get_groups(
		array(
			'include'     => $group_ids,
			'per_page'    => null, // Return all results.
		)
	);
	remove_filter( 'bp_groups_get_where_conditions', 'bb_groups_get_where_conditions', 10, 2 );

	// Create a new list of group ids, based on those that actually exist.
	$gids = array();
	foreach ( $groups['groups'] as $group ) {
		$gids[] = $group->id;
	}

	$base_url = remove_query_arg( array( 'action', 'action2', 'paged', 's', '_wpnonce', 'gid' ), $_SERVER['REQUEST_URI'] );
	?>

	<div class="wrap">
		<h1><?php _e( 'Delete Groups', 'buddyboss' ); ?></h1>
		<p><?php _e( 'You are about to delete the following groups:', 'buddyboss' ); ?></p>

		<ul class="bp-group-delete-list">
		<?php foreach ( $groups['groups'] as $group ) : ?>
			<li>
				<?php echo esc_html( bp_get_group_name( $group ) ); ?>
				<?php if ( bp_is_active( 'forums' ) && bbp_get_group_forum_ids( $group->id ) ) : ?>
					<label for="delete-group-forum-<?php echo $group->id; ?>" class="delete-forum-label">
						<input type="checkbox" name="delete_group_forum" id="delete-group-forum-<?php echo $group->id; ?>" value="<?php echo $group->id; ?>" checked/>
						<?php esc_html_e( 'Permanently delete the group discussion forum', 'buddyboss' ); ?>
					</label>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>

		<p><strong><?php _e( 'This action cannot be undone.', 'buddyboss' ); ?></strong></p>

		<a
			class="button-primary"
			id="delete-groups-submit"
			href="
			<?php
			echo esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'do_delete',
							'gid'    => implode( ',', $gids ),
						),
						$base_url
					),
					'bp-groups-delete'
				)
			);
			?>
			"
		>
			<?php _e( 'Delete Permanently', 'buddyboss' ); ?>
		</a>
		<a class="button" href="<?php echo esc_attr( $base_url ); ?>"><?php _e( 'Cancel', 'buddyboss' ); ?></a>
	</div>

	<?php
}

/**
 * Display the Groups admin index screen.
 *
 * This screen contains a list of all BuddyBoss groups.
 *
 * @since BuddyPress 1.7.0
 *
 * @global BP_Groups_List_Table $bp_groups_list_table Group screen list table.
 * @global string $plugin_page Currently viewed plugin page.
 */
function bp_groups_admin_index() {
	global $bp_groups_list_table, $plugin_page;

	$messages = array();

	// If the user has just made a change to a group, build status messages.
	if ( ! empty( $_REQUEST['deleted'] ) ) {
		$deleted = ! empty( $_REQUEST['deleted'] ) ? (int) $_REQUEST['deleted'] : 0;

		if ( $deleted > 0 ) {
			$messages[] = sprintf( _n( '%s group has been permanently deleted.', '%s groups have been permanently deleted.', $deleted, 'buddyboss' ), bp_core_number_format( $deleted ) );
		}
	}

	// Prepare the group items for display.
	$bp_groups_list_table->prepare_items();

	/**
	 * Fires before the display of messages for the edit form.
	 *
	 * Useful for plugins to modify the messages before display.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $messages Array of messages to be displayed.
	 */
	do_action( 'bp_groups_admin_index', $messages );
	?>

	<div class="wrap">
		<?php if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) : ?>

			<h1 class="wp-heading-inline"><?php _e( 'Groups', 'buddyboss' ); ?></h1>

			<?php if ( is_user_logged_in() && bp_user_can_create_groups() ) : ?>
				<a class="page-title-action" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page'   => 'bp-groups',
							'create' => 'create-from-admin',
							'action' => 'edit',
						),
						bp_get_admin_url( 'admin.php' )
					)
				);
				?>
				"><?php _e( 'New Group', 'buddyboss' ); ?></a>
			<?php endif; ?>

			<?php if ( ! empty( $_REQUEST['s'] ) ) : ?>
				<span class="subtitle"><?php printf( __( 'Search results for "%s"', 'buddyboss' ), wp_html_excerpt( esc_html( stripslashes( $_REQUEST['s'] ) ), 50 ) ); ?></span>
			<?php endif; ?>

			<hr class="wp-header-end">

		<?php else : ?>

		<h1>
			<?php _e( 'Groups', 'buddyboss' ); ?>

			<?php if ( is_user_logged_in() && bp_user_can_create_groups() ) : ?>
				<a class="add-new-h2" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page'   => 'bp-groups',
							'create' => 'create-from-admin',
							'action' => 'edit',
						),
						bp_get_admin_url( 'admin.php' )
					)
				);
				?>
				"><?php _e( 'New Group', 'buddyboss' ); ?></a>
			<?php endif; ?>

			<?php if ( ! empty( $_REQUEST['s'] ) ) : ?>
				<span class="subtitle"><?php printf( __( 'Search results for "%s"', 'buddyboss' ), wp_html_excerpt( esc_html( stripslashes( $_REQUEST['s'] ) ), 50 ) ); ?></span>
			<?php endif; ?>
		</h1>

		<?php endif; ?>

		<?php // If the user has just made a change to an group, display the status messages. ?>
		<?php if ( ! empty( $messages ) ) : ?>
			<div id="moderated" class="<?php echo ( ! empty( $_REQUEST['error'] ) ) ? 'error' : 'updated'; ?>"><p><?php echo implode( "<br/>\n", $messages ); ?></p></div>
		<?php endif; ?>

		<?php // Display each group on its own row. ?>
		<?php $bp_groups_list_table->views(); ?>

		<form id="bp-groups-form" action="" method="get">
			<?php $bp_groups_list_table->search_box( __( 'Search all Groups', 'buddyboss' ), 'bp-groups' ); ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
			<?php $bp_groups_list_table->display(); ?>
		</form>

	</div>

	<?php
}

/**
 * Markup for the single group's Settings metabox.
 *
 * @since BuddyPress 1.7.0
 *
 * @param object $item Information about the current group.
 */
function bp_groups_admin_edit_metabox_settings( $item ) {

	$invite_status        = bp_group_get_invite_status( $item->id );
	$activity_feed_status = bp_group_get_activity_feed_status( $item->id );
	$media_status         = bp_group_get_media_status( $item->id );
	$album_status         = bp_group_get_album_status( $item->id );
	$document_status      = bp_group_get_document_status( $item->id );
	$video_status         = bp_group_get_video_status( $item->id );
	$message_status       = bp_group_get_message_status( $item->id );
	?>

	<div class="bp-groups-settings-section" id="bp-groups-settings-section-status">
		<fieldset>
			<legend><?php esc_html_e( 'Group Privacy', 'buddyboss' ); ?></legend>

			<label for="bp-group-status-public"><input type="radio" name="group-status" id="bp-group-status-public" value="public" <?php checked( $item->status, 'public' ); ?> /><?php esc_html_e( 'Public', 'buddyboss' ); ?></label>
			<label for="bp-group-status-private"><input type="radio" name="group-status" id="bp-group-status-private" value="private" <?php checked( $item->status, 'private' ); ?> /><?php esc_html_e( 'Private', 'buddyboss' ); ?></label>
			<label for="bp-group-status-hidden"><input type="radio" name="group-status" id="bp-group-status-hidden" value="hidden" <?php checked( $item->status, 'hidden' ); ?> /><?php esc_html_e( 'Hidden', 'buddyboss' ); ?></label>
		</fieldset>
	</div>

	<div class="bp-groups-settings-section" id="bp-groups-settings-section-invite-status">
		<fieldset>
			<legend><?php esc_html_e( 'Who can invite others to join this group?', 'buddyboss' ); ?></legend>

			<label for="bp-group-invite-status-members"><input type="radio" name="group-invite-status" id="bp-group-invite-status-members" value="members" <?php checked( $invite_status, 'members' ); ?> /><?php esc_html_e( 'All Members', 'buddyboss' ); ?></label>
			<label for="bp-group-invite-status-mods"><input type="radio" name="group-invite-status" id="bp-group-invite-status-mods" value="mods" <?php checked( $invite_status, 'mods' ); ?> /><?php esc_html_e( 'Organizers and Moderators', 'buddyboss' ); ?></label>
			<label for="bp-group-invite-status-admins"><input type="radio" name="group-invite-status" id="bp-group-invite-status-admins" value="admins" <?php checked( $invite_status, 'admins' ); ?> /><?php esc_html_e( 'Organizers', 'buddyboss' ); ?></label>
		</fieldset>
	</div>

	<?php if ( bp_is_active( 'activity' ) ) : ?>
		<div class="bp-groups-settings-section" id="bp-groups-settings-section-activity-feed-status">
			<fieldset>
				<legend><?php esc_html_e( 'Who can post into this group?', 'buddyboss' ); ?></legend>

				<label for="bp-group-activity-feed-status-members"><input type="radio" name="group-activity-feed-status" id="bp-group-activity-feed-status-members" value="members" <?php checked( $activity_feed_status, 'members' ); ?> /><?php esc_html_e( 'All group members', 'buddyboss' ); ?></label>
				<label for="bp-group-activity-feed-status-mods"><input type="radio" name="group-activity-feed-status" id="bp-group-activity-feed-status-mods" value="mods" <?php checked( $activity_feed_status, 'mods' ); ?> /><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss' ); ?></label>
				<label for="bp-group-activity-feed-status-admins"><input type="radio" name="group-activity-feed-status" id="bp-group-activity-feed-status-admins" value="admins" <?php checked( $activity_feed_status, 'admins' ); ?> /><?php esc_html_e( 'Organizers only', 'buddyboss' ); ?></label>
			</fieldset>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() ) : ?>
		<div class="bp-groups-settings-section" id="bp-groups-settings-section-album-status">
			<fieldset>
				<legend><?php esc_html_e( 'Who can upload photos in this group?', 'buddyboss' ); ?></legend>

				<label for="bp-group-media-status-members"><input type="radio" name="group-media-status" id="bp-group-media-status-members" value="members" <?php checked( $media_status, 'members' ); ?> /><?php esc_html_e( 'All Members', 'buddyboss' ); ?></label>
				<label for="bp-group-media-status-mods"><input type="radio" name="group-media-status" id="bp-group-media-status-mods" value="mods" <?php checked( $media_status, 'mods' ); ?> /><?php esc_html_e( 'Organizers and Moderators', 'buddyboss' ); ?></label>
				<label for="bp-group-media-status-admins"><input type="radio" name="group-media-status" id="bp-group-media-status-admins" value="admins" <?php checked( $media_status, 'admins' ); ?> /><?php esc_html_e( 'Organizers', 'buddyboss' ); ?></label>
			</fieldset>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_group_albums_support_enabled() ) : ?>
		<div class="bp-groups-settings-section" id="bp-groups-settings-section-album-status">
			<fieldset>
				<legend><?php esc_html_e( 'Who can create albums in this group?', 'buddyboss' ); ?></legend>

				<label for="bp-group-album-status-members"><input type="radio" name="group-album-status" id="bp-group-album-status-members" value="members" <?php checked( $album_status, 'members' ); ?> /><?php esc_html_e( 'All Members', 'buddyboss' ); ?></label>
				<label for="bp-group-album-status-mods"><input type="radio" name="group-album-status" id="bp-group-album-status-mods" value="mods" <?php checked( $album_status, 'mods' ); ?> /><?php esc_html_e( 'Organizers and Moderators', 'buddyboss' ); ?></label>
				<label for="bp-group-album-status-admins"><input type="radio" name="group-album-status" id="bp-group-album-status-admins" value="admins" <?php checked( $album_status, 'admins' ); ?> /><?php esc_html_e( 'Organizers', 'buddyboss' ); ?></label>
			</fieldset>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() ) : ?>
		<div class="bp-groups-settings-section" id="bp-groups-settings-section-document-status">
			<fieldset>
				<legend><?php esc_html_e( 'Who can upload documents in this group?', 'buddyboss' ); ?></legend>

				<label for="bp-group-document-status-members"><input type="radio" name="group-document-status" id="bp-group-document-status-members" value="members" <?php checked( $document_status, 'members' ); ?> /><?php esc_html_e( 'All Members', 'buddyboss' ); ?></label>
				<label for="bp-group-document-status-mods"><input type="radio" name="group-document-status" id="bp-group-document-status-mods" value="mods" <?php checked( $document_status, 'mods' ); ?> /><?php esc_html_e( 'Organizers and Moderators', 'buddyboss' ); ?></label>
				<label for="bp-group-document-status-admins"><input type="radio" name="group-document-status" id="bp-group-document-status-admins" value="admins" <?php checked( $document_status, 'admins' ); ?> /><?php esc_html_e( 'Organizers', 'buddyboss' ); ?></label>
			</fieldset>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_group_video_support_enabled() ) : ?>
		<div class="bp-groups-settings-section" id="bp-groups-settings-section-video-status">
			<fieldset>
				<legend><?php esc_html_e( 'Who can upload videos in this group?', 'buddyboss' ); ?></legend>

				<label for="bp-group-video-status-members"><input type="radio" name="group-video-status" id="bp-group-video-status-members" value="members" <?php checked( $video_status, 'members' ); ?> /><?php esc_html_e( 'All Members', 'buddyboss' ); ?></label>
				<label for="bp-group-video-status-mods"><input type="radio" name="group-video-status" id="bp-group-video-status-mods" value="mods" <?php checked( $video_status, 'mods' ); ?> /><?php esc_html_e( 'Organizers and Moderators', 'buddyboss' ); ?></label>
				<label for="bp-group-video-status-admins"><input type="radio" name="group-video-status" id="bp-group-video-status-admins" value="admins" <?php checked( $video_status, 'admins' ); ?> /><?php esc_html_e( 'Organizers', 'buddyboss' ); ?></label>
			</fieldset>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'messages' ) && bp_disable_group_messages() ) : ?>
		<div class="bp-groups-settings-section" id="bp-groups-settings-section-message-status">
			<fieldset>
				<legend><?php esc_html_e( 'Who can manage group messages in this group?', 'buddyboss' ); ?></legend>

				<label for="bp-group-message-status-members"><input type="radio" name="group-message-status" id="bp-group-message-status-members" value="members" <?php checked( $message_status, 'members' ); ?> /><?php esc_html_e( 'All Members', 'buddyboss' ); ?></label>
				<label for="bp-group-message-status-mods"><input type="radio" name="group-message-status" id="bp-group-message-status-mods" value="mods" <?php checked( $message_status, 'mods' ); ?> /><?php esc_html_e( 'Organizers and Moderators', 'buddyboss' ); ?></label>
				<label for="bp-group-message-status-admins"><input type="radio" name="group-message-status" id="bp-group-message-status-admins" value="admins" <?php checked( $message_status, 'admins' ); ?> /><?php esc_html_e( 'Organizers', 'buddyboss' ); ?></label>
			</fieldset>
		</div>
	<?php endif; ?>

	<?php
}

/**
 * Markup for the single group's Group Hierarchy metabox.
 *
 * @since BuddyPress 1.7.0
 *
 * @param object $item Information about the current group.
 */
function bp_groups_admin_edit_metabox_group_parent( $item ) {

	$current_parent_group_id = bp_get_parent_group_id( $item->id );
	$possible_parent_groups  = bp_get_possible_parent_groups( $item->id, bp_loggedin_user_id() );
	?>

	<div class="bp-groups-settings-section" id="bp-groups-settings-section-group-hierarchy">
		<select id="bp-groups-parent" name="bp-groups-parent" autocomplete="off">
			<option
				value="0" <?php selected( 0, $current_parent_group_id ); ?>><?php _e( 'Select Parent', 'buddyboss' ); ?></option>
			<?php
			if ( $possible_parent_groups ) {

				foreach ( $possible_parent_groups as $possible_parent_group ) {
					?>
					<option
						value="<?php echo $possible_parent_group->id; ?>" <?php selected( $current_parent_group_id, $possible_parent_group->id ); ?>><?php echo esc_html( $possible_parent_group->name ); ?></option>
					<?php
				}
			}
			?>
		</select>
	</div>

	<?php
}

/**
 * Output the markup for a single group's Add New Members metabox.
 *
 * @since BuddyPress 1.7.0
 *
 * @param BP_Groups_Group $item The BP_Groups_Group object for the current group.
 */
function bp_groups_admin_edit_metabox_add_new_members( $item ) {
	?>

	<label for="bp-groups-new-members" class="screen-reader-text">
	<?php
		/* translators: accessibility text */
		_e( 'Add new members', 'buddyboss' );
	?>
	</label>
	<input name="bp-groups-new-members" type="text" id="bp-groups-new-members" class="bp-suggest-user" placeholder="<?php esc_attr_e( 'Enter a comma-separated list of user logins.', 'buddyboss' ); ?>" />
	<ul id="bp-groups-new-members-list"></ul>
	<?php
}

/**
 * Renders the Members metabox on single group pages.
 *
 * @since BuddyPress 1.7.0
 *
 * @param BP_Groups_Group $item The BP_Groups_Group object for the current group.
 */
function bp_groups_admin_edit_metabox_members( $item ) {

	// Pull up a list of group members, so we can separate out the types
	// We'll also keep track of group members here to place them into a
	// JavaScript variable, which will help with group member autocomplete.
	$members = array(
		'admin'  => array(),
		'mod'    => array(),
		'member' => array(),
		'banned' => array(),
	);

	$pagination = array(
		'admin'  => array(),
		'mod'    => array(),
		'member' => array(),
		'banned' => array(),
	);

	foreach ( $members as $type => &$member_type_users ) {
		$page_qs_key       = $type . '_page';
		$current_type_page = isset( $_GET[ $page_qs_key ] ) ? absint( $_GET[ $page_qs_key ] ) : 1;
		$member_type_query = new BP_Group_Member_Query(
			array(
				'group_id'   => $item->id,
				'group_role' => array( $type ),
				'type'       => 'alphabetical',
				/**
				 * Filters the admin members type per page value.
				 *
				 * @since BuddyPress 2.8.0
				 *
				 * @param int    $value profile types per page. Default 10.
				 * @param string $type  profile type.
				 */
				'per_page'   => apply_filters( 'bp_groups_admin_members_type_per_page', 10, $type ),
				'page'       => $current_type_page,
			)
		);

		$member_type_users   = $member_type_query->results;
		$pagination[ $type ] = bp_groups_admin_create_pagination_links( $member_type_query, $type );
	}

	// Echo out the JavaScript variable.
	echo '<script>var group_id = "' . esc_js( $item->id ) . '";</script>';

	// Loop through each profile type.
	foreach ( $members as $member_type => $type_users ) :
		?>

		<div class="bp-groups-member-type" id="bp-groups-member-type-<?php echo esc_attr( $member_type ); ?>">

			<h3>
			<?php
			switch ( $member_type ) :
				case 'admin':
							  esc_html_e( 'Organizers', 'buddyboss' );
					break;
				case 'mod':
							  esc_html_e( 'Moderators', 'buddyboss' );
					break;
				case 'member':
							  esc_html_e( 'Members', 'buddyboss' );
					break;
				case 'banned':
							  esc_html_e( 'Banned Members', 'buddyboss' );
					break;
			endswitch;
			?>
			</h3>

			<div class="bp-group-admin-pagination table-top">
				<?php echo $pagination[ $member_type ]; ?>
			</div>

		<?php if ( ! empty( $type_users ) ) : ?>

			<table class="widefat bp-group-members">
				<thead>
					<tr>
						<th scope="col" class="uid-column"><?php _e( 'ID', 'buddyboss' ); ?></th>
						<th scope="col" class="uname-column"><?php _e( 'Name', 'buddyboss' ); ?></th>
						<th scope="col" class="urole-column"><?php _e( 'Role', 'buddyboss' ); ?></th>
					</tr>
				</thead>

				<tbody>

				<?php foreach ( $type_users as $type_user ) : ?>
					<tr>
						<th scope="row" class="uid-column"><?php echo esc_html( $type_user->ID ); ?></th>

						<td class="uname-column">
							<a style="float: left;" href="<?php echo bp_core_get_user_domain( $type_user->ID ); ?>">
																	 <?php
																		echo bp_core_fetch_avatar(
																			array(
																				'item_id' => $type_user->ID,
																				'width'   => '32',
																				'height'  => '32',
																			)
																		);
																		?>
							</a>

							<span style="margin: 8px; float: left;"><?php echo bp_core_get_userlink( $type_user->ID ); ?></span>
						</td>

						<td class="urole-column">
							<label for="bp-groups-role-<?php echo esc_attr( $type_user->ID ); ?>" class="screen-reader-text">
																  <?php
																	/* translators: accessibility text */
																	_e( 'Select group role for member', 'buddyboss' );
																	?>
							</label>
							<select class="bp-groups-role" id="bp-groups-role-<?php echo esc_attr( $type_user->ID ); ?>" name="bp-groups-role[<?php echo esc_attr( $type_user->ID ); ?>]">
								<optgroup label="<?php esc_attr_e( 'Roles', 'buddyboss' ); ?>">
									<option class="admin"  value="admin"  <?php selected( 'admin', $member_type ); ?>><?php esc_html_e( 'Organizer', 'buddyboss' ); ?></option>
									<option class="mod"    value="mod"    <?php selected( 'mod', $member_type ); ?>><?php esc_html_e( 'Moderator', 'buddyboss' ); ?></option>
									<option class="member" value="member" <?php selected( 'member', $member_type ); ?>><?php esc_html_e( 'Member', 'buddyboss' ); ?></option>
									<?php if ( 'banned' === $member_type ) : ?>
									<option class="banned" value="banned" <?php selected( 'banned', $member_type ); ?>><?php esc_html_e( 'Banned', 'buddyboss' ); ?></option>
									<?php endif; ?>
								</optgroup>
								<optgroup label="<?php esc_attr_e( 'Actions', 'buddyboss' ); ?>">
									<option class="remove" value="remove"><?php esc_html_e( 'Remove', 'buddyboss' ); ?></option>
									<?php if ( 'banned' !== $member_type ) : ?>
										<option class="banned" value="banned"><?php esc_html_e( 'Ban', 'buddyboss' ); ?></option>
									<?php endif; ?>
								</optgroup>
							</select>

							<?php
							/**
							 * Store the current role for this user,
							 * so we can easily detect changes.
							 *
							 * @todo remove this, and do database detection on save
							 */
							?>
							<input type="hidden" name="bp-groups-existing-role[<?php echo esc_attr( $type_user->ID ); ?>]" value="<?php echo esc_attr( $member_type ); ?>" />
						</td>
					</tr>

					<?php if ( has_filter( 'bp_groups_admin_manage_member_row' ) ) : ?>
						<tr>
							<td colspan="3">
								<?php

								/**
								 * Fires after the listing of a single row for members in a group on the group edit screen.
								 *
								 * @since BuddyPress 1.8.0
								 *
								 * @param int             $ID   ID of the user being rendered.
								 * @param BP_Groups_Group $item Object for the current group.
								 */
								do_action( 'bp_groups_admin_manage_member_row', $type_user->ID, $item );
								?>
							</td>
						</tr>
					<?php endif; ?>

				<?php endforeach; ?>

				</tbody>
			</table>

		<?php else : ?>

			<p class="bp-groups-no-members description"><?php esc_html_e( 'No members of this type', 'buddyboss' ); ?></p>

		<?php endif; ?>

		</div><!-- .bp-groups-member-type -->

		<?php
	endforeach;
}

/**
 * Renders the Status metabox for the Groups admin edit screen.
 *
 * @since BuddyPress 1.7.0
 *
 * @param object $item Information about the currently displayed group.
 */
function bp_groups_admin_edit_metabox_status( $item ) {
	$base_url = add_query_arg(
		array(
			'page' => 'bp-groups',
			'gid'  => $item->id,
		),
		bp_get_admin_url( 'admin.php' )
	);
	?>

	<div id="submitcomment" class="submitbox">
		<div id="major-publishing-actions">
			<div id="delete-action">
				<a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $base_url ), 'bp-groups-delete' ) ); ?>"><?php _e( 'Delete Group', 'buddyboss' ); ?></a>
			</div>

			<div id="publishing-action">
				<?php submit_button( __( 'Save Changes', 'buddyboss' ), 'primary', 'save', false ); ?>
			</div>
			<div class="clear"></div>
		</div><!-- #major-publishing-actions -->
	</div><!-- #submitcomment -->

	<?php
}

/**
 * Create pagination links out of a BP_Group_Member_Query.
 *
 * This function is intended to create pagination links for use under the
 * Manage Members section of the Groups Admin Dashboard pages. It is a stopgap
 * measure until a more general pagination solution is in place for BuddyPress.
 * Plugin authors should not use this function, as it is likely to be
 * deprecated soon.
 *
 * @since BuddyPress 1.8.0
 *
 * @param BP_Group_Member_Query $query       A BP_Group_Member_Query object.
 * @param string                $member_type member|mod|admin|banned.
 * @return string Pagination links HTML.
 */
function bp_groups_admin_create_pagination_links( BP_Group_Member_Query $query, $member_type ) {
	$pagination = '';

	if ( ! in_array( $member_type, array( 'admin', 'mod', 'member', 'banned' ) ) ) {
		return $pagination;
	}

	// The key used to paginate this profile type in the $_GET global.
	$qs_key   = $member_type . '_page';
	$url_base = remove_query_arg( array( $qs_key, 'updated', 'success_modified' ), $_SERVER['REQUEST_URI'] );

	$page = isset( $_GET[ $qs_key ] ) ? absint( $_GET[ $qs_key ] ) : 1;

	/**
	 * Filters the number of members per profile type that is displayed in group editing admin area.
	 *
	 * @since BuddyPress 2.8.0
	 *
	 * @param string $member_type profile type, which is a group role (admin, mod etc).
	 */
	$per_page = apply_filters( 'bp_groups_admin_members_type_per_page', 10, $member_type );

	// Don't show anything if there's no pagination.
	if ( 1 === $page && $query->total_users <= $per_page ) {
		return $pagination;
	}

	$current_page_start = ( ( $page - 1 ) * $per_page ) + 1;
	$current_page_end   = $page * $per_page > intval( $query->total_users ) ? $query->total_users : $page * $per_page;

	$pag_links = paginate_links(
		array(
			'base'      => add_query_arg( $qs_key, '%#%', $url_base ),
			'format'    => '',
			'prev_text' => __( '&laquo;', 'buddyboss' ),
			'next_text' => __( '&raquo;', 'buddyboss' ),
			'total'     => ceil( $query->total_users / $per_page ),
			'current'   => $page,
		)
	);

	$viewing_text = sprintf(
		_n( 'Viewing 1 member', 'Viewing %1$s - %2$s of %3$s members', $query->total_users, 'buddyboss' ),
		bp_core_number_format( $current_page_start ),
		bp_core_number_format( $current_page_end ),
		bp_core_number_format( $query->total_users )
	);

	$pagination .= '<span class="bp-group-admin-pagination-viewing">' . $viewing_text . '</span>';
	$pagination .= '<span class="bp-group-admin-pagination-links">' . $pag_links . '</span>';

	return $pagination;
}

/**
 * Get a set of usernames corresponding to a set of user IDs.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $user_ids Array of user IDs.
 * @return array Array of user_logins corresponding to $user_ids.
 */
function bp_groups_admin_get_usernames_from_ids( $user_ids = array() ) {

	$usernames = array();
	$users     = new WP_User_Query(
		array(
			'blog_id' => 0,
			'include' => $user_ids,
		)
	);

	foreach ( (array) $users->results as $user ) {
		$usernames[] = $user->user_login;
	}

	return $usernames;
}

/**
 * AJAX handler for group member autocomplete requests.
 *
 * @since BuddyPress 1.7.0
 */
function bp_groups_admin_autocomplete_handler() {

	// Bail if user user shouldn't be here, or is a large network.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_die( -1 );
	}

	$term     = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
	$group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;

	if ( ! $term || ! $group_id ) {
		wp_die( -1 );
	}

	$suggestions = bp_core_get_suggestions(
		array(
			'group_id' => -$group_id,  // A negative value will exclude this group's members from the suggestions.
			'limit'    => 10,
			'term'     => $term,
			'type'     => 'members',
		)
	);

	$matches = array();

	if ( $suggestions && ! is_wp_error( $suggestions ) ) {
		foreach ( $suggestions as $user ) {

			$matches[] = array(
				// Translators: 1: user_login, 2: user_email.
				'label' => sprintf( __( '%1$s (%2$s)', 'buddyboss' ), $user->name, $user->ID ),
				'value' => $user->user_nicename,
			);
		}
	}

	wp_die( json_encode( $matches ) );
}
add_action( 'wp_ajax_bp_group_admin_member_autocomplete', 'bp_groups_admin_autocomplete_handler' );

// Legacy group type CPT admin functions removed — now handled by Settings 2.0.
// See class-bb-admin-groups-ajax.php for Settings 2.0 group type AJAX handlers.

/**
 * Filter groups query arguments to unset hidden param.
 *
 * @since BuddyBoss 2.5.40
 *
 * @param array $r array of arguments.
 *
 * @return array
 */
function bb_groups_group_get_parse_args( $r ) {

	$r['show_hidden'] = false;

	return $r;
}

/**
 * Filter groups query to exclude hidden query.
 *
 * @since BuddyBoss 2.5.40
 *
 * @param array $where_conditions Where conditions SQL statement.
 * @param array $r                Array of parsed arguments for the get method.
 *
 * @return array
 */
function bb_groups_get_where_conditions( $where_conditions, $r ) {

	if ( isset( $where_conditions['hidden'] ) ) {
		unset( $where_conditions['hidden'] );
	}

	return $where_conditions;
}
