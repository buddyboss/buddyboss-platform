<?php
/**
 * Common template tags
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fire specific hooks at various places of templates
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $pieces The list of terms of the hook to join.
 */
function bp_nouveau_hook( $pieces = array() ) {
	if ( ! $pieces ) {
		return;
	}

	$bp_prefix = reset( $pieces );
	if ( 'bp' !== $bp_prefix ) {
		array_unshift( $pieces, 'bp' );
	}

	$hook = join( '_', $pieces );

	/**
	 * Fires inside the `bp_nouveau_hook()` function.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( $hook );
}

/**
 * Fire plugin hooks in the plugins.php template (Groups and Members single items)
 *
 * @since BuddyPress 3.0.0
 *
 * @param string The suffix of the hook.
 */
function bp_nouveau_plugin_hook( $suffix = '' ) {
	if ( ! $suffix ) {
		return;
	}

	bp_nouveau_hook(
		array(
			'bp',
			'template',
			$suffix,
		)
	);
}

/**
 * Fire friend hooks
 *
 * @todo Move this into bp-nouveau/includes/friends/template-tags.php
 *       once we'll need other friends template tags.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string The suffix of the hook.
 */
function bp_nouveau_friend_hook( $suffix = '' ) {
	if ( ! $suffix ) {
		return;
	}

	bp_nouveau_hook(
		array(
			'bp',
			'friend',
			$suffix,
		)
	);
}

/**
 * Add classes to style the template notice/feedback message
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_template_message_classes() {
	$classes = array( 'bp-feedback', 'bp-messages' );

	if ( ! empty( bp_nouveau()->template_message['message'] ) ) {
		$classes[] = 'bp-template-notice';
	}

	$classes[] = bp_nouveau_get_template_message_type();
	echo join( ' ', array_map( 'sanitize_html_class', $classes ) );
}

	/**
	 * Get the template notice/feedback message type
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string The type of the notice. Defaults to error.
	 */
function bp_nouveau_get_template_message_type() {
	$bp_nouveau = bp_nouveau();
	$type       = 'error';

	if ( ! empty( $bp_nouveau->template_message['type'] ) ) {
		$type = $bp_nouveau->template_message['type'];
	} elseif ( ! empty( $bp_nouveau->user_feedback['type'] ) ) {
		$type = $bp_nouveau->user_feedback['type'];
	}

	return $type;
}

/**
 * Checks if a template notice/feedback message is set
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if a template notice is set. False otherwise.
 */
function bp_nouveau_has_template_message() {
	$bp_nouveau = bp_nouveau();

	if ( empty( $bp_nouveau->template_message['message'] ) && empty( $bp_nouveau->user_feedback ) ) {
		return false;
	}

	return true;
}

/**
 * Checks if the template notice/feedback message needs a dismiss button
 *
 * @todo Dismiss button re-worked to try and prevent buttons on general
 *       BP template notices - Nouveau user_feedback key needs review.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if a template notice needs a dismiss button. False otherwise.
 */
function bp_nouveau_has_dismiss_button() {
	$bp_nouveau = bp_nouveau();

	// BP template notices - set 'dismiss' true for a type in `bp_nouveau_template_notices()`
	if ( ! empty( $bp_nouveau->template_message['message'] ) && true === $bp_nouveau->template_message['dismiss'] ) {
		return true;
	}

	// Test for isset as value can be falsey.
	if ( isset( $bp_nouveau->user_feedback['dismiss'] ) ) {
		return true;
	}

	return false;
}

/**
 * Output the dismiss type.
 *
 * $type is used to set the data-attr for the button.
 * 'clear' is tested for & used to remove cookies, if set, in buddypress-nouveau.js.
 * Currently template_notices(BP) will take $type = 'clear' if button set to true.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_dismiss_button_type() {
	$bp_nouveau = bp_nouveau();
	$type       = 'clear';

	if ( ! empty( $bp_nouveau->user_feedback['dismiss'] ) ) {
		$type = $bp_nouveau->user_feedback['dismiss'];
	}

	echo esc_attr( $type );
}

/**
 * Displays a template notice/feedback message.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_template_message() {
	echo bp_nouveau_get_template_message();
}

	/**
	 * Get the template notice/feedback message and make sure core filter is applied.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string HTML Output.
	 */
function bp_nouveau_get_template_message() {
	$bp_nouveau = bp_nouveau();

	if ( ! empty( $bp_nouveau->user_feedback['message'] ) ) {
		$user_feedback = $bp_nouveau->user_feedback['message'];

		// @TODO: why is this treated differently?
		foreach ( array( 'wp_kses_data', 'wp_unslash', 'wptexturize', 'convert_smilies', 'convert_chars' ) as $filter ) {
			$user_feedback = call_user_func( $filter, $user_feedback );
		}

		return '<p>' . $user_feedback . '</p>';

	} elseif ( ! empty( $bp_nouveau->template_message['message'] ) ) {
		/**
		 * Filters the 'template_notices' feedback message content.
		 *
		 * @since BuddyPress 1.5.5
		 *
		 * @param string $template_message Feedback message content.
		 * @param string $type             The type of message being displayed.
		 *                                 Either 'updated' or 'error'.
		 */
		return apply_filters( 'bp_core_render_message_content', $bp_nouveau->template_message['message'], bp_nouveau_get_template_message_type() );
	}
}

/**
 * Template tag to display feedback notices to users, if there are to display
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_template_notices() {
	$bp         = buddypress();
	$bp_nouveau = bp_nouveau();

	if ( ! empty( $bp->template_message ) ) {
		// Clone BuddyPress template message to avoid altering it.
		$template_message = array( 'message' => $bp->template_message );

		if ( ! empty( $bp->template_message_type ) ) {
			$template_message['type'] = $bp->template_message_type;
		}

		// Adds a 'dimiss' (button) key to array - set true/false.
		$template_message['dismiss'] = false;

		// Set dismiss button true for sitewide notices
		if ( 'bp-sitewide-notice' == $template_message['type'] ) {
			$template_message['dismiss'] = true;
		}

		$bp_nouveau->template_message = $template_message;
		bp_get_template_part( 'common/notices/template-notices' );

		// Reset just after rendering it.
		$bp_nouveau->template_message = array();
		$bp->template_message         = '';

		/**
		 * Fires after the display of any template_notices feedback messages.
		 *
		 * @since BuddyPress 3.0.0
		 */
		do_action( 'bp_core_render_message' );
	}

	/**
	 * Fires towards the top of template pages for notice display.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'template_notices' );
}

/**
 * Displays a feedback message to the user.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $feedback_id The ID of the message to display
 */
function bp_nouveau_user_feedback( $feedback_id = '' ) {
	if ( ! isset( $feedback_id ) ) {
		return;
	}

	$bp_nouveau = bp_nouveau();
	$feedback   = bp_nouveau_get_user_feedback( $feedback_id );

	if ( ! $feedback ) {
		return;
	}

	if ( ! empty( $feedback['before'] ) ) {

		/**
		 * Fires before display of a feedback message to the user.
		 *
		 * This is a dynamic filter that is dependent on the "before" value provided by bp_nouveau_get_user_feedback().
		 *
		 * @since BuddyPress 3.0.0
		 */
		do_action( $feedback['before'] );
	}

	$bp_nouveau->user_feedback = $feedback;

	bp_get_template_part(
		/**
		 * Filter here if you wish to use a different templates than the notice one.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string path to your template part.
		 */
		apply_filters( 'bp_nouveau_user_feedback_template', 'common/notices/template-notices' )
	);

	if ( ! empty( $feedback['after'] ) ) {

		/**
		 * Fires before display of a feedback message to the user.
		 *
		 * This is a dynamic filter that is dependent on the "after" value provided by bp_nouveau_get_user_feedback().
		 *
		 * @since BuddyPress 3.0.0
		 */
		do_action( $feedback['after'] );
	}

	// Reset the feedback message.
	$bp_nouveau->user_feedback = array();
}

