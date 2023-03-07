<?php
/**
 * Members functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the nav items for the Members directory
 *
 * @since BuddyPress 3.0.0
 *
 * @return array An associative array of nav items.
 */
function bp_nouveau_get_members_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'members',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array(),
		'link'      => bp_get_members_directory_permalink(),
		'text'      => __( 'All Members', 'buddyboss' ),
		'count'     => bp_core_get_all_member_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() ) {
		// If friends component is active and the user has friends
		if ( bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) {
			$nav_items['personal'] = array(
				'component' => 'members',
				'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array(),
				'link'      => bp_loggedin_user_domain() . bp_get_friends_slug() . '/my-friends/',
				'text'      => __( 'My Connections', 'buddyboss' ),
				'count'     => bp_get_total_friend_count( bp_loggedin_user_id() ),
				'position'  => 15,
			);
		}

		// If follow component is active and the user is following
		if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
			$counts = bp_total_follow_counts();

			if ( ! empty( $counts['following'] ) ) {
				$nav_items['following'] = array(
					'component' => 'members',
					'slug'      => 'following', // slug is used because BP_Core_Nav requires it, but it's the scope
					'li_class'  => array(),
					'link'      => bp_loggedin_user_domain() . bp_get_follow_slug() . '/my-following/',
					'text'      => __( 'Following', 'buddyboss' ),
					'count'     => $counts['following'],
					'position'  => 16,
				);
			}
		}
	}

	// Check for the deprecated hook :
	$extra_nav_items = bp_nouveau_parse_hooked_dir_nav( 'bp_members_directory_member_types', 'members', 20 );
	if ( ! empty( $extra_nav_items ) ) {
		$nav_items = array_merge( $nav_items, $extra_nav_items );
	}

	/**
	 * Use this filter to introduce your custom nav items for the members directory.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $nav_items The list of the members directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_members_directory_nav_items', $nav_items );
}

/**
 * Get Dropdown filters for the members component
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $context Optional.
 *
 * @return array the filters
 */
function bp_nouveau_get_members_filters( $context = '' ) {
	if ( 'group' !== $context ) {
		$filters = array(
			'active' => __( 'Recently Active', 'buddyboss' ),
			'newest' => __( 'Newest Members', 'buddyboss' ),
		);

		if ( bp_is_active( 'xprofile' ) ) {
			$filters['alphabetical'] = __( 'Alphabetical', 'buddyboss' );
		}

		$action = 'bp_members_directory_order_options';

		if ( 'friends' === $context ) {
			$action = 'bp_member_friends_order_options';
		}
	} else {
		$filters = array(
			'last_joined'  => __( 'Newest', 'buddyboss' ),
			'first_joined' => __( 'Oldest', 'buddyboss' ),
		);

		if ( bp_is_active( 'activity' ) ) {
			$filters['group_activity'] = __( 'Group Activity', 'buddyboss' );
		}

		$filters['alphabetical'] = __( 'Alphabetical', 'buddyboss' );
		$action                  = 'bp_groups_members_order_options';
	}

	/**
	 * Recommended, filter here instead of adding an action to 'bp_members_directory_order_options'
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array  the members filters.
	 * @param string the context.
	 */
	$filters = apply_filters( 'bp_nouveau_get_members_filters', $filters, $context );

	return bp_nouveau_parse_hooked_options( $action, $filters );
}

/**
 * Catch the arguments for buttons
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $buttons The arguments of the button that BuddyPress is about to create.
 *
 * @return array An empty array to stop the button creation process.
 */
function bp_nouveau_members_catch_button_args( $button = array() ) {
	/*
	 * Globalize the arguments so that we can use it
	 * in bp_nouveau_get_member_header_buttons().
	 */
	bp_nouveau()->members->button_args = $button;

	// return an empty array to stop the button creation process
	return array();
}

/**
 * Catch the content hooked to the do_action hooks in single member header
 * and in the members loop.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string|false HTML Output if hooked. False otherwise.
 */
function bp_nouveau_get_hooked_member_meta() {
	ob_start();

	if ( ! empty( $GLOBALS['members_template'] ) ) {
		/**
		 * Fires inside the display of a directory member item.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_directory_members_item' );

	// It's the user's header
	} else {
		/**
		 * Fires after the group header actions section.
		 *
		 * If you'd like to show specific profile fields here use:
		 * bp_member_profile_data( 'field=About Me' ); -- Pass the name of the field
		 *
		 * @since BuddyPress 1.2.0
		 */
		do_action( 'bp_profile_header_meta' );
	}

	$output = ob_get_clean();

	if ( ! empty( $output ) ) {
		return $output;
	}

	return false;
}

/**
 * Adds a 'Dashbaord' link in admin bar.
 *
 * @global \WP_Admin_Bar $wp_admin_bar
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $wp_admin_nav
 * @return void
 */