/**
 * Template tag to wrap the before component loop
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_before_loop() {
	$component = bp_current_component();

	if ( bp_is_group() ) {
		$component = bp_current_action();
	}

	/**
	 * Fires before the start of the component loop.
	 *
	 * This is a variable hook that is dependent on the current component.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( "bp_before_{$component}_loop" );
}

/**
 * Template tag to wrap the after component loop
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_after_loop() {
	$component = bp_current_component();

	if ( bp_is_group() ) {
		$component = bp_current_action();
	}

	/**
	 * Fires after the finish of the component loop.
	 *
	 * This is a variable hook that is dependent on the current component.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( "bp_after_{$component}_loop" );
}

/**
 * Pagination for loops
 *
 * @param string $position
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_pagination( $position ) {
	$screen          = 'dir';
	$pagination_type = bp_current_component();

	if ( bp_is_user() ) {
		$screen = 'user';

	} elseif ( bp_is_group() ) {
		$screen          = 'group';
		$pagination_type = bp_current_action();

		if ( bp_is_group_admin_page() ) {
			$pagination_type = bp_action_variable( 0 );
		}
	}

	if ( 'subgroups' === $pagination_type ) {
		$pagination_type = 'groups';
	}

	// Set default values.
	$page_arg  = '';
	$pag_count = '';
	$pag_links = '';

	switch ( $pagination_type ) {
		case 'blogs':
			$pag_count   = bp_get_blogs_pagination_count();
			$pag_links   = bp_get_blogs_pagination_links();
			$top_hook    = 'bp_before_directory_blogs_list';
			$bottom_hook = 'bp_after_directory_blogs_list';
			$page_arg    = $GLOBALS['blogs_template']->pag_arg;
			break;

		case 'members':
		case 'friends':
		case 'manage-members':
			$pag_count = bp_get_members_pagination_count();
			$pag_links = bp_get_members_pagination_links();

			// Groups single items are not using these hooks
			if ( ! bp_is_group() ) {
				$top_hook    = 'bp_before_directory_members_list';
				$bottom_hook = 'bp_after_directory_members_list';
			}

			$page_arg = $GLOBALS['members_template']->pag_arg;
			break;

		case 'groups':
			$pag_count   = bp_get_groups_pagination_count();
			$pag_links   = bp_get_groups_pagination_links();
			$top_hook    = 'bp_before_directory_groups_list';
			$bottom_hook = 'bp_after_directory_groups_list';
			$page_arg    = $GLOBALS['groups_template']->pag_arg;
			break;

		case 'notifications':
			$pag_count   = bp_get_notifications_pagination_count();
			$pag_links   = bp_get_notifications_pagination_links();
			$top_hook    = '';
			$bottom_hook = '';
			$page_arg    = buddypress()->notifications->query_loop->pag_arg;
			break;

		case 'membership-requests':
			$pag_count   = bp_get_group_requests_pagination_count();
			$pag_links   = bp_get_group_requests_pagination_links();
			$top_hook    = '';
			$bottom_hook = '';
			$page_arg    = $GLOBALS['requests_template']->pag_arg;
			break;
	}

	$count_class = sprintf( '%1$s-%2$s-count-%3$s', $pagination_type, $screen, $position );
	$links_class = sprintf( '%1$s-%2$s-links-%3$s', $pagination_type, $screen, $position );
	?>

	<?php
	if ( 'bottom' === $position && isset( $bottom_hook ) ) {
		/**
		 * Fires after the component directory list.
		 *
		 * @since BuddyPress 3.0.0
		 */
		do_action( $bottom_hook );
	};
	?>

	<div class="<?php echo esc_attr( 'bp-pagination ' . sanitize_html_class( $position ) ); ?>" data-bp-pagination="<?php echo esc_attr( $page_arg ); ?>">

		<?php if ( $pag_count ) : ?>
			<div class="<?php echo esc_attr( 'pag-count ' . sanitize_html_class( $position ) ); ?>">

				<p class="pag-data">
					<?php echo esc_html( $pag_count ); ?>
				</p>

			</div>
		<?php endif; ?>

		<?php if ( $pag_links ) : ?>
			<div class="<?php echo esc_attr( 'bp-pagination-links ' . sanitize_html_class( $position ) ); ?>">

				<p class="pag-data">
					<?php echo wp_kses_post( $pag_links ); ?>
				</p>

			</div>
		<?php endif; ?>

	</div>

	<?php
	if ( 'top' === $position && isset( $top_hook ) ) {
		/**
		 * Fires before the component directory list.
		 *
		 * @since BuddyPress 3.0.0
		 */
		do_action( $top_hook );
	};
}

/**
 * Display the component's loop classes
 *
 * @since BuddyPress 3.0.0
 *
 * @return string CSS class attributes (escaped).
 */
function bp_nouveau_loop_classes() {
	echo esc_attr( bp_nouveau_get_loop_classes() );
}

/**
 * Get the component's loop classes
 *
 * @since BuddyPress 3.0.0
 *
 * @return string space separated value of classes.
 */
function bp_nouveau_get_loop_classes() {
	// @todo: this function could do with passing args so we can pass simple strings in or array of strings

	// The $component is faked if it's the single group member loop
	if ( ! bp_is_directory() && ( bp_is_group() && 'members' === bp_current_action() ) ) {
		$component = 'members_group';
	} elseif ( ! bp_is_directory() && ( bp_is_user() && ( 'my-friends' === bp_current_action() || 'mutual' === bp_current_action() ) ) ) {
		$component = 'members_friends';
	} else {
		$component = sanitize_key( bp_current_component() );
	}

	$classes = array(
		'item-list',
		sprintf( '%s-list', str_replace( '_', '-', $component ) ),
		'bp-list',
	);

	if ( bp_is_user() && 'my-friends' === bp_current_action() ) {
		$classes[] = 'members-list';
	}

	if ( bp_is_user() && 'requests' === bp_current_action() ) {
		$classes[] = 'friends-request-list';
	}

	if ( bp_is_user() && 'mutual' === bp_current_action() ) {
		$classes[] = 'friends-mutual-list';
	}

	$available_components = array(
		'members'         => true,
		'groups'          => true,
		'blogs'           => true,

		/*
		 * Technically not a component but allows us to check the single group members loop as a seperate loop.
		 */
		'members_group'   => true,
		'members_friends' => true,
	);

	// Only the available components supports custom layouts.
	if ( ! empty( $available_components[ $component ] ) && ( bp_is_directory() || bp_is_group() || bp_is_user() || bp_is_groups_directory() ) ) {

		// check for layout options in browsers storage
		if ( bp_is_members_directory() || bp_is_user() ) {
			if ( ! bp_is_user_groups() ) {
				$current_value = bp_get_option( 'bp-profile-layout-format' );
			} else {
				$current_value = bp_get_option( 'bp-group-layout-format' );
			}
		} elseif ( bp_is_groups_directory() || bp_is_group() ) {
			if ( ! bp_is_user_groups() && ! bp_is_groups_directory() ) {
				$current_value = bp_get_option( 'bp-profile-layout-format' );
			} else {
				$current_value = bp_get_option( 'bp-group-layout-format' );
			}
		}

		if ( 'list_grid' === $current_value ) {
			if ( bp_is_members_directory() || bp_is_user() ) {
				if ( ! bp_is_user_groups() ) {
					$default_current_value = bb_get_directory_layout_preference( 'members' );
				} else {
					$default_current_value = bb_get_directory_layout_preference( 'groups' );
				}
			} elseif ( bp_is_groups_directory() || bp_is_group() ) {
				if ( ! bp_is_user_groups() && ! bp_is_groups_directory() ) {
					$default_current_value = bb_get_directory_layout_preference( 'members' );
				} else {
					$default_current_value = bb_get_directory_layout_preference( 'groups' );
				}
			} else {
				$default_current_value = bb_get_directory_layout_preference( 'groups' );
			}
			if ( bp_is_group() && 'members' === bp_current_action() ) {
				$default_current_value = bb_get_directory_layout_preference( 'members' );
			}
			$classes = array_merge(
				$classes,
				array(
					$default_current_value,
				)
			);
		} elseif ( 'list' === $current_value ) {
			$classes = array_merge(
				$classes,
				array(
					'list',
				)
			);
		} else {
			$classes = array_merge(
				$classes,
				array(
					'grid',
				)
			);
		}
	}

	/**
	 * Filter to edit/add classes.
	 *
	 * NB: you can also directly add classes into the template parts.
	 *
	 * @param array $classes The list of classes.
	 * @param string $component The current component's loop.
	 *
	 * @since BuddyPress 3.0.0
	 */
	$class_list = (array) apply_filters( 'bp_nouveau_get_loop_classes', $classes, $component );

	return join( ' ', array_map( 'sanitize_html_class', $class_list ) );
}


/**
 * Checks if the layout preferences is set to grid (2 or more columns).
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if loop is displayed in grid mod. False otherwise.
 */
function bp_nouveau_loop_is_grid() {
	$bp_nouveau = bp_nouveau();
	$component  = sanitize_key( bp_current_component() );

	return ! empty( $bp_nouveau->{$component}->loop_layout ) && $bp_nouveau->{$component}->loop_layout > 1;
}

/**
 * Returns the number of columns of the layout preferences.
 *
 * @since BuddyPress 3.0.0
 *
 * @return int The number of columns.
 */
function bp_nouveau_loop_get_grid_columns() {
	$bp_nouveau = bp_nouveau();
	$component  = sanitize_key( bp_current_component() );

	$columns = 1;

	if ( ! empty( $bp_nouveau->{$component}->loop_layout ) ) {
		$columns = (int) $bp_nouveau->{$component}->loop_layout;
	}

	/**
	 * Filter number of columns for this grid.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param int $columns The number of columns.
	 */
	return (int) apply_filters( 'bp_nouveau_loop_get_grid_columns', $columns );
}

/**
 * Return a bool check for component directory layout.
 *
 * Checks if activity, members, groups, blogs has the vert nav layout selected.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool
 */
function bp_dir_is_vert_layout() {
	$bp_nouveau = bp_nouveau();
	$component  = sanitize_key( bp_current_component() );

	return (bool) $bp_nouveau->{$component}->directory_vertical_layout;
}

/**
 * Get the full size avatar args.
 *
 * @since BuddyPress 3.0.0
 *
 * @return array The avatar arguments.
 */
function bp_nouveau_avatar_args() {
	/**
	 * Filter arguments for full-size avatars.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $args {
	 *     @param string $type   Avatar type.
	 *     @param int    $width  Avatar width value.
	 *     @param int    $height Avatar height value.
	 * }
	 */
	return apply_filters(
		'bp_nouveau_avatar_args',
		array(
			'type'   => 'full',
			'width'  => bp_core_avatar_full_width(),
			'height' => bp_core_avatar_full_height(),
		)
	);
}


/** Template Tags for BuddyPress navigations **********************************/

/*
 * This is the BP Nouveau Navigation Loop.
 *
 * It can be used by any object using the
 * BP_Core_Nav API introduced in BuddyPress 2.6.0.
 */

/**
 * Init the Navigation Loop and check it has items.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *
 *     @type string $type                    The type of Nav to get (primary or secondary)
 *                                           Default 'primary'. Required.
 *     @type string $object                  The object to get the nav for (eg: 'directory', 'group_manage',
 *                                           or any custom object). Default ''. Optional
 *     @type bool   $user_has_access         Used by the secondary member's & group's nav. Default true. Optional.
 *     @type bool   $show_for_displayed_user Used by the primary member's nav. Default true. Optional.
 * }
 *
 * @return bool True if the Nav contains items. False otherwise.
 */