function bp_nouveau_admin_bar_member_dashboard ( $wp_admin_nav = array() ) {
    if ( !bp_loggedin_user_id() ) {
        return false;
    }

	if ( bp_nouveau_get_appearance_settings( 'user_front_page' ) ) {

		$page_ids = bp_core_get_directory_page_ids();

		$profile_dashboard   = isset( $page_ids['profile_dashboard'] ) ? $page_ids['profile_dashboard'] : false;

		if ( $profile_dashboard > 0 ) {
			$dashboard_link = get_permalink( $profile_dashboard );

			// Add main Dashboard menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-front',
				'title'  => __( 'Dashboard', 'buddyboss' ),
				'href'   => $dashboard_link,
			);

			// View sub menu.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-front',
				'id'       => 'my-account-front-view',
				'title'    => __( 'View', 'buddyboss' ),
				'href'     => $dashboard_link,
				'position' => 10
			);

			// Define the WordPress global.
			global $wp_admin_bar;

			// Add each admin menu.
			foreach ( $wp_admin_nav as $admin_menu ) {
				$wp_admin_bar->add_menu( $admin_menu );
			}
		}
	}
}

/**
 * Locate a single member template into a specific hierarchy.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $template The template part to get (eg: activity, groups...).
 *
 * @return string The located template.
 */
function bp_nouveau_member_locate_template_part( $template = '' ) {
	$displayed_user = bp_get_displayed_user();
	$bp_nouveau     = bp_nouveau();

	if ( ! $template || empty( $displayed_user->id ) ) {
		return '';
	}

	// Use a global to avoid requesting the hierarchy for each template
	if ( ! isset( $bp_nouveau->members->displayed_user_hierarchy ) ) {
		$bp_nouveau->members->displayed_user_hierarchy = array(
			'members/single/%s-id-' . (int) $displayed_user->id . '.php',
			'members/single/%s-nicename-' . sanitize_file_name( $displayed_user->userdata->user_nicename ) . '.php',
		);

		/*
		 * Check for profile types and add it to the hierarchy
		 *
		 * Make sure to register your member
		 * type using the hook 'bp_register_member_types'
		 */
		if ( bp_get_member_types() ) {
			$displayed_user_member_type = bp_get_member_type( $displayed_user->id );
			if ( ! $displayed_user_member_type ) {
				$displayed_user_member_type = 'none';
			}

			$bp_nouveau->members->displayed_user_hierarchy[] = 'members/single/%s-member-type-' . sanitize_file_name( $displayed_user_member_type ) . '.php';
		}

		// And the regular one
		$bp_nouveau->members->displayed_user_hierarchy[] = 'members/single/%s.php';
	}

	$templates = array();

	// Loop in the hierarchy to fill it for the requested template part
	foreach ( $bp_nouveau->members->displayed_user_hierarchy as $part ) {
		$templates[] = sprintf( $part, $template );
	}

	/**
	 * Filters the found template parts for the member template part locating functionality.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $templates Array of found templates.
	 */
	return bp_locate_template( apply_filters( 'bp_nouveau_member_locate_template_part', $templates ), false, true );
}

/**
 * Load a single member template part
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $template The template part to get (eg: activity, groups...).
 *
 * @return string HTML output.
 */
function bp_nouveau_member_get_template_part( $template = '' ) {
	$located = bp_nouveau_member_locate_template_part( $template );

	if ( false !== $located ) {
		$slug = str_replace( '.php', '', $located );
		$name = null;

		/**
		 * Let plugins adding an action to bp_get_template_part get it from here.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $slug Template part slug requested.
		 * @param string $name Template part name requested.
		 */
		do_action( 'get_template_part_' . $slug, $slug, $name );

		load_template( $located, true );
	}

	return $located;
}

/**
 * Are we inside the Current user's default front page sidebar?
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if in the group's home sidebar. False otherwise.
 */
function bp_nouveau_member_is_home_widgets() {
	return ( true === bp_nouveau()->members->is_user_home_sidebar );
}

/**
 * Filter the Latest activities Widget to only keep the one of displayed user
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args The Activities Template arguments.
 *
 * @return array The Activities Template arguments.
 */
function bp_nouveau_member_activity_widget_overrides( $args = array() ) {
	return array_merge( $args, array(
		'user_id' => bp_displayed_user_id(),
	) );
}

/**
 * Filter the Groups widget to only keep the groups the displayed user is a member of.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args The Groups Template arguments.
 *
 * @return array The Groups Template arguments.
 */
function bp_nouveau_member_groups_widget_overrides( $args = array() ) {
	return array_merge( $args, array(
		'user_id' => bp_displayed_user_id(),
	) );
}

/**
 * Filter the Members widgets to only keep members of the displayed group.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args The Members Template arguments.
 *
 * @return array The Members Template arguments.
 */
function bp_nouveau_member_members_widget_overrides( $args = array() ) {
	// Do nothing for the friends widget
	if ( ! empty( $args['user_id'] ) && (int) $args['user_id'] === (int) bp_displayed_user_id() ) {
		return $args;
	}

	return array_merge( $args, array(
		'include' => bp_displayed_user_id(),
	) );
}