function bp_nouveau_has_nav( $args = array() ) {
	$bp_nouveau = bp_nouveau();

	$n = bp_parse_args(
		$args,
		array(
			'type'                    => 'primary',
			'object'                  => '',
			'user_has_access'         => true,
			'show_for_displayed_user' => true,
		)
	);

	if ( empty( $n['type'] ) ) {
		return false;
	}

	$nav                       = array();
	$bp_nouveau->displayed_nav = '';
	$bp_nouveau->object_nav    = $n['object'];

	if ( bp_is_directory() || 'directory' === $bp_nouveau->object_nav ) {
		$bp_nouveau->displayed_nav = 'directory';
		$nav                       = $bp_nouveau->directory_nav->get_primary();

		// So far it's only possible to build a Group nav when displaying it.
	} elseif ( bp_is_group() ) {
		$bp_nouveau->displayed_nav = 'groups';
		$parent_slug               = bp_get_current_group_slug();
		$group_nav                 = buddypress()->groups->nav;

		if ( 'group_manage' === $bp_nouveau->object_nav && bp_is_group_admin_page() ) {
			$parent_slug .= '_manage';
		} elseif ( 'group_messages' === $bp_nouveau->object_nav && bp_is_group_messages() ) {
			$parent_slug .= '_messages';
		} elseif ( 'group_invite' === $bp_nouveau->object_nav && bp_is_group_invites() ) {
			$parent_slug .= '_invite';
		} elseif ( 'group_media' === $bp_nouveau->object_nav && bp_is_group_media() ) {
			$parent_slug .= '_media';
		} elseif ( 'group_members' === $bp_nouveau->object_nav && bp_is_group_members() ) {
			$parent_slug .= '_members';

			/**
			 * If it's not the Admin tabs, reorder the Group's nav according to the
			 * customizer setting.
			 */
		} else {
			bp_nouveau_set_nav_item_order( $group_nav, bp_nouveau_get_appearance_settings( 'group_nav_order' ), $parent_slug );
		}

		$nav = $group_nav->get_secondary(
			array(
				'parent_slug'     => apply_filters( 'bp_nouveau_group_secondary_nav_parent_slug', $parent_slug ),
				'user_has_access' => (bool) $n['user_has_access'],
			)
		);

		// Build the nav for the displayed user.
	} elseif ( bp_is_user() ) {
		$bp_nouveau->displayed_nav = 'personal';
		$parent_slug               = bp_current_component();
		$user_nav                  = buddypress()->members->nav;

		if ( 'account_notifications' === $bp_nouveau->object_nav ) {
			$parent_slug .= '_notifications';
		}

		if ( 'secondary' === $n['type'] ) {
			$nav = $user_nav->get_secondary(
				array(
					'parent_slug'     => $parent_slug,
					'user_has_access' => (bool) $n['user_has_access'],
				)
			);

		} else {
			$args = array();

			if ( true === (bool) $n['show_for_displayed_user'] && ! bp_is_my_profile() ) {
				$args = array( 'show_for_displayed_user' => true );
			}

			// Reorder the user's primary nav according to the customizer setting.
			bp_nouveau_set_nav_item_order( $user_nav, bp_nouveau_get_appearance_settings( 'user_nav_order' ) );

			$nav = $user_nav->get_primary( $args );
		}
	} elseif ( ! empty( $bp_nouveau->object_nav ) ) {
		$bp_nouveau->displayed_nav = $bp_nouveau->object_nav;

		/**
		 * Use the filter to use your specific Navigation.
		 * Use the $n param to check for your custom object.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $nav The list of item navigations generated by the BP_Core_Nav API.
		 * @param array $n   The arguments of the Navigation loop.
		 */
		$nav = apply_filters( 'bp_nouveau_get_nav', $nav, $n );

	}

	// The navigation can be empty.
	if ( false === $nav ) {
		$nav = array();
	}

	$bp_nouveau->sorted_nav = array_values( $nav );

	if ( 0 === count( $bp_nouveau->sorted_nav ) || ! $bp_nouveau->displayed_nav ) {
		unset( $bp_nouveau->sorted_nav, $bp_nouveau->displayed_nav, $bp_nouveau->object_nav );

		return false;
	}

	$bp_nouveau->current_nav_index = 0;
	return true;
}

/**
 * Checks there are still nav items to display.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if there are still items to display. False otherwise.
 */
function bp_nouveau_nav_items() {
	$bp_nouveau = bp_nouveau();

	if ( isset( $bp_nouveau->sorted_nav[ $bp_nouveau->current_nav_index ] ) ) {
		return true;
	}

	$bp_nouveau->current_nav_index = 0;
	unset( $bp_nouveau->current_nav_item );

	return false;
}

/**
 * Sets the current nav item and prepare the navigation loop to iterate to next one.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_item() {
	$bp_nouveau = bp_nouveau();

	$bp_nouveau->current_nav_item   = $bp_nouveau->sorted_nav[ $bp_nouveau->current_nav_index ];
	$bp_nouveau->current_nav_index += 1;
}

/**
 * Displays the nav item ID.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_id() {
	echo esc_attr( bp_nouveau_get_nav_id() );
}

	/**
	 * Retrieve the ID attribute of the current nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string the ID attribute.
	 */
function bp_nouveau_get_nav_id() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;

	if ( 'directory' === $bp_nouveau->displayed_nav ) {
		$id = sprintf( '%1$s-%2$s', $nav_item->component, $nav_item->slug );
	} elseif ( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav ) {
		$id = sprintf( '%1$s-%2$s-li', $nav_item->css_id, $bp_nouveau->displayed_nav );
	} else {
		$id = $nav_item->slug;
	}

	/**
	 * Filter to edit the ID attribute of the nav.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $id       The ID attribute of the nav.
	 * @param object $nav_item The current nav item object.
	 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return apply_filters( 'bp_nouveau_get_nav_id', $id, $nav_item, $bp_nouveau->displayed_nav );
}

/**
 * Displays the nav item classes.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_classes() {
	echo esc_attr( bp_nouveau_get_nav_classes() );
}

	/**
	 * Retrieve a space separated list of classes for the current nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string List of classes.
	 */
function bp_nouveau_get_nav_classes() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$classes    = array();

	if ( 'directory' === $bp_nouveau->displayed_nav ) {
		if ( ! empty( $nav_item->li_class ) ) {
			$classes = (array) $nav_item->li_class;
		}

		if ( bp_get_current_member_type() ) {
			$classes[] = 'no-ajax';
		}
	} elseif ( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav ) {
		$classes  = array( 'bp-' . $bp_nouveau->displayed_nav . '-tab' );
		$selected = bp_current_action();

		// User's primary nav.
		if ( ! empty( $nav_item->primary ) ) {
			$selected = bp_current_component();

			// Group Member Tabs.
		} elseif ( 'group_members' === $bp_nouveau->object_nav ) {
			$selected = bp_action_variable( 0 );
			$classes  = array( 'bp-' . $bp_nouveau->displayed_nav . '-member-tab' );

			// Group Admin Tabs.
		} elseif ( 'group_manage' === $bp_nouveau->object_nav ) {
			$selected = bp_action_variable( 0 );
			$classes  = array( 'bp-' . $bp_nouveau->displayed_nav . '-admin-tab' );

			// If we are here, it's the member's sub nav.
		} elseif ( 'personal' === $bp_nouveau->displayed_nav ) {
			$classes = array( 'bp-' . $bp_nouveau->displayed_nav . '-sub-tab' );
		}

		if ( $nav_item->slug === $selected || ( $nav_item->slug == 'just-me' && strpos( $selected, 'just-me' ) !== false ) ) {
			$classes = array_merge( $classes, array( 'current', 'selected' ) );
		}

		if ( 'document' === $nav_item->css_id && 'folders' === bp_current_action() && 'document' === bp_current_component() && (int) bp_action_variable( 0 ) > 0 ) {
			$classes = array_merge( $classes, array( 'current', 'selected' ) );
		}
	}

	if ( ! empty( $classes ) ) {
		$classes = array_map( 'sanitize_html_class', $classes );
	}

	/**
	 * Filter to edit/add classes.
	 *
	 * NB: you can also directly add classes into the template parts.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $value    A space separated list of classes.
	 * @param array  $classes  The list of classes.
	 * @param object $nav_item The current nav item object.
	 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	$classes_list = apply_filters( 'bp_nouveau_get_classes', join( ' ', $classes ), $classes, $nav_item, $bp_nouveau->displayed_nav );
	if ( ! $classes_list ) {
		$classes_list = '';
	}

	return $classes_list;
}

/**
 * Displays the nav item scope.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_scope() {
	echo bp_nouveau_get_nav_scope();  // Escaped by bp_get_form_field_attributes().
}

	/**
	 * Retrieve the specific scope for the current nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string the specific scope of the nav.
	 */
function bp_nouveau_get_nav_scope() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$scope      = array();

	if ( 'directory' === $bp_nouveau->displayed_nav ) {
		$scope = array( 'data-bp-scope' => $nav_item->slug );

	} elseif ( 'personal' === $bp_nouveau->displayed_nav && ! empty( $nav_item->secondary ) ) {
		$scope = array( 'data-bp-user-scope' => $nav_item->slug );

	} else {
		/**
		 * Filter to add your own scope.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $scope     Contains the key and the value for your scope.
		 * @param object $nav_item The current nav item object.
		 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
		 */
		$scope = apply_filters( 'bp_nouveau_set_nav_scope', $scope, $nav_item, $bp_nouveau->displayed_nav );
	}

	if ( ! $scope ) {
		return '';
	}

	return bp_get_form_field_attributes( 'scope', $scope );
}

/**
 * Displays the nav item URL.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_link() {
	echo esc_url( bp_nouveau_get_nav_link() );
}

	/**
	 * Retrieve the URL for the current nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string The URL for the nav item.
	 */
function bp_nouveau_get_nav_link() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;

	$link = '#';
	if ( ! empty( $nav_item->link ) ) {
		$link = $nav_item->link;
	}

	if ( 'personal' === $bp_nouveau->displayed_nav && ! empty( $nav_item->primary ) ) {
		if ( bp_loggedin_user_domain() ) {
			$link = str_replace( bp_loggedin_user_domain(), bp_displayed_user_domain(), $link );
		} else {
			$link = trailingslashit( bp_displayed_user_domain() . $link );
		}
	}

	/**
	 * Filter to edit the URL of the nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $link     The URL for the nav item.
	 * @param object $nav_item The current nav item object.
	 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return apply_filters( 'bp_nouveau_get_nav_link', $link, $nav_item, $bp_nouveau->displayed_nav );
}

/**
 * Displays the nav item link ID.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_link_id() {
	echo esc_attr( bp_nouveau_get_nav_link_id() );
}

	/**
	 * Retrieve the id attribute of the link for the current nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string The link id for the nav item.
	 */
function bp_nouveau_get_nav_link_id() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$link_id    = '';

	if ( ( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav ) && ! empty( $nav_item->css_id ) ) {
		$link_id = $nav_item->css_id;

		if ( ! empty( $nav_item->primary ) && 'personal' === $bp_nouveau->displayed_nav ) {
			$link_id = 'user-' . $link_id;
		}
	} else {
		$link_id = $nav_item->slug;
	}

	/**
	 * Filter to edit the link id attribute of the nav.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $link_id  The link id attribute for the nav item.
	 * @param object $nav_item The current nav item object.
	 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return apply_filters( 'bp_nouveau_get_nav_link_id', $link_id, $nav_item, $bp_nouveau->displayed_nav );
}

/**
 * Displays the nav item link title.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_link_title() {
	echo esc_attr( bp_nouveau_get_nav_link_title() );
}

	/**
	 * Retrieve the title attribute of the link for the current nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string The link title for the nav item.
	 */
function bp_nouveau_get_nav_link_title() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$title      = '';

	if ( 'directory' === $bp_nouveau->displayed_nav && ! empty( $nav_item->title ) ) {
		$title = $nav_item->title;

	} elseif (
		( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav )
		&&
		! empty( $nav_item->name )
	) {
		$title = $nav_item->name;
	}

	/**
	 * Filter to edit the link title attribute of the nav.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $title    The link title attribute for the nav item.
	 * @param object $nav_item The current nav item object.
	 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return apply_filters( 'bp_nouveau_get_nav_link_title', $title, $nav_item, $bp_nouveau->displayed_nav );
}

/**
 * Displays the nav item link html text.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_link_text() {
	echo esc_html( bp_nouveau_get_nav_link_text() );
}

	/**
	 * Retrieve the html text of the link for the current nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string The html text for the nav item.
	 */
function bp_nouveau_get_nav_link_text() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$link_text  = '';

	if ( 'directory' === $bp_nouveau->displayed_nav && ! empty( $nav_item->text ) ) {
		$link_text = _bp_strip_spans_from_title( $nav_item->text );

	} elseif (
		( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav )
		&&
		! empty( $nav_item->name )
	) {
		$link_text = _bp_strip_spans_from_title( $nav_item->name );
	}

	/**
	 * Filter to edit the html text of the nav.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $link_text The html text of the nav item.
	 * @param object $nav_item  The current nav item object.
	 * @param string $value     The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return apply_filters( 'bp_nouveau_get_nav_link_text', $link_text, $nav_item, $bp_nouveau->displayed_nav );
}

/**
 * Checks if the nav item has a count attribute.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool
 */
function bp_nouveau_nav_has_count() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$count      = false;

	if ( bb_enable_content_counts() ) {
		if ( 'directory' === $bp_nouveau->displayed_nav ) {
			if ( isset( $nav_item->count ) && false !== $nav_item->count ) {
				$count = $nav_item->count;
			} else {
				if ( bp_is_members_directory() ) {
					if ( 'all' === $nav_item->slug ) {
						$count = bp_core_get_all_member_count();
					} elseif ( 'personal' === $nav_item->slug ) {
						$count = bp_get_total_friend_count( bp_loggedin_user_id() );
					} elseif ( 'following' === $nav_item->slug ) {
						$counts = bp_total_follow_counts();
						$count  = $counts['following'];
					} elseif ( 'followers' === $nav_item->slug ) {
						$counts = bp_total_follow_counts();
						$count  = $counts['followers'];
					}
				} elseif ( bp_is_groups_directory() ) {
					if ( 'all' === $nav_item->slug ) {
						$count = bp_get_total_group_count();
					}
				} elseif ( bp_is_media_directory() ) {
					if ( 'all' === $nav_item->slug ) {
						$count = bp_get_total_media_count();
					} elseif ( 'personal' === $nav_item->slug ) {
						$count = bp_media_get_total_media_count();
					} elseif ( 'groups' === $nav_item->slug ) {
						$count = bp_media_get_user_total_group_media_count();
					}
				} elseif ( bp_is_video_directory() ) {
					if ( 'all' === $nav_item->slug ) {
						$count = bp_get_total_video_count();
					} elseif ( 'personal' === $nav_item->slug ) {
						$count = bp_video_get_total_video_count();
					} elseif ( 'groups' === $nav_item->slug ) {
						$count = bp_video_get_user_total_group_video_count();
					}
				}
			}

		} elseif ( 'groups' === $bp_nouveau->displayed_nav && 'members' === $nav_item->slug ) {
			$count = 0 !== (int) groups_get_current_group()->total_member_count;
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && bp_is_active( 'media' ) && bp_is_group_media_support_enabled() && 'photos' === $nav_item->slug ) {
			$count = 0 !== (int) bp_media_get_total_group_media_count();
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && bp_is_active( 'media' ) && bp_is_group_video_support_enabled() && 'videos' === $nav_item->slug ) {
			$count = 0 !== (int) bp_video_get_total_group_video_count();
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && bp_is_active( 'media' ) && bp_is_group_albums_support_enabled() && 'albums' === $nav_item->slug ) {
			$count = 0 !== (int) bp_media_get_total_group_album_count();
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && 'subgroups' === $nav_item->slug ) {
			$count = 0 !== (int) count( bp_get_descendent_groups( bp_get_current_group_id(), bp_loggedin_user_id() ) );
		} elseif ( 'personal' === $bp_nouveau->displayed_nav && ! empty( $nav_item->primary ) ) {
			$count = (bool) strpos( $nav_item->name, '="count"' );
		}
	}

	/**
	 * Filter to edit whether the nav has a count attribute.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param bool   $value     True if the nav has a count attribute. False otherwise
	 * @param object $nav_item  The current nav item object.
	 * @param string $value     The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return (bool) apply_filters( 'bp_nouveau_nav_has_count', false !== $count, $nav_item, $bp_nouveau->displayed_nav );
}

/**
 * Displays the nav item count attribute.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_nav_count() {
	echo esc_html( bp_core_number_format( bp_nouveau_get_nav_count() ) );
}

	/**
	 * Retrieve the count attribute for the current nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return bool|int The count attribute for the nav item, false if not available.
	 */
function bp_nouveau_get_nav_count() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$count      = false;

	if ( bb_enable_content_counts() ) {
		if ( 'directory' === $bp_nouveau->displayed_nav ) {
			if ( isset( $nav_item->count ) && false !== $nav_item->count ) {
				$count = (int) str_replace( ',', '', $nav_item->count );
			} else {

				if ( bp_is_members_directory() ) {
					if ( 'all' === $nav_item->slug ) {
						$count = bp_core_get_all_member_count();
					} elseif ( 'personal' === $nav_item->slug ) {
						$count = bp_get_total_friend_count( bp_loggedin_user_id() );
					} elseif ( 'following' === $nav_item->slug ) {
						// Following count.
						$counts = bp_total_follow_counts();
						$count  = $counts['following'];
					} elseif ( 'followers' === $nav_item->slug ) {
						$counts = bp_total_follow_counts();
						$count  = $counts['followers'];
					}
				} elseif ( bp_is_groups_directory() ) {
					if ( 'all' === $nav_item->slug ) {
						$count = bp_get_total_group_count();
					}
				} elseif ( bp_is_media_directory() ) {
					if ( 'all' === $nav_item->slug ) {
						$count = bp_get_total_media_count();
					} elseif ( 'personal' === $nav_item->slug ) {
						$count = bp_media_get_total_media_count();
					} elseif ( 'groups' === $nav_item->slug ) {
						$count = bp_media_get_user_total_group_media_count();
					}
				} elseif ( bp_is_video_directory() ) {
					if ( 'all' === $nav_item->slug ) {
						$count = bp_get_total_video_count();
					} elseif ( 'personal' === $nav_item->slug ) {
						$count = bp_video_get_total_video_count();
					} elseif ( 'groups' === $nav_item->slug ) {
						$count = bp_video_get_user_total_group_video_count();
					}
				}
			}
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && ( 'members' === $nav_item->slug || 'all-members' === $nav_item->slug ) ) {
			$count = (int) groups_get_current_group()->total_member_count;
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && 'subgroups' === $nav_item->slug ) {
			$count = count( bp_get_descendent_groups( bp_get_current_group_id(), bp_loggedin_user_id() ) );
			// } elseif ( 'groups' === $bp_nouveau->displayed_nav && bp_is_active( 'media' ) && bp_is_group_document_support_enabled() && 'documents' === $nav_item->slug ) {
			// $count = bp_document_get_total_group_document_count();
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && bp_is_active( 'media' ) && bp_is_group_media_support_enabled() && 'photos' === $nav_item->slug ) {
			$count = bp_media_get_total_group_media_count();
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && bp_is_active( 'media' ) && bp_is_group_albums_support_enabled() && 'albums' === $nav_item->slug ) {
			$count = bp_media_get_total_group_album_count();
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && bp_is_active( 'video' ) && bp_is_group_video_support_enabled() && 'videos' === $nav_item->slug ) {
			$count = bp_video_get_total_group_video_count();
		} elseif ( 'groups' === $bp_nouveau->displayed_nav && 'leaders' === $nav_item->slug ) {
			$group  = groups_get_current_group();
			$admins = groups_get_group_admins( $group->id );
			$mods   = groups_get_group_mods( $group->id );
			$count  = sizeof( $admins ) + sizeof( $mods );

			// @todo imho BuddyPress shouldn't add html tags inside Nav attributes...
		} elseif ( 'personal' === $bp_nouveau->displayed_nav && ! empty( $nav_item->primary ) ) {
			$span = strpos( $nav_item->name, '<span' );

			// Grab count out of the <span> element.
			if ( false !== $span ) {
				$count_start = strpos( $nav_item->name, '>', $span ) + 1;
				$count_end   = strpos( $nav_item->name, '<', $count_start );
				$count       = (int) substr( $nav_item->name, $count_start, $count_end - $count_start );
			}
		}
	}

	/**
	 * Filter to edit the count attribute for the nav item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param int $count    The count attribute for the nav item.
	 * @param object $nav_item The current nav item object.
	 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return (int) apply_filters( 'bp_nouveau_get_nav_count', $count, $nav_item, $bp_nouveau->displayed_nav );
}

/** Template tags specific to the Directory navs ******************************/