/**
 * Init the Member's default front page filters as we're in the sidebar
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_members_add_home_widget_filters() {
	add_filter( 'bp_nouveau_activity_widget_query', 'bp_nouveau_member_activity_widget_overrides', 10, 1 );
	add_filter( 'bp_before_has_groups_parse_args', 'bp_nouveau_member_groups_widget_overrides', 10, 1 );
	add_filter( 'bp_before_has_members_parse_args', 'bp_nouveau_member_members_widget_overrides', 10, 1 );

	/**
	 * Fires after Nouveau adds its members home widget filters.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_nouveau_members_add_home_widget_filters' );
}

/**
 * Remove the Member's default front page filters as we're no more in the sidebar
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_members_remove_home_widget_filters() {
	remove_filter( 'bp_nouveau_activity_widget_query', 'bp_nouveau_member_activity_widget_overrides', 10, 1 );
	remove_filter( 'bp_before_has_groups_parse_args', 'bp_nouveau_member_groups_widget_overrides', 10, 1 );
	remove_filter( 'bp_before_has_members_parse_args', 'bp_nouveau_member_members_widget_overrides', 10, 1 );

	/**
	 * Fires after Nouveau removes its members home widget filters.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_nouveau_members_remove_home_widget_filters' );
}

/**
 * Get the WP Profile fields for all or a specific user
 *
 * @since BuddyPress 3.0.0
 *
 * @param WP_User $user The user object. Optional.
 *
 * @return array The list of WP Profile fields
 */
function bp_nouveau_get_wp_profile_fields( $user = null ) {
	/**
	 * Filters the contact methods to be included in the WP Profile fields for a specific user.
	 *
	 * Provide a chance for plugins to avoid showing the contact methods they're adding on front end.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array   $value Array of user contact methods.
	 * @param WP_User $user  WordPress user to get contact methods for.
	 */
	$contact_methods = (array) apply_filters( 'bp_nouveau_get_wp_profile_field', wp_get_user_contact_methods( $user ), $user );

	$wp_fields = array(
		'display_name'     => __( 'Name', 'buddyboss' ),
		'user_description' => __( 'About Me', 'buddyboss' ),
		'user_url'         => __( 'Website', 'buddyboss' ),
	);

	return array_merge( $wp_fields, $contact_methods );
}

/**
 * Build the Member's nav for the our customizer control.
 *
 * @since BuddyPress 3.0.0
 *
 * @return array The Members single item primary nav ordered.
 */
function bp_nouveau_member_customizer_nav() {

	$nav = buddypress()->members->nav;

	// Eventually reset the order.
	bp_nouveau_set_nav_item_order( $nav, bp_nouveau_get_appearance_settings( 'user_nav_order' ) );

	return $nav->get_primary();
}

/**
 * Enqueue the members scripts
 *
 * @since BuddyBoss 2.2.6
 */
function bp_nouveau_member_enqueue_scripts() {
	if ( ! bp_is_user_settings() ) {
		return;
	}

	if ( bp_is_user_settings_notifications() && bp_action_variables() && 'subscriptions' === bp_action_variable( 0 ) ) {
		wp_enqueue_script( 'bb-subscriptions' );
	}
}

/**
 * Register Scripts for the Member component
 *
 * @since BuddyBoss 2.2.6
 *
 * @param array $scripts The array of scripts to register.
 *
 * @return array The same array with the specific messages scripts.
 */
function bp_nouveau_member_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge(
		$scripts,
		array(
			'bb-subscriptions' => array(
				'file'         => 'js/bb-subscriptions%s.js',
				'dependencies' => array( 'bp-nouveau', 'json2', 'wp-backbone', 'bp-api-request' ),
				'footer'       => true,
			),
		)
	);
}

/**
 * Localize the strings needed for the Member UI
 *
 * @since BuddyBoss 2.2.6
 *
 * @param array $params Associative array containing the JS Strings needed by scripts.
 *
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_member_localize_scripts( $params = array() ) {
	if ( ! bp_is_user_settings() && ! function_exists( 'bp_is_groups_component' ) ) {
		return $params;
	}

	if (
		(
			bp_is_user_settings_notifications() &&
			bp_action_variables() &&
			'subscriptions' === bp_action_variable( 0 )
		) ||
		(
			bp_is_groups_component() &&
			bp_is_group()
		)
	) {
		$params['subscriptions'] = array(
			'unsubscribe'     => __( 'You\'ve been unsubscribed from ', 'buddyboss' ),
			'error'           => __( 'There was a problem unsubscribing from ', 'buddyboss' ),
			'per_page'        => apply_filters( 'bb_subscriptions_per_page', 5 ),
			'no_result'       => __( 'You are not currently subscribed to any %s.', 'buddyboss' ),
			'subscribe_error' => __( 'There was a problem subscribing to ', 'buddyboss' ),
		);
	}

	return $params;
}