/**
 * Displays the directory nav class.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_directory_type_navs_class() {
	echo esc_attr( bp_nouveau_get_directory_type_navs_class() );
}

	/**
	 * Provides default nav wrapper classes.
	 *
	 * Gets the directory component nav class.
	 * Gets user selection Customizer options.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string
	 */
function bp_nouveau_get_directory_type_navs_class() {
	$component = sanitize_key( bp_current_component() );

	// If component is 'blogs' we need to access options as 'Sites'.
	if ( 'blogs' === $component ) {
		$component = 'sites';
	};

	$customizer_option = sprintf( '%s_dir_tabs', $component );
	$nav_style         = bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );
	$tab_style         = '';

	if ( 1 === $nav_style ) {
		$tab_style = $component . '-nav-tabs';
	}

	$nav_wrapper_classes = array(
		sprintf( '%s-type-navs', $component ),
		'main-navs',
		'bp-navs',
		'dir-navs',
		$tab_style,
	);

	/**
	 * Filter to edit/add classes.
	 *
	 * NB: you can also directly add classes to the class attr.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $nav_wrapper_classes The list of classes.
	 */
	$nav_wrapper_classes = (array) apply_filters( 'bp_nouveau_get_directory_type_navs_class', $nav_wrapper_classes );

	return join( ' ', array_map( 'sanitize_html_class', $nav_wrapper_classes ) );
}

/**
 * Displays the directory nav item list class.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_directory_list_class() {
	echo esc_attr( bp_nouveau_get_directory_list_class() );
}

	/**
	 * Gets the directory nav item list class.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string
	 */
function bp_nouveau_get_directory_list_class() {
	return sanitize_html_class( sprintf( '%s-nav', bp_current_component() ) );
}

/**
 * Displays the directory nav item object (data-bp attribute).
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_directory_nav_object() {
	$obj = bp_nouveau_get_directory_nav_object();

	if ( ! is_null( $obj ) ) {
		echo esc_attr( $obj );
	}
}

	/**
	 * Gets the directory nav item object.
	 *
	 * @see BP_Component::setup_nav().
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return array
	 */
function bp_nouveau_get_directory_nav_object() {
	$nav_item = bp_nouveau()->current_nav_item;

	if ( ! $nav_item->component ) {
		return null;
	}

	return $nav_item->component;
}


// Template tags for the single item navs.

/**
 * Output main BuddyPress container classes.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string CSS classes
 */
function bp_nouveau_container_classes() {
	echo esc_attr( bp_nouveau_get_container_classes() );
}

	/**
	 * Returns the main BuddyPress container classes.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string CSS classes
	 */
function bp_nouveau_get_container_classes() {
	$classes           = array( 'buddypress-wrap' );
	$component         = bp_current_component();
	$bp_nouveau        = bp_nouveau();
	$member_type_class = '';

	if ( bp_is_user() ) {
		$customizer_option = 'user_nav_display';
		$component         = 'members';
		$user_type         = bp_get_member_type( bp_displayed_user_id() );
		$member_type_class = ( $user_type ) ? $user_type : '';

	} elseif ( bp_is_group() ) {
		$customizer_option = 'group_nav_display';

	} elseif ( bp_is_directory() ) {
		switch ( $component ) {
			case 'activity':
				$customizer_option = 'activity_dir_layout';
				break;

			case 'members':
				$customizer_option = 'members_dir_layout';
				break;

			case 'groups':
				$customizer_option = 'groups_dir_layout';
				break;

			case 'blogs':
				$customizer_option = 'sites_dir_layout';
				break;

			case 'media':
				$customizer_option = 'media_dir_layout';
				break;

			default:
				$customizer_option = '';
				break;
		}
	} else {
		/**
		 * Filters the BuddyPress Nouveau single item setting ID.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $value Setting ID.
		 */
		$customizer_option = apply_filters( 'bp_nouveau_single_item_display_settings_id', '' );
	}

	if ( $member_type_class ) {
		$classes[] = $member_type_class;
	}

	// Provide a class token to acknowledge additional extended profile fields added to default account reg screen
	if ( 'register' === bp_current_component() && bp_is_active( 'xprofile' ) && bp_nouveau_base_account_has_xprofile() ) {
		$classes[] = 'extended-default-reg';
	}

	// Add classes according to site owners preferences. These are options set via Customizer.

	// Set via earlier switch for component check to provide correct option key.
	if ( $customizer_option ) {
		$layout_prefs = bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );

		if ( $layout_prefs && (int) $layout_prefs === 1 && ( bp_is_user() || bp_is_group() ) ) {
			$classes[] = 'bp-single-vert-nav';
			$classes[] = 'bp-vertical-navs';
		} else {
			$classes[] = 'bp-single-plain-nav';
		}

		if ( $layout_prefs && bp_is_directory() ) {
			$classes[] = 'bp-dir-vert-nav';
			$classes[] = 'bp-vertical-navs';
			$bp_nouveau->{$component}->directory_vertical_layout = $layout_prefs;
		} else {
			$classes[] = 'bp-dir-hori-nav';
		}
	}

	$class = array_map( 'sanitize_html_class', $classes );

	/**
	 * Filters the final results for BuddyPress Nouveau container classes.
	 *
	 * This filter will return a single string of concatenated classes to be used.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $value   Concatenated classes.
	 * @param array  $classes Array of classes that were concatenated.
	 */
	return apply_filters( 'bp_nouveau_get_container_classes', join( ' ', $class ), $classes );
}

/**
 * Output single item nav container classes
 *
 * @since BuddyPress 3.0.0
 *
 * @return string CSS classes
 */
function bp_nouveau_single_item_nav_classes() {
	echo esc_attr( bp_nouveau_get_single_item_nav_classes() );
}

	/**
	 * Returns the single item nav container classes
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string CSS classes
	 */
function bp_nouveau_get_single_item_nav_classes() {
	$classes    = array( 'main-navs', 'no-ajax', 'bp-navs', 'single-screen-navs', 'bb-single-main-nav' );
	$component  = bp_current_component();
	$bp_nouveau = bp_nouveau();

	if ( bp_is_user() ) {
		$component = 'members';
		$menu_type = 'users-nav';
	} else {
		$menu_type = 'groups-nav';
	}

	$customizer_option = ( bp_is_user() ) ? 'user_nav_display' : 'group_nav_display';

	$layout_prefs = (int) bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );

	// Set the global for a later use - this is moved from the `bp_nouveau_get_container_classes()
	// But was set as a check for this array class addition.
	$bp_nouveau->{$component}->single_primary_nav_layout = $layout_prefs;

	if ( 1 === $layout_prefs ) {
		$classes[] = 'vertical';
		$classes[] = 'bb-single-main-nav--vertical';
	} else {
		$classes[] = 'horizontal';
		$classes[] = 'bb-single-main-nav--horizontal';
	}

	$classes[] = $menu_type;
	$class     = array_map( 'sanitize_html_class', $classes );

	/**
	 * Filters the final results for BuddyPress Nouveau single item nav classes.
	 *
	 * This filter will return a single string of concatenated classes to be used.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $value   Concatenated classes.
	 * @param array  $classes Array of classes that were concatenated.
	 */
	return apply_filters( 'bp_nouveau_get_single_item_nav_classes', join( ' ', $class ), $classes );
}

/**
 * Output single item subnav container classes.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string CSS classes
 */
function bp_nouveau_single_item_subnav_classes() {
	echo esc_attr( bp_nouveau_get_single_item_subnav_classes() );
}

	/**
	 * Returns the single item subnav container classes.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string CSS classes
	 */
function bp_nouveau_get_single_item_subnav_classes() {
	$customizer_option = ( bp_is_user() ) ? 'user_nav_display' : 'group_nav_display';
	$layout_prefs      = bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );
	$classes           = array( 'bp-navs', 'bp-subnavs', 'no-ajax' );

	// Set user or group class string
	if ( bp_is_user() ) {
		$classes[] = 'user-subnav';
	}

	if ( bp_is_group() ) {
		$classes[] = 'group-subnav';
	}

	if ( ( bp_is_group() && 'send-invites' === bp_current_action() ) || ( bp_is_group() && 'pending-invites' === bp_current_action() ) || ( bp_is_group() && 'invite' === bp_current_action() ) || ( bp_is_group_create() && 'group-invites' === bp_get_groups_current_create_step() ) ) {
		$classes[] = 'bp-invites-nav';
	}

	if ( ( bp_is_group() && 'messages' === bp_current_action() ) ) {
		$classes[] = 'bp-messages-nav';
	}

	if ( $layout_prefs && 1 === (int) $layout_prefs && ( bp_is_user() || bp_is_group() ) ) {
		$classes[] = 'bb-subnav-vert';
	} else {
		$classes[] = 'bb-subnav-plain';
	}

	$class = array_map( 'sanitize_html_class', $classes );

	/**
	 * Filters the final results for BuddyPress Nouveau single item subnav classes.
	 *
	 * This filter will return a single string of concatenated classes to be used.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $value   Concatenated classes.
	 * @param array  $classes Array of classes that were concatenated.
	 */
	return apply_filters( 'bp_nouveau_get_single_item_subnav_classes', join( ' ', $class ), $classes );
}

/**
 * Output the groups create steps classes.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string CSS classes
 */
function bp_nouveau_groups_create_steps_classes() {
	echo esc_attr( bp_nouveau_get_group_create_steps_classes() );
}

	/**
	 * Returns the groups create steps customizer option choice class.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string CSS classes
	 */
function bp_nouveau_get_group_create_steps_classes() {
	$classes = array( 'bp-navs', 'group-create-links', 'no-ajax' );

	$class = array_map( 'sanitize_html_class', $classes );

	/**
	 * Filters the final results for BuddyPress Nouveau group creation step classes.
	 *
	 * This filter will return a single string of concatenated classes to be used.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $value   Concatenated classes.
	 * @param array  $classes Array of classes that were concatenated.
	 */
	return apply_filters( 'bp_nouveau_get_group_create_steps_classes', join( ' ', $class ), $classes );
}


/** Template tags for the object search **************************************/

/**
 * Get the search primary object
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $object Optional. The primary object.
 *
 * @return string The primary object.
 */
function bp_nouveau_get_search_primary_object( $object = '' ) {
	if ( bp_is_user() ) {
		$object = 'member';
	} elseif ( bp_is_group() ) {
		$object = 'group';
	} elseif ( bp_is_directory() ) {
		$object = 'dir';
	} else {

		/**
		 * Filters the search primary object if no other was found.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $object Search object.
		 */
		$object = apply_filters( 'bp_nouveau_get_search_primary_object', $object );
	}

	return $object;
}

/**
 * Get The list of search objects (primary + secondary).
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $objects Optional. The list of objects.
 *
 * @return array The list of objects.
 */
function bp_nouveau_get_search_objects( $objects = array() ) {
	$primary = bp_nouveau_get_search_primary_object();
	if ( ! $primary ) {
		return $objects;
	}

	$objects = array(
		'primary' => $primary,
	);

	if ( 'member' === $primary || 'dir' === $primary ) {
		$objects['secondary'] = bp_current_component();
	} elseif ( 'group' === $primary ) {
		$objects['secondary'] = bp_current_action();
	} else {

		/**
		 * Filters the search objects if no others were found.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $objects Search objects.
		 */
		$objects = apply_filters( 'bp_nouveau_get_search_objects', $objects );
	}

	return $objects;
}

/**
 * Output the search form container classes.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_search_container_class() {
	$objects = bp_nouveau_get_search_objects();
	$class   = join( '-search ', array_map( 'sanitize_html_class', $objects ) ) . '-search';

	echo esc_attr( $class );
}

/**
 * Output the search form data-bp attribute.
 *
 * @since BuddyPress 3.0.0
 *
 * @param  string $attr The data-bp attribute.
 * @return string The data-bp attribute.
 */
function bp_nouveau_search_object_data_attr( $attr = '' ) {
	$objects = bp_nouveau_get_search_objects();

	if ( ! isset( $objects['secondary'] ) ) {
		return $attr;
	}

	if ( bp_is_active( 'groups' ) && bp_is_group_members() ) {
		$attr = join( '_', $objects );
	} elseif ( bp_is_active( 'groups' ) && bp_is_group_subgroups() ) {
		$attr = 'group_subgroups';
	} else {
		$attr = $objects['secondary'];
	}

	echo esc_attr( $attr );
}

/**
 * Output a selector ID.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $suffix Optional. A string to append at the end of the ID.
 * @param string $sep    Optional. The separator to use between each token.
 *
 * @return string The selector ID.
 */
function bp_nouveau_search_selector_id( $suffix = '', $sep = '-' ) {
	$id = join( $sep, array_merge( bp_nouveau_get_search_objects(), (array) $suffix ) );
	echo esc_attr( $id );
}

/**
 * Output the name attribute of a selector.
 *
 * @since BuddyPress 3.0.0
 *
 * @param  string $suffix Optional. A string to append at the end of the name.
 * @param  string $sep    Optional. The separator to use between each token.
 *
 * @return string The name attribute of a selector.
 */
function bp_nouveau_search_selector_name( $suffix = '', $sep = '_' ) {
	$objects = bp_nouveau_get_search_objects();

	if ( isset( $objects['secondary'] ) && ! $suffix ) {
		$name = bp_core_get_component_search_query_arg( $objects['secondary'] );
	} else {
		$name = join( $sep, array_merge( $objects, (array) $suffix ) );
	}

	echo esc_attr( $name );
}

/**
 * Output the default search text for the search object
 *
 * @since BuddyPress 3.0.0
 *
 * @param  string $text    Optional. The default search text for the search object.
 * @param  string $is_attr Optional. True if it's to be output inside an attribute. False Otherwise.
 *
 * @return string The default search text.
 *
 * @todo 28/09/17 added  'empty( $text )' check to $object query as it wasn't returning output as expected & not returning user set params
 * This may require further examination - hnla
 */
function bp_nouveau_search_default_text( $text = '', $is_attr = true ) {
	$objects = bp_nouveau_get_search_objects();

	if ( ! empty( $objects['secondary'] ) && empty( $text ) ) {
		$text = bp_get_search_default_text( $objects['secondary'] );
	}

	if ( $is_attr ) {
		echo esc_attr( $text );
	} else {
		echo esc_html( $text );
	}
}

/**
 * Get the search form template part and fire some do_actions if needed.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_search_form() {
	$search_form_html = bp_buffer_template_part( 'common/search/search-form', null, false );

	$objects = bp_nouveau_get_search_objects();
	if ( empty( $objects['primary'] ) || empty( $objects['secondary'] ) ) {
		return;
	}

	if ( 'dir' === $objects['primary'] ) {
		/**
		 * Filter here to edit the HTML output of the directory search form.
		 *
		 * NB: This will take in charge the following BP Core Components filters
		 *     - bp_directory_members_search_form
		 *     - bp_directory_blogs_search_form
		 *     - bp_directory_groups_search_form
		 *
		 * @since BuddyPress 1.9.0
		 *
		 * @param string $search_form_html The HTML output for the directory search form.
		 */
		echo apply_filters( "bp_directory_{$objects['secondary']}_search_form", $search_form_html );

		if ( 'activity' === $objects['secondary'] ) {
			/**
			 * Fires before the display of the activity syndication options.
			 *
			 * @since BuddyPress 1.2.0
			 */
			do_action( 'bp_activity_syndication_options' );

		} elseif ( 'blogs' === $objects['secondary'] ) {
			/**
			 * Fires inside the unordered list displaying blog sub-types.
			 *
			 * @since BuddyPress 1.5.0
			 */
			do_action( 'bp_blogs_directory_blog_sub_types' );

		} elseif ( 'groups' === $objects['secondary'] ) {
			/**
			 * Fires inside the groups directory group types.
			 *
			 * @since BuddyPress 1.2.0
			 */
			do_action( 'bp_groups_directory_group_types' );

		} elseif ( 'members' === $objects['secondary'] ) {
			/**
			 * Fires inside the members directory member sub-types.
			 *
			 * @since BuddyPress 1.5.0
			 */
			do_action( 'bp_members_directory_member_sub_types' );
		}
	} elseif ( 'group' === $objects['primary'] ) {
		if ( 'members' !== $objects['secondary'] ) {
			/**
			 * Filter here to edit the HTML output of the displayed group search form.
			 *
			 * @since BuddyPress 3.2.0
			 *
			 * @param string $search_form_html The HTML output for the directory search form.
			 */
			echo apply_filters( "bp_group_{$objects['secondary']}_search_form", $search_form_html );

		} else {
			/**
			 * Filters the Members component search form.
			 *
			 * @since BuddyPress 1.9.0
			 *
			 * @param string $search_form_html HTML markup for the member search form.
			 */
			echo apply_filters( 'bp_directory_members_search_form', $search_form_html );
		}

		if ( 'members' === $objects['secondary'] ) {
			/**
			 * Fires at the end of the group members search unordered list.
			 *
			 * Part of bp_groups_members_template_part().
			 *
			 * @since BuddyPress 1.5.0
			 */
			do_action( 'bp_members_directory_member_sub_types' );

		} elseif ( 'activity' === $objects['secondary'] ) {
			/**
			 * Fires inside the syndication options list, after the RSS option.
			 *
			 * @since BuddyPress 1.2.0
			 */
			do_action( 'bp_group_activity_syndication_options' );
		}
	} elseif ( 'member' === $objects['primary'] && 'activity' === $objects['secondary'] ) {

		echo apply_filters( "bp_directory_{$objects['secondary']}_search_form", $search_form_html );

		/**
		 * Fires before the display of the activity syndication options.
		 *
		 * @since BuddyBoss 2.8.20
		 */
		do_action( 'bp_activity_syndication_options' );
	}
}


// Template tags for the directory & user/group screen filters.

/**
 * Get the current component or action.
 *
 * If on single group screens we need to switch from component to bp_current_action() to add the correct
 * IDs/labels for group/activity & similar screens.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_current_object() {
	/*
	 * If we're looking at groups single screens we need to factor in current action
	 * to avoid the component check adding the wrong id for the main dir e.g 'groups' instead of 'activity'.
	 * We also need to check for group screens to adjust the id's for prefixes.
	 */
	$component = array();

	if ( bp_is_group() ) {
		$component['members_select']   = 'groups_members-order-select';
		$component['members_order_by'] = 'groups_members-order-by';
		$component['object']           = bp_current_action();
		$component['data_filter']      = bp_current_action();

		if ( 'activity' !== bp_current_action() ) {
			$component['data_filter'] = 'group_' . bp_current_action();
		}
	} else {
		$component['members_select']   = 'members-order-select';
		$component['members_order_by'] = 'members-order-by';
		$component['object']           = bp_current_component();
		$component['data_filter']      = bp_current_component();
	}

	return $component;
}

/**
 * Output data filter container's ID attribute value.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_filter_container_id() {
	echo esc_attr( bp_nouveau_get_filter_container_id() );
}

	/**
	 * Get data filter container's ID attribute value.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string
	 */
function bp_nouveau_get_filter_container_id() {
	$component = bp_nouveau_current_object();

	$ids = array(
		'members'       => $component['members_select'],
		'friends'       => 'members-friends-select',
		'notifications' => 'notifications-filter-select',
		'activity'      => 'activity-filter-select',
		'groups'        => 'groups-order-select',
		'blogs'         => 'blogs-order-select',
	);

	if ( isset( $ids[ $component['object'] ] ) ) {

		/**
		 * Filters the container ID for BuddyPress Nouveau filters.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $value ID based on current component object.
		 */
		return apply_filters( 'bp_nouveau_get_filter_container_id', $ids[ $component['object'] ] );
	}

	return '';
}

/**
 * Output data filter's ID attribute value.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_filter_id() {
	echo esc_attr( bp_nouveau_get_filter_id() );
}

	/**
	 * Get data filter's ID attribute value.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string
	 */
function bp_nouveau_get_filter_id() {
	$component = bp_nouveau_current_object();

	$ids = array(
		'members'       => $component['members_order_by'],
		'friends'       => 'members-friends',
		'notifications' => 'notifications-filter-by',
		'activity'      => 'activity-filter-by',
		'groups'        => 'groups-order-by',
		'blogs'         => 'blogs-order-by',
	);

	if ( isset( $ids[ $component['object'] ] ) ) {

		/**
		 * Filters the filter ID for BuddyPress Nouveau filters.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $value ID based on current component object.
		 */
		return apply_filters( 'bp_nouveau_get_filter_id', $ids[ $component['object'] ] );
	}

	return '';
}

/**
 * Output data filter's label.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_filter_label() {
	echo esc_html( bp_nouveau_get_filter_label() );
}

	/**
	 * Get data filter's label.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string
	 */
function bp_nouveau_get_filter_label() {
	$component = bp_nouveau_current_object();
	$label     = __( 'Order By:', 'buddyboss' );

	if ( 'activity' === $component['object'] || 'friends' === $component['object'] ) {
		$label = __( 'Show:', 'buddyboss' );
	}

	/**
	 * Filters the label for BuddyPress Nouveau filters.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $label     Label for BuddyPress Nouveau filter.
	 * @param array  $component The data filter's data-bp-filter attribute value.
	 */
	return apply_filters( 'bp_nouveau_get_filter_label', $label, $component );
}

/**
 * Output data filter's data-bp-filter attribute value.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_filter_component() {
	$component = bp_nouveau_current_object();
	echo esc_attr( $component['data_filter'] );
}

/**
 * Output the <option> of the data filter's <select> element.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_filter_options() {
	echo bp_nouveau_get_filter_options();  // Escaped in inner functions.
}

	/**
	 * Get the <option> of the data filter's <select> element.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string
	 */
function bp_nouveau_get_filter_options() {
	$output = '';

	if ( 'notifications' === bp_current_component() ) {
		$output = bp_nouveau_get_notifications_filters();

	} else {
		$filters = bp_nouveau_get_component_filters();

		foreach ( $filters as $key => $value ) {
			$output .= sprintf(
				'<option value="%1$s">%2$s</option>%3$s',
				esc_attr( $key ),
				esc_html( $value ),
				PHP_EOL
			);
		}
	}

	return $output;
}


/** Template tags for the Customizer ******************************************/

/**
 * Get a link to reach a specific section into the customizer
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args Optional. The argument to customize the Customizer link.
 *
 * @return string HTML.
 */
function bp_nouveau_get_customizer_link( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'capability' => 'bp_moderate',
			'object'     => 'user',
			'item_id'    => 0,
			'autofocus'  => '',
			'text'       => '',
		),
		'nouveau_get_customizer_link'
	);

	if ( empty( $r['capability'] ) || empty( $r['autofocus'] ) || empty( $r['text'] ) ) {
		return '';
	}

	if ( ! bp_current_user_can( $r['capability'] ) ) {
		return '';
	}

	$url = '';

	if ( bp_is_user() ) {
		$url = rawurlencode( bp_displayed_user_domain() );

	} elseif ( bp_is_group() ) {
		$url = rawurlencode( bp_get_group_permalink( groups_get_current_group() ) );

	} elseif ( ! empty( $r['object'] ) && ! empty( $r['item_id'] ) ) {
		if ( 'user' === $r['object'] ) {
			$url = rawurlencode( bp_core_get_user_domain( $r['item_id'] ) );

		} elseif ( 'group' === $r['object'] ) {
			$group = groups_get_group( array( 'group_id' => $r['item_id'] ) );

			if ( ! empty( $group->id ) ) {
				$url = rawurlencode( bp_get_group_permalink( $group ) );
			}
		}
	}

	if ( ! $url ) {
		return '';
	}

	$customizer_link = add_query_arg(
		array(
			'autofocus[section]' => $r['autofocus'],
			'url'                => $url,
		),
		admin_url( 'customize.php' )
	);

	return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $customizer_link ), esc_html( $r['text'] ) );
}

/** Template tags for signup forms *******************************************/

/**
 * Fire specific hooks into the register template
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $when   'before' or 'after'
 * @param string $prefix Use it to add terms before the hook name
 */
function bp_nouveau_signup_hook( $when = '', $prefix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	if ( $prefix ) {
		if ( 'page' === $prefix ) {
			$hook[] = 'register';
		} elseif ( 'steps' === $prefix ) {
			$hook[] = 'signup';
		}

		$hook[] = $prefix;
	}

	if ( 'page' !== $prefix && 'steps' !== $prefix ) {
		$hook[] = 'fields';
	}

	bp_nouveau_hook( $hook );
}

/**
 * Fire specific hooks into the activate template
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $when   'before' or 'after'
 * @param string $prefix Use it to add terms before the hook name
 */
function bp_nouveau_activation_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	$hook[] = 'activate';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	if ( 'page' === $suffix ) {
		$hook[2] = 'activation';
	}

	bp_nouveau_hook( $hook );
}

/**
 * Output the signup form for the requested section
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $section Optional. The section of fields to get 'account_details' or 'blog_details'.
 *                        Default: 'account_details'.
 */
function bp_nouveau_signup_form( $section = 'account_details' ) {
	$fields = bp_nouveau_get_signup_fields( $section );
	if ( ! $fields ) {
		return;
	}

	foreach ( $fields as $name => $attributes ) {
		list( $label, $required, $value, $attribute_type, $type, $class ) = array_values( $attributes );

		$signup_class_arr = array( 'bb-signup-field', $name );
		/**
		 * Filters the class of the signup field.
		 *
		 * @since BuddyBoss [BVERSION]
		 *
		 * @param array $signup_class_arr The class of the signup field.
		 * @param array $attributes       The attributes of the signup field.
		 */
		$signup_class = apply_filters( 'bb_nouveau_signup_field_class', $signup_class_arr, $attributes );

		// Ensure $signup_class is an array after the filter.
		if ( ! is_array( $signup_class ) ) {
			$signup_class = (array) $signup_class;
		}

		$signup_class = ! empty( $signup_class ) ? join( ' ', array_map( 'sanitize_html_class', $signup_class ) ) : '';
		?>
		<div class="<?php echo esc_attr( $signup_class ); ?>">
		<?php
		// Text fields are using strings, radios are using their inputs
		$label_output = '<label for="%1$s">%2$s</label>';
		$id           = $name;
		$classes      = '';

		if ( $required ) {
			/* translators: Do not translate placeholders. 2 = form field name, 3 = "(required)". */
			$label_output = __( '<label for="%1$s">%2$s %3$s</label>', 'buddyboss' );
		}

		// Output the label for regular fields
		if ( 'radio' !== $type ) {
			if ( $required ) {
				printf( $label_output, esc_attr( $name ), esc_html( $label ), '' );
			} else {
				printf( $label_output, esc_attr( $name ), esc_html( $label ) );
			}

			if ( ! empty( $value ) && is_callable( $value ) ) {
				$value = call_user_func( $value );
			}

			// Handle the specific case of Site's privacy differently
		} elseif ( 'signup_blog_privacy_private' !== $name ) {
			?>
				<label for="signup_blog_privacy">
					<?php esc_html_e( 'I would like my site to appear in search engines, and in public listings around this network.', 'buddyboss' ); ?>
				</label>
			<?php
		}

		// Set the additional attributes
		if ( $attribute_type ) {
			$existing_attributes = array();

			if ( ! empty( $required ) ) {
				$existing_attributes = array( 'aria-required' => 'true' );

				/**
				 * The blog section is hidden, so let's avoid a browser warning
				 * and deal with the Blog section in Javascript.
				 */
				if ( $section !== 'blog_details' ) {
					// Removed because we don't have to display the browser error message.
					// $existing_attributes['required'] = 'required';
				}
			}

			$attribute_type = ' ' . bp_get_form_field_attributes( $attribute_type, $existing_attributes );
		}

		// Specific case for Site's privacy
		if ( 'signup_blog_privacy_public' === $name || 'signup_blog_privacy_private' === $name ) {
			$name      = 'signup_blog_privacy';
			$submitted = bp_get_signup_blog_privacy_value();

			if ( ! $submitted ) {
				$submitted = 'public';
			}

			$attribute_type = ' ' . checked( $value, $submitted, false );
		}

		// Do not run function to display errors for the private radio.
		if ( 'private' !== $value ) {

			/**
			 * Fetch & display any BP member registration field errors.
			 *
			 * Passes BP signup errors to Nouveau's template function to
			 * render suitable markup for error string.
			 */
			if ( isset( buddypress()->signup->errors[ $name ] ) ) {
				nouveau_error_template( buddypress()->signup->errors[ $name ] );
				$invalid = 'invalid';
			}
		}

		if ( isset( $invalid ) && isset( buddypress()->signup->errors[ $name ] ) ) {
			if ( ! empty( $class ) ) {
				$class = $class . ' ' . $invalid;
			} else {
				$class = $invalid;
			}
		}

		if ( $class ) {
			$class = sprintf(
				' class="%s"',
				esc_attr( join( ' ', array_map( 'sanitize_html_class', explode( ' ', $class ) ) ) )
			);
		}

		// Set the input.
		$field_output = sprintf(
			'<input type="%1$s" name="%2$s" id="%3$s" %4$s value="%5$s" %6$s />',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $id ),
			$class,  // Constructed safely above.
			esc_attr( $value ),
			$attribute_type // Constructed safely above.
		);

		// Not a radio, let's output the field
		if ( 'radio' !== $type ) {
			if ( 'signup_blog_url' !== $name ) {

				if ( ( 'signup_password' === $name ) || ( 'signup_password_confirm' === $name ) ) {
					echo '<div class="bb-password-wrap">';
					echo '<a href="#" class="bb-toggle-password" tabindex="-1"><i class="bb-icon-l bb-icon-eye"></i></a>';
				}

				print( $field_output );  // Constructed safely above.

				if ( ( 'signup_password' === $name ) || ( 'signup_password_confirm' === $name ) ) {
					echo '</div>';
				}

				// If it's the signup blog url, it's specific to Multisite config.
			} elseif ( is_subdomain_install() ) {
				// Constructed safely above.
				printf(
					'<small>%1$s</small> %2$s <small>. %3$s</small><br /><br />',
					is_ssl() ? 'https://' : 'http://',
					$field_output,
					bp_signup_get_subdomain_base()
				);

				// Subfolders!
			} else {
				printf(
					'<small>%1$s</small> %2$s',
					home_url( '/' ),
					$field_output  // Constructed safely above.
				);
			}

			// It's a radio, let's output the field inside the label
		} else {
			// $label_output and $field_output are constructed safely above.
			printf( $label_output, esc_attr( $name ), $field_output . ' ' . esc_html( $label ) );
		}

		// Password strength is restricted to the signup_password field
		if ( 'signup_password' === $name ) :
			?>
			<div id="pass-strength-result"></div>
			<?php
		endif;

		// Email Confirm
		if ( 'signup_email' === $name ) :
			?>
			<div id="email-strength-result"></div>
			<?php
		endif;
		?>

		</div>
		<?php
	}

	/**
	 * Fires and displays any extra member registration details fields.
	 *
	 * This is a variable hook that depends on the current section.
	 *
	 * @since BuddyPress 1.9.0
	 */
	do_action( "bp_{$section}_fields" );
}

/**
 * Output a terms of service and privacy policy pages if activated
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_signup_terms_privacy() {

	$page_ids             = bp_core_get_directory_page_ids();
	$show_legal_agreement = bb_register_legal_agreement();

	$terms   = isset( $page_ids['terms'] ) ? $page_ids['terms'] : false;
	$privacy = isset( $page_ids['privacy'] ) ? $page_ids['privacy'] : (int) get_option( 'wp_page_for_privacy_policy' );

	// Do not show the page if page is not published.
	if ( false !== $terms && 'publish' !== get_post_status( $terms ) ) {
		$terms = false;
	}

	// Do not show the page if page is not published.
	if ( false !== $privacy && 'publish' !== get_post_status( $privacy ) ) {
		$privacy = false;
	}

	if ( ! $terms && ! $privacy ) {
		return false;
	}

	if ( ! empty( $terms ) && ! empty( $privacy ) ) {
		$terms_link   = '<a class="popup-modal-register popup-terms" href="#terms-modal">' . get_the_title( $terms ) . '</a>';
		$privacy_link = '<a class="popup-modal-register popup-privacy" href="#privacy-modal">' . get_the_title( $privacy ) . '</a>';
		?>
		<?php if ( $show_legal_agreement ) { ?>
			<div class="input-options checkbox-options">
				<div class="bp-checkbox-wrap">
					<input type="checkbox" name="legal_agreement" id="legal_agreement" value="1" class="bs-styled-checkbox">
					<label for="legal_agreement" class="option-label"><?php printf( __( 'I agree to the %1$s and %2$s.', 'buddyboss' ), $terms_link, $privacy_link ); ?></label>
				</div>
			</div>
		<?php } else { ?>
			<p class="register-privacy-info">
				<?php printf( __( 'By creating an account you are agreeing to the %1$s and %2$s.', 'buddyboss' ), $terms_link, $privacy_link ); ?>
			</p>
		<?php } ?>
		<div id="terms-modal" class="mfp-hide registration-popup bb-modal">
			<h1><?php echo esc_html( get_the_title( $terms ) ); ?></h1>
			<?php
			$get_terms = get_post( $terms );
			echo apply_filters( 'bp_term_of_service_content', apply_filters( 'the_content', $get_terms->post_content ), $get_terms->post_content );
			?>
			<button title="<?php esc_attr_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close"><?php esc_html_e( '×', 'buddyboss' ); ?></button>
		</div>
		<div id="privacy-modal" class="mfp-hide registration-popup bb-modal">
			<h1><?php echo esc_html( get_the_title( $privacy ) ); ?></h1>
			<?php
			$get_privacy = get_post( $privacy );
			echo apply_filters( 'bp_privacy_policy_content', apply_filters( 'the_content', $get_privacy->post_content ), $get_privacy->post_content );
			?>
			<button title="<?php esc_attr_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close"><?php esc_html_e( '×', 'buddyboss' ); ?></button>
		</div>
		<?php
	} elseif ( empty( $terms ) && ! empty( $privacy ) ) {
		$privacy_link = '<a class="popup-modal-register popup-privacy" href="#privacy-modal">' . get_the_title( $privacy ) . '</a>';
		?>
		<?php if ( $show_legal_agreement ) { ?>
			<div class="input-options checkbox-options">
				<div class="bp-checkbox-wrap">
					<input type="checkbox" name="legal_agreement" id="legal_agreement" value="1" class="bs-styled-checkbox">
					<label for="legal_agreement" class="option-label"><?php printf( __( 'I agree to the %s.', 'buddyboss' ), $privacy_link ); ?></label>
				</div>
			</div>
		<?php } else { ?>
			<p class="register-privacy-info">
				<?php printf( __( 'By creating an account you are agreeing to the %s.', 'buddyboss' ), $privacy_link ); ?>
			</p>
		<?php } ?>
		<div id="privacy-modal" class="mfp-hide registration-popup bb-modal">
			<h1><?php echo esc_html( get_the_title( $privacy ) ); ?></h1>
			<?php
			$get_privacy = get_post( $privacy );
			echo apply_filters( 'bp_privacy_policy_content', apply_filters( 'the_content', $get_privacy->post_content ), $get_privacy->post_content );
			?>
			<button title="<?php esc_attr_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close"><?php esc_html_e( '×', 'buddyboss' ); ?></button>
		</div>
		<?php
	} elseif ( ! empty( $terms ) && empty( $privacy ) ) {
		$terms_link = '<a class="popup-modal-register popup-terms" href="#terms-modal">' . get_the_title( $terms ) . '</a>';
		?>
		<?php if ( $show_legal_agreement ) { ?>
			<div class="input-options checkbox-options">
				<div class="bp-checkbox-wrap">
					<input type="checkbox" name="legal_agreement" id="legal_agreement" value="1" class="bs-styled-checkbox">
					<label for="legal_agreement" class="option-label"><?php printf( __( 'I agree to the %s.', 'buddyboss' ), $terms_link ); ?></label>
				</div>
			</div>
		<?php } else { ?>
			<p class="register-privacy-info">
				<?php printf( __( 'By creating an account you are agreeing to the %s.', 'buddyboss' ), $terms_link ); ?>
			</p>
		<?php } ?>

		<div id="terms-modal" class="mfp-hide registration-popup bb-modal">
			<h1><?php echo esc_html( get_the_title( $terms ) ); ?></h1>
			<?php
			$get_terms = get_post( $terms );
			echo apply_filters( 'bp_term_of_service_content', apply_filters( 'the_content', $get_terms->post_content ), $get_terms->post_content );
			?>
			<button title="<?php esc_attr_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close"><?php esc_html_e( '×', 'buddyboss' ); ?></button>
		</div>
		<?php
	}

	if ( $show_legal_agreement ) {
		do_action( 'bp_legal_agreement_errors' );
	}
}

/**
 * Output a submit button and the nonce for the requested action.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $action The action to get the submit button for. Required.
 */
function bp_nouveau_submit_button( $action ) {
	$submit_data = bp_nouveau_get_submit_button( $action );
	if ( empty( $submit_data['attributes'] ) || empty( $submit_data['nonce'] ) ) {
		return;
	}

	if ( ! empty( $submit_data['before'] ) ) {

		/**
		 * Fires before display of the submit button.
		 *
		 * This is a dynamic filter that is dependent on the "before" value provided by bp_nouveau_get_submit_button().
		 *
		 * @since BuddyPress 3.0.0
		 */
		do_action( $submit_data['before'] );
	}

	$submit_input = sprintf(
		'<input type="submit" %s/>',
		bp_get_form_field_attributes( 'submit', $submit_data['attributes'] )  // Safe.
	);

	// Output the submit button.
	if ( isset( $submit_data['wrapper'] ) && false === $submit_data['wrapper'] ) {
		echo $submit_input;

		// Output the submit button into a wrapper.
	} else {
		printf( '<div class="submit">%s</div>', $submit_input );
	}

	if ( empty( $submit_data['nonce_key'] ) ) {
		wp_nonce_field( $submit_data['nonce'] );
	} else {
		wp_nonce_field( $submit_data['nonce'], $submit_data['nonce_key'] );
	}

	if ( ! empty( $submit_data['after'] ) ) {

		/**
		 * Fires before display of the submit button.
		 *
		 * This is a dynamic filter that is dependent on the "after" value provided by bp_nouveau_get_submit_button().
		 *
		 * @since BuddyPress 3.0.0
		 */
		do_action( $submit_data['after'] );
	}
}

/**
 * Display supplemental error or feedback messages.
 *
 * This template handles in page error or feedback messages e.g signup fields
 * 'Username exists' type registration field error notices.
 *
 * @param string $message required: the message to display.
 * @param string $type optional: the type of error message e.g 'error'.
 *
 * @since BuddyPress 3.0.0
 */
function nouveau_error_template( $message = '', $type = '' ) {
	if ( ! $message ) {
		return;
	}

	$type = ( $type ) ? $type : 'error';
	?>

	<div class="<?php echo esc_attr( 'bp-messages bp-feedback ' . $type ); ?>">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php echo esc_html( $message ); ?></p>
	</div>

	<?php
}

/**
 * Displays the nav item link class.
 *
 * @since BuddyBoss 1.9.3
 */
function bp_nouveau_nav_link_class() {
	echo esc_attr( bp_nouveau_get_nav_link_class() );
}

/**
 * Retrieve the class attribute of the link for the current nav item.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return string The link class for the nav item.
 */
function bp_nouveau_get_nav_link_class() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$link_class = '';

	if ( ! empty( $nav_item->css_class ) ) {
		$link_class = $nav_item->css_class;
	}

	/**
	 * Filter to edit the link class attribute of the nav.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $link_class The link class attribute for the nav item.
	 * @param object $nav_item   The current nav item object.
	 * @param string $value      The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return apply_filters( 'bp_nouveau_get_nav_link_class', $link_class, $nav_item, $bp_nouveau->displayed_nav );
}
