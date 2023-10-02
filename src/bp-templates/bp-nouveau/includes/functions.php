<?php
/**
 * Common functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * This function looks scarier than it actually is. :)
 * Each object loop (activity/members/groups/blogs/forums) contains default
 * parameters to show specific information based on the page we are currently
 * looking at.
 *
 * The following function will take into account any cookies set in the JS and
 * allow us to override the parameters sent. That way we can change the results
 * returned without reloading the page.
 *
 * By using cookies we can also make sure that user settings are retained
 * across page loads.
 *
 * @param string $query_string Query string for the current request.
 * @param string $object Object for cookie.
 *
 * @return string Query string for the component loops
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_querystring( $query_string, $object ) {
	if ( empty( $object ) ) {
		return '';
	}

	// Default query
	$post_query = array(
		'filter'       => '',
		'scope'        => 'all',
		'page'         => 1,
		'search_terms' => '',
		'extras'       => '',
	);

	if ( ! empty( $_POST ) ) {

		$post_query = bp_parse_args( $_POST, $post_query );

		// Make sure to transport the scope, filter etc.. in HeartBeat Requests
		if ( ! empty( $post_query['data']['bp_heartbeat'] ) ) {
			$bp_heartbeat = $post_query['data']['bp_heartbeat'];

			// Remove heartbeat specific vars
			$post_query = array_diff_key(
				bp_parse_args( $bp_heartbeat, $post_query ),
				array(
					'data'      => false,
					'interval'  => false,
					'_nonce'    => false,
					'action'    => false,
					'screen_id' => false,
					'has_focus' => false,
				)
			);
		}
	}

	// Init the query string
	$qs = array();

	// Activity feed filtering on action.
	if ( ! empty( $post_query['filter'] ) && '-1' !== $post_query['filter'] ) {
		if ( 'notifications' === $object ) {
			$qs[] = 'component_action=' . $post_query['filter'];
		} else {
			$qs[] = 'type=' . $post_query['filter'];
			$qs[] = 'action=' . $post_query['filter'];
		}
	}

	// Sort the notifications if needed
	if ( ! empty( $post_query['extras'] ) && 'notifications' === $object ) {
		$qs[] = 'sort_order=' . $post_query['extras'];
	}

	if ( 'personal' === $post_query['scope'] && 'document' !== $object ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
		$qs[]    = 'user_id=' . $user_id;
	}

	// Activity feed scope only on activity directory.
	if ( 'all' !== $post_query['scope'] && ! bp_displayed_user_id() && ! bp_is_single_item() ) {
		$qs[] = 'scope=' . $post_query['scope'];
	}

	// If page have been passed via the AJAX post request, use those.
	if ( '-1' != $post_query['page'] ) {
		$qs[] = 'page=' . absint( $post_query['page'] );
	}

	// Excludes activity just posted and avoids duplicate ids.
	if ( ! empty( $post_query['exclude_just_posted'] ) ) {
		$just_posted = wp_parse_id_list( $post_query['exclude_just_posted'] );
		$qs[]        = 'exclude=' . implode( ',', $just_posted );
	}

	// To get newest activities.
	if ( ! empty( $post_query['offset'] ) ) {
		$qs[] = 'offset=' . intval( $post_query['offset'] );
	}

	if ( isset( $_POST['member_type_id'] ) && '' !== $_POST['member_type_id'] && 'all' !== $_POST['member_type_id'] && 'undefined' !== $_POST['member_type_id'] ) {
		$member_type_id  = $_POST['member_type_id'];
		$member_type_key = get_post_meta( $member_type_id, '_bp_member_type_key', true );
		$qs[]            = 'member_type=' . $member_type_key;
	}

	if ( isset( $_POST['group_type'] ) && '' !== $_POST['group_type'] && 'all' !== $_POST['group_type'] && 'undefined' !== $_POST['group_type'] ) {
		$group_type = $_POST['group_type'];
		$qs[]       = 'group_type=' . $group_type;
	}

	$object_search_text = bp_get_search_default_text( $object );
	if ( ! empty( $post_query['search_terms'] ) && $object_search_text != $post_query['search_terms'] && 'false' != $post_query['search_terms'] && 'undefined' != $post_query['search_terms'] ) {
		$qs[] = 'search_terms=' . urlencode( $_POST['search_terms'] );
	}

	// Specific to messages
	if ( 'messages' === $object ) {
		if ( ! empty( $post_query['box'] ) ) {
			$qs[] = 'box=' . $post_query['box'];
		}
		if ( ! empty( $post_query['thread_type'] ) ) {
			$qs[] = 'thread_type=' . $post_query['thread_type'];
		}
	}

	// Single activity.
	if ( bp_is_single_activity() ) {
		$qs = array(
			'display_comments=threaded',
			'show_hidden=true',
			'include=' . bp_current_action(),
		);
	}

	// Now pass the querystring to override default values.
	$query_string = empty( $qs ) ? '' : join( '&', (array) $qs );

	// List the variables for the filter
	list( $filter, $scope, $page, $search_terms, $extras ) = array_values( $post_query );

	/**
	 * Filters the AJAX query string for the component loops.
	 *
	 * @param string $query_string The query string we are working with.
	 * @param string $object The type of page we are on.
	 * @param string $filter The current object filter.
	 * @param string $scope The current object scope.
	 * @param string $page The current object page.
	 * @param string $search_terms The current object search terms.
	 * @param string $extras The current object extras.
	 *
	 * @since BuddyPress 3.0.0
	 */
	return apply_filters( 'bp_nouveau_ajax_querystring', $query_string, $object, $filter, $scope, $page, $search_terms, $extras );
}

/**
 * Output ajax button for action (e.g. request connection with member, join group, leave group, follow member, etc.)
 *
 * @return string
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_button( $output = '', $button = null, $before = '', $after = '', $r = array() ) {
	if ( empty( $button->component ) ) {
		return $output;
	}

	// Custom data attribute.
	$r['button_attr']['data-bp-btn-action'] = $button->id;

	$reset_ids = array(
		'member_friendship' => true,
		'member_follow'     => true,
		'group_membership'  => true,
	);

	if ( ! empty( $reset_ids[ $button->id ] ) ) {
		$parse_class = array_map( 'sanitize_html_class', explode( ' ', $r['button_attr']['class'] ) );
		if ( false === $parse_class ) {
			return $output;
		}

		$find_id = array_intersect(
			$parse_class,
			array(
				'pending_friend',
				'is_friend',
				'not_friends',
				'leave-group',
				'join-group',
				'accept-invite',
				'membership-requested',
				'request-membership',
				'not_following',
				'following',
			)
		);

		if ( 1 !== count( $find_id ) ) {
			return $output;
		}

		$data_attribute = reset( $find_id );
		if ( 'pending_friend' === $data_attribute ) {
			$data_attribute = str_replace( '_friend', '', $data_attribute );
		} elseif ( 'group_membership' === $button->id ) {
			$data_attribute = str_replace( '-', '_', $data_attribute );
		}

		$r['button_attr']['data-bp-btn-action'] = $data_attribute;
	}

	// Re-render the button with our custom data attribute.
	$output = new BP_Core_HTML_Element(
		array(
			'element'    => $r['button_element'],
			'attr'       => $r['button_attr'],
			'inner_html' => ! empty( $r['link_text'] ) ? $r['link_text'] : '',
		)
	);
	$output = $output->contents();

	// Add span bp-screen-reader-text class
	return $before . $output . $after;
}

/**
 * Output HTML content into a wrapper.
 *
 * @param array $args {
 *     Optional arguments.
 *
 * @type string $container String HTML container type that should wrap
 *                                     the items as a group: 'div', 'ul', or 'p'. Required.
 * @type string $container_id The group wrapping container element ID
 * @type string $container_classes The group wrapping container elements class
 * @type string $output The HTML to output. Required.
 * }
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_wrapper( $args = array() ) {
	/**
	 * Classes need to be determined & set by component to a certain degree
	 *
	 * Check the component to find a default container_class to add
	 */
	$current_component_class = bp_current_component() . '-meta';
	$generic_class           = 'bp-generic-meta';

	$r = bp_parse_args(
		$args,
		array(
			'container'         => 'div',
			'container_id'      => '',
			'container_classes' => array( $generic_class, $current_component_class ),
			'output'            => '',
		)
	);

	$valid_containers = array(
		'div'  => true,
		'ul'   => true,
		'ol'   => true,
		'span' => true,
		'p'    => true,
	);

	// Actually merge some classes defaults and $args
	// @todo This is temp, we need certain classes but maybe improve this approach.
	$default_classes        = array( 'action' );
	$r['container_classes'] = array_merge( $r['container_classes'], $default_classes );

	if ( empty( $r['container'] ) && ! empty( $r['output'] ) ) {
		printf( $r['output'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	} elseif ( empty( $r['container'] ) || ! isset( $valid_containers[ $r['container'] ] ) || empty( $r['output'] ) ) {
		return;
	}

	$container         = $r['container'];
	$container_id      = '';
	$container_classes = '';
	$output            = trim( $r['output'] );

	if ( ! empty( $r['container_id'] ) ) {
		$container_id = ' id="' . esc_attr( $r['container_id'] ) . '"';
	}

	if ( ! empty( $r['container_classes'] ) && is_array( $r['container_classes'] ) ) {
		$container_classes = ' class="' . join( ' ', array_map( 'sanitize_html_class', $r['container_classes'] ) ) . '"';
	}

	// Print the wrapper and its content.
	printf( '<%1$s%2$s%3$s>%4$s</%1$s>', $container, $container_id, $container_classes, $output );
}

/**
 * Check if Nouveau sidebar object nav widget is active.
 *
 * @return bool
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_is_object_nav_in_sidebar() {
	return is_active_widget( false, false, 'bp_nouveau_sidebar_object_nav_widget', true );
}

/**
 * Check if logged in user has queried capability.
 *
 * @return bool
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_current_user_can( $capability = '' ) {
	/**
	 * Filters whether or not the current user can perform an action for BuddyPress Nouveau.
	 *
	 * @param bool $value Whether or not the user is logged in.
	 * @param string $capability Current capability being checked.
	 * @param int $value Current logged in user ID.
	 *
	 * @since BuddyPress 3.0.0
	 */
	return apply_filters( 'bp_nouveau_current_user_can', is_user_logged_in(), $capability, bp_loggedin_user_id() );
}

/**
 * Parse an html output to a list of component's directory nav item.
 *
 * @param string $hook The hook to fire.
 * @param string $component The component nav belongs to.
 * @param int    $position The position of the nav item.
 *
 * @return array A list of component's dir nav items
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_parse_hooked_dir_nav( $hook = '', $component = '', $position = 99 ) {
	$extra_nav_items = array();

	if ( empty( $hook ) || empty( $component ) || ! has_action( $hook ) ) {
		return $extra_nav_items;
	}

	// Get the hook output.
	ob_start();

	/**
	 * Fires at the start of the output for `bp_nouveau_parse_hooked_dir_nav()`.
	 *
	 * This hook is variable and depends on the hook parameter passed in.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( $hook );
	$output = ob_get_clean();

	if ( empty( $output ) ) {
		return $extra_nav_items;
	}

	preg_match_all( "/<li\sid=\"{$component}\-(.*)\"[^>]*>/siU", $output, $lis );
	if ( empty( $lis[1] ) ) {
		return $extra_nav_items;
	}

	$extra_nav_items = array_fill_keys(
		$lis[1],
		array(
			'component' => $component,
			'position'  => $position,
		)
	);
	preg_match_all( '/<a\s[^>]*>(.*)<\/a>/siU', $output, $as );

	if ( ! empty( $as[0] ) ) {
		foreach ( $as[0] as $ka => $a ) {
			$extra_nav_items[ $lis[1][ $ka ] ]['slug'] = $lis[1][ $ka ];
			$extra_nav_items[ $lis[1][ $ka ] ]['text'] = $as[1][ $ka ];
			preg_match_all( '/([\w\-]+)=([^"\'> ]+|([\'"]?)(?:[^\3]|\3+)+?\3)/', $a, $attrs );

			if ( ! empty( $attrs[1] ) ) {
				foreach ( $attrs[1] as $katt => $att ) {
					if ( 'href' === $att ) {
						$extra_nav_items[ $lis[1][ $ka ] ]['link'] = trim( $attrs[2][ $katt ], '"' );
					} else {
						$extra_nav_items[ $lis[1][ $ka ] ][ $att ] = trim( $attrs[2][ $katt ], '"' );
					}
				}
			}
		}
	}

	if ( ! empty( $as[1] ) ) {
		foreach ( $as[1] as $ks => $s ) {
			preg_match_all( '/<span>(.*)<\/span>/siU', $s, $spans );

			if ( empty( $spans[0] ) ) {
				$extra_nav_items[ $lis[1][ $ks ] ]['count'] = false;
			} elseif ( ! empty( $spans[1][0] ) ) {
				$extra_nav_items[ $lis[1][ $ks ] ]['count'] = (int) $spans[1][0];
			} else {
				$extra_nav_items[ $lis[1][ $ks ] ]['count'] = '';
			}
		}
	}

	return $extra_nav_items;
}

/**
 * Run specific "select filter" hooks to catch the options and build an array out of them
 *
 * @param string $hook
 * @param array  $filters
 *
 * @return array
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_parse_hooked_options( $hook = '', $filters = array() ) {
	if ( empty( $hook ) ) {
		return $filters;
	}

	ob_start();

	/**
	 * Fires at the start of the output for `bp_nouveau_parse_hooked_options()`.
	 *
	 * This hook is variable and depends on the hook parameter passed in.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( $hook );

	$output = ob_get_clean();

	preg_match_all( '/<option value="(.*?)"\s*>(.*?)<\/option>/', $output, $matches );

	if ( ! empty( $matches[1] ) && ! empty( $matches[2] ) ) {
		foreach ( $matches[1] as $ik => $key_action ) {
			if ( ! empty( $matches[2][ $ik ] ) && ! isset( $filters[ $key_action ] ) ) {
				$filters[ $key_action ] = $matches[2][ $ik ];
			}
		}
	}

	return $filters;
}

/**
 * Get Dropdawn filters for the current component of the one passed in params
 *
 * @param string $context 'directory', 'user' or 'group'
 * @param string $component The BuddyPress component ID
 *
 * @return array the dropdown filters
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_component_filters( $context = '', $component = '' ) {
	$filters = array();

	if ( empty( $context ) ) {
		if ( bp_is_user() ) {
			$context = 'user';
		} elseif ( bp_is_group() ) {
			$context = 'group';

			// Defaults to directory
		} else {
			$context = 'directory';
		}
	}

	if ( empty( $component ) ) {
		if ( 'directory' === $context || 'user' === $context ) {
			$component = bp_current_component();

			if ( 'friends' === $component ) {
				$context   = 'friends';
				$component = 'members';
			}
		} elseif ( 'group' === $context && bp_is_group_activity() ) {
			$component = 'activity';
		} elseif ( 'group' === $context && bp_is_group_members() ) {
			$component = 'members';
		}
	}

	if ( ! bp_is_active( $component ) ) {
		return $filters;
	}

	if ( 'members' === $component ) {
		$filters = bp_nouveau_get_members_filters( $context );
	} elseif ( 'activity' === $component ) {
		$filters = bp_nouveau_get_activity_filters();

		// Specific case for the activity dropdown
		$filters = array_merge( array( '-1' => __( '- View All -', 'buddyboss' ) ), $filters );
	} elseif ( 'groups' === $component ) {
		$filters = bp_nouveau_get_groups_filters( $context );
	} elseif ( 'blogs' === $component ) {
		$filters = bp_nouveau_get_blogs_filters( $context );
	}

	return $filters;
}

/**
 * When previewing make sure to get the temporary setting of the customizer.
 * This is necessary when we need to get these very early.
 *
 * @param string $option the index of the setting to get.
 * @param mixed  $retval the value to use as default.
 *
 * @return mixed The value for the requested option.
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_temporary_setting( $option = '', $retval = false ) {
	if ( empty( $option ) || ! isset( $_POST['customized'] ) ) {
		return $retval;
	}

	$temporary_setting = wp_unslash( $_POST['customized'] );
	if ( ! is_array( $temporary_setting ) ) {
		$temporary_setting = json_decode( $temporary_setting, true );
	}

	// This is used to transport the customizer settings into Ajax requests.
	if ( 'any' === $option ) {
		$retval = array();

		foreach ( $temporary_setting as $key => $setting ) {
			if ( 0 !== strpos( $key, 'bp_nouveau_appearance' ) ) {
				continue;
			}

			$k            = str_replace( array( '[', ']' ), array( '_', '' ), $key );
			$retval[ $k ] = $setting;
		}

		// Used when it's an early regular request
	} elseif ( isset( $temporary_setting[ 'bp_nouveau_appearance[' . $option . ']' ] ) ) {
		$retval = $temporary_setting[ 'bp_nouveau_appearance[' . $option . ']' ];

		// Used when it's an ajax request
	} elseif ( isset( $_POST['customized'][ 'bp_nouveau_appearance_' . $option ] ) ) {
		$retval = $_POST['customized'][ 'bp_nouveau_appearance_' . $option ];
	}

	return $retval;
}

/**
 * Get the BP Nouveau Appearance settings.
 *
 * @param string                                                                  $option Leave empty to get all settings, specify a value for a specific one.
 * @param mixed          An array of settings, the value of the requested setting.
 *
 * @return array|false|mixed
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_appearance_settings( $option = '' ) {

	$default_args = array(
		'user_nav_display'             => 0, // O is default (horizontally). 1 is vertically.
		'user_nav_order'               => array(),
		'user_nav_hide'                => array(),
		'user_profile_actions_display' => array(),
		'user_profile_actions_order'   => array(),
		'members_layout'               => 4,
		'members_dir_tabs'             => 0,
		'members_dir_layout'           => 0,
		'bp_emails'                    => '',
		'user_default_tab'             => 'profile',
	);

	if ( bp_is_active( 'friends' ) ) {
		$default_args['members_friends_layout'] = 4;
	}

	if ( bp_is_active( 'activity' ) ) {
		$default_args['activity_dir_layout'] = 0;
		$default_args['activity_dir_tabs']   = 0; // default = no tabs
	}

	if ( bp_is_active( 'groups' ) ) {
		$default_args = array_merge(
			$default_args,
			array(
				'group_front_page'        => 1,
				'group_front_boxes'       => 1,
				'group_front_description' => 0,
				'group_nav_display'       => 0,       // O is default (horizontally). 1 is vertically.
				'group_nav_order'         => array(),
				'groups_layout'           => 4,
				'members_group_layout'    => 4,
				'groups_dir_layout'       => 0,
				'groups_dir_tabs'         => 0,
				'group_default_tab'       => 'members',
			)
		);
	}

	if ( is_multisite() && bp_is_active( 'blogs' ) ) {
		$default_args = array_merge(
			$default_args,
			array(
				'sites_dir_layout' => 0,
				'sites_dir_tabs'   => 0,
			)
		);
	}

	$settings = bp_parse_args(
		bp_get_option( 'bp_nouveau_appearance', array() ),
		$default_args,
		'nouveau_appearance_settings'
	);

	if ( ! empty( $option ) ) {
		if ( isset( $settings[ $option ] ) ) {
			return $settings[ $option ];
		} else {
			return false;
		}
	}

	return $settings;
}

/**
 * Sanitize a list of slugs to save it as an array
 *
 * @param string $option A comma separated list of nav items slugs.
 *
 * @return array An array of nav items slugs.
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_sanitize_nav_order( $option = '' ) {
	if ( ! is_array( $option ) ) {
		$option = explode( ',', $option );
	}

	return array_map( 'sanitize_key', $option );
}

/**
 * Sanitize a list of slugs to save it as an array
 *
 * @param string $option A comma separated list of nav items slugs.
 *
 * @return array An array of nav items slugs.
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_sanitize_nav_hide( $option = '' ) {
	if ( ! is_array( $option ) ) {
		$option = explode( ',', $option );
	}

	return array_map( 'sanitize_key', $option );
}

/**
 * BP Nouveau's callback for the cover photo feature.
 *
 * @param array $params Optional. The current component's feature parameters.
 *
 * @return string
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_theme_cover_image( $params = array() ) {
	if ( empty( $params ) ) {
		return '';
	}

	// Avatar height - padding - 1/2 avatar height.
	$avatar_offset = $params['height'] - 5 - round( (int) bp_core_avatar_full_height() / 2 );

	// Header content offset + spacing.
	$top_offset  = bp_core_avatar_full_height() - 10;
	$left_offset = bp_core_avatar_full_width() + 20;

	$cover_image       = isset( $params['cover_image'] ) ? 'background-image: url( ' . $params['cover_image'] . ' );' : '';
	$hide_avatar_style = '';

	// Adjust the cover photo header, in case avatars are completely disabled.
	if ( ! buddypress()->avatar->show_avatars ) {
		$hide_avatar_style = '
			#buddypress #item-header-cover-image #item-header-avatar {
				display:  none;
			}
		';

		if ( bp_is_user() ) {
			$hide_avatar_style = '
				#buddypress #item-header-cover-image #item-header-avatar a {
					display: block;
					height: ' . $top_offset . 'px;
					margin: 0 15px 19px 0;
				}

				#buddypress div#item-header #item-header-cover-image #item-header-content {
					margin-left:auto;
				}
			';
		}
	}

	return '
		/* cover photo */
		#buddypress #header-cover-image {
			height: ' . $params['height'] . 'px;
			' . $cover_image . '
		}

		' . $hide_avatar_style . '

		#buddypress div#item-header-cover-image h2 a,
		#buddypress div#item-header-cover-image h2 {
			color: #fff;
			text-rendering: optimizelegibility;
			text-shadow: 0px 0px 3px rgba( 0, 0, 0, 0.8 );
			margin: 0 0 .6em;
			font-size:200%;
		}

		#buddypress #item-header-cover-image #item-buttons {
			margin: 0 0 10px;
			padding: 0 0 5px;
		}

		#buddypress #item-header-cover-image #item-buttons:after {
			clear: both;
			content: "";
			display: table;
		}

		@media screen and (max-width: 782px) {
			#buddypress #item-header-cover-image #item-header-avatar,
			.bp-user #buddypress #item-header #item-header-cover-image #item-header-avatar,
			#buddypress div#item-header #item-header-cover-image #item-header-content {
				width:100%;
				text-align:center;
			}

			#buddypress div#item-header #item-header-cover-image #item-header-content,
			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-header-content,
			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
				margin:0;
			}

			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-header-content,
			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
				max-width: 100%;
			}

			#buddypress div#item-header-cover-image h2 a,
			#buddypress div#item-header-cover-image h2 {
				color: inherit;
				text-shadow: none;
				margin:25px 0 0;
				font-size:200%;
			}

			#buddypress #item-header-cover-image #item-buttons div {
				float:none;
				display:inline-block;
			}

			#buddypress #item-header-cover-image #item-buttons:before {
				content:"";
			}

			#buddypress #item-header-cover-image #item-buttons {
				margin: 5px 0;
			}
		}
	';
}

/**
 * All user feedback messages are available here
 *
 * @param string $feedback_id The ID of the message.
 *
 * @return string|false The list of parameters for the message
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_user_feedback( $feedback_id = '' ) {
	/**
	 * Filters the BuddyPress Nouveau feedback messages.
	 *
	 * Use this filter to add your custom feedback messages.
	 *
	 * @param array $value The list of feedback messages.
	 *
	 * @since BuddyPress 3.0.0
	 */
	$feedback_messages = apply_filters(
		'bp_nouveau_feedback_messages',
		array(
			'registration-disabled'             => array(
				'type'    => 'info',
				'message' => __( 'Member registration is currently disabled.', 'buddyboss' ),
				'before'  => 'bp_before_registration_disabled',
				'after'   => 'bp_after_registration_disabled',
			),
			'completed-confirmation'            => array(
				'type'    => 'info',
				'message' => __( 'You have successfully created your account! Please log in using the email and password you have just created.', 'buddyboss' ),
				'before'  => 'bp_before_registration_confirmed',
				'after'   => 'bp_after_registration_confirmed',
			),
			'directory-activity-loading'        => array(
				'type'    => 'loading',
				'message' => __( 'Loading community updates. Please wait.', 'buddyboss' ),
			),
			'activity-comments-loading'         => array(
				'type'    => 'loading',
				'message' => __( 'Loading activity comments. Please wait.', 'buddyboss' ),
			),
			'single-activity-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading the update. Please wait.', 'buddyboss' ),
			),
			'activity-loop-none'                => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there was no activity found.', 'buddyboss' ),
			),
			'blogs-loop-none'                   => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there were no sites found.', 'buddyboss' ),
			),
			'blogs-no-signup'                   => array(
				'type'    => 'info',
				'message' => __( 'Site registration is currently disabled.', 'buddyboss' ),
			),
			'directory-blogs-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading the sites of the network. Please wait.', 'buddyboss' ),
			),
			'directory-groups-loading'          => array(
				'type'    => 'loading',
				'message' => __( 'Loading groups of the community. Please wait.', 'buddyboss' ),
			),
			'groups-loop-none'                  => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there were no groups found.', 'buddyboss' ),
			),
			'group-activity-loading'            => array(
				'type'    => 'loading',
				'message' => __( 'Loading the group updates. Please wait.', 'buddyboss' ),
			),
			'group-members-loading'             => array(
				'type'    => 'loading',
				'message' => __( 'Requesting the group members. Please wait.', 'buddyboss' ),
			),
			'group-leaders-loading'             => array(
				'type'    => 'loading',
				'message' => __( 'Requesting the group leaders. Please wait.', 'buddyboss' ),
			),
			'group-media-loading'               => array(
				'type'    => 'loading',
				'message' => __( 'Requesting the group photos. Please wait.', 'buddyboss' ),
			),
			'group-video-loading'               => array(
				'type'    => 'loading',
				'message' => __( 'Requesting the group videos. Please wait.', 'buddyboss' ),
			),
			'group-document-loading'            => array(
				'type'    => 'loading',
				'message' => __( 'Requesting the group documents. Please wait.', 'buddyboss' ),
			),
			'group-members-none'                => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no group members were found.', 'buddyboss' ),
			),
			'group-members-search-none'         => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there was no member with that name found within this group.', 'buddyboss' ),
			),
			'group-manage-members-none'         => array(
				'type'    => 'info',
				'message' => __( 'This group has no members.', 'buddyboss' ),
			),
			'group-requests-none'               => array(
				'type'    => 'info',
				'message' => __( 'There are no pending membership requests.', 'buddyboss' ),
			),
			'group-requested-membership'        => array(
				'type'    => 'info',
				'message' => __( 'You have already requested to join this group.', 'buddyboss' ),
			),
			'group-requests-loading'            => array(
				'type'    => 'loading',
				'message' => __( 'Loading the members who requested to join the group. Please wait.', 'buddyboss' ),
			),
			'group-delete-warning'              => array(
				'type'    => 'warning',
				'message' => __( 'WARNING: Deleting this group will completely remove ALL content associated with it. There is no way back. Please be careful with this option.', 'buddyboss' ),
			),
			'group-avatar-delete-info'          => array(
				'type'    => 'info',
				'message' => __( 'To remove the existing group photo, please use the delete group profile photo button.', 'buddyboss' ),
			),
			'directory-members-loading'         => array(
				'type'    => 'loading',
				'message' => __( 'Loading members of the community. Please wait.', 'buddyboss' ),
			),
			'members-loop-none'                 => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no members were found.', 'buddyboss' ),
			),
			'member-requests-none'              => array(
				'type'    => 'info',
				'message' => __( 'You have no pending requests to connect.', 'buddyboss' ),
			),
			'member-mutual-friends-none'        => array(
				'type'    => 'loading',
				'message' => __( 'You have no mutual connections with this member.', 'buddyboss' ),
			),
			'member-invites-none'               => array(
				'type'    => 'info',
				'message' => __( 'You have no outstanding group invites.', 'buddyboss' ),
			),
			'member-notifications-none'         => array(
				'type'    => 'info',
				'message' => __( 'This member has no notifications.', 'buddyboss' ),
			),
			'member-wp-profile-none'            => array(
				'type'    => 'info',
				'message' => __( '%s did not save any profile information yet.', 'buddyboss' ),
			),
			'member-delete-account'             => array(
				'type'    => 'warning',
				'message' => __( 'Deleting this account will delete all of the content it has created. It will be completely unrecoverable.', 'buddyboss' ),
			),
			'member-activity-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading member\'s updates. Please wait.', 'buddyboss' ),
			),
			'member-blogs-loading'              => array(
				'type'    => 'loading',
				'message' => __( 'Loading member\'s blog. Please wait.', 'buddyboss' ),
			),
			'member-friends-loading'            => array(
				'type'    => 'loading',
				'message' => __( 'Loading member\'s friends. Please wait.', 'buddyboss' ),
			),
			'member-mutual-friends-loading'     => array(
				'type'    => 'loading',
				'message' => __( 'Loading member\'s mutual connections. Please wait.', 'buddyboss' ),
			),
			'member-groups-loading'             => array(
				'type'    => 'loading',
				'message' => __( 'Loading member\'s groups. Please wait.', 'buddyboss' ),
			),
			'member-notifications-loading'      => array(
				'type'    => 'loading',
				'message' => __( 'Loading notifications. Please wait.', 'buddyboss' ),
			),
			'member-group-invites-all'          => array(
				'type'    => 'info',
				'message' => __( 'Currently every member of the community can invite you to join their groups. Optionally, you may restrict group invites to your connections only.', 'buddyboss' ),
			),
			'member-group-invites-friends-only' => array(
				'type'    => 'info',
				'message' => __( 'Currently only your connections may invite you to join a group. Uncheck this box to allow any member to send invites.', 'buddyboss' ),
			),
			'member-data-export'                => array(
				'type'    => 'info',
				'message' => __( 'You may download a copy of all data you have created on this platform. Click the button below to start a new request. An email will be sent to you to verify the request. Then the site admin will review your request and if approved, a zip file will be generated and emailed to you.', 'buddyboss' ),
			),
			'member-media-loading'              => array(
				'type'    => 'loading',
				'message' => __( 'Loading member\'s photos. Please wait.', 'buddyboss' ),
			),
			'member-document-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading member\'s documents. Please wait.', 'buddyboss' ),
			),
			'member-video-loading'              => array(
				'type'    => 'loading',
				'message' => __( 'Loading member\'s videos. Please wait.', 'buddyboss' ),
			),
			'media-loop-none'                   => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no photos were found.', 'buddyboss' ),
			),
			'media-video-loop-none'             => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no photos or videos were found.', 'buddyboss' ),
			),
			'video-loop-none'                   => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no videos were found.', 'buddyboss' ),
			),
			'document-loop-none'                => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no documents were found.', 'buddyboss' ),
			),
			'media-loop-document-none'          => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no documents were found.', 'buddyboss' ),
			),
			'member-media-none'                 => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no photos were found.', 'buddyboss' ),
			),
			'member-video-none'                 => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no videos were found.', 'buddyboss' ),
			),
			'member-media-document-none'        => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no documents were found.', 'buddyboss' ),
			),
			'media-album-none'                  => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no albums were found.', 'buddyboss' ),
			),
			'video-album-none'                  => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no albums were found.', 'buddyboss' ),
			),
			'album-media-loading'               => array(
				'type'    => 'loading',
				'message' => __( 'Loading photos from the album. Please wait.', 'buddyboss' ),
			),
			'album-media-video-loading'               => array(
				'type'    => 'loading',
				'message' => __( 'Loading photos and videos from the album. Please wait.', 'buddyboss' ),
			),
			'album-video-loading'               => array(
				'type'    => 'loading',
				'message' => __( 'Loading videos from the album. Please wait.', 'buddyboss' ),
			),
			'directory-media-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading photos from the community. Please wait.', 'buddyboss' ),
			),
			'directory-video-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading videos from the community. Please wait.', 'buddyboss' ),
			),
			'directory-media-document-loading'  => array(
				'type'    => 'loading',
				'message' => __( 'Loading documents from the community. Please wait.', 'buddyboss' ),
			),
			'moderation-block-member-loading'   => array(
				'type'    => 'loading',
				'message' => __( 'Loading blocked members. Please wait.', 'buddyboss' ),
			),
			'moderation-requests-none'          => array(
				'type'    => 'info',
				'message' => __( 'No blocked members found.', 'buddyboss' ),
			),
		)
	);

	if ( ! isset( $feedback_messages[ $feedback_id ] ) ) {
		return false;
	}

	/*
	 * Adjust some messages to the context.
	 */
	if ( 'completed-confirmation' === $feedback_id && bp_registration_needs_activation() ) {
		$feedback_messages['completed-confirmation']['message'] = __( 'Before you can login, you need to confirm your email address via the email we just sent to you.', 'buddyboss' );
	} elseif ( 'member-notifications-none' === $feedback_id ) {
		$is_myprofile = bp_is_my_profile();

		if ( bp_is_current_action( 'unread' ) ) {
			$feedback_messages['member-notifications-none']['message'] = __( 'This member has no unread notifications.', 'buddyboss' );

			if ( $is_myprofile ) {
				$feedback_messages['member-notifications-none']['message'] = __( 'You have no unread notifications.', 'buddyboss' );
			}
		} elseif ( $is_myprofile ) {
			$feedback_messages['member-notifications-none']['message'] = __( 'You have no notifications.', 'buddyboss' );
		}
	} elseif ( 'member-wp-profile-none' === $feedback_id && bp_is_user_profile() ) {
		$feedback_messages['member-wp-profile-none']['message'] = sprintf( $feedback_messages['member-wp-profile-none']['message'], bp_get_displayed_user_fullname() );
	} elseif ( 'member-delete-account' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-delete-account']['message'] = __( 'Deleting your account will delete all of the content you have created. It will be completely irrecoverable.', 'buddyboss' );
	} elseif ( 'member-activity-loading' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-activity-loading']['message'] = __( 'Loading your updates. Please wait.', 'buddyboss' );
	} elseif ( 'member-blogs-loading' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-blogs-loading']['message'] = __( 'Loading your blogs. Please wait.', 'buddyboss' );
	} elseif ( 'member-friends-loading' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-friends-loading']['message'] = __( 'Loading your connections. Please wait.', 'buddyboss' );
	} elseif ( 'member-groups-loading' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-groups-loading']['message'] = __( 'Loading your groups. Please wait.', 'buddyboss' );
	}

	/**
	 * Filter here if you wish to edit the message just before being displayed
	 *
	 * @param array $feedback_messages
	 *
	 * @since BuddyPress 3.0.0
	 */
	return apply_filters( 'bp_nouveau_get_user_feedback', $feedback_messages[ $feedback_id ] );
}

/**
 * Get the signup fields for the requested section
 *
 * @param string $section Optional. The section of fields to get 'account_details' or 'blog_details'.
 *
 * @return array|false The list of signup fields for the requested section. False if not found.
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_signup_fields( $section = '' ) {
	if ( empty( $section ) ) {
		return false;
	}

	/**
	 * Filter to add your specific 'text' or 'password' inputs
	 *
	 * If you need to use other types of field, please use the
	 * do_action( 'bp_account_details_fields' ) or do_action( 'blog_details' ) hooks instead.
	 *
	 * @param array $value The list of fields organized into sections.
	 *
	 * @since BuddyPress 3.0.0
	 */

	$email_opt    = function_exists( 'bp_register_confirm_email' ) && true === bp_register_confirm_email() ? true : false;
	$password_opt = function_exists( 'bp_register_confirm_password' ) && true === bp_register_confirm_password() ? true : false;

	if ( true === $email_opt && true === $password_opt ) {
		$fields = apply_filters(
			'bp_nouveau_get_signup_fields',
			array(
				'account_details' => array(
					'signup_email'            => array(
						'label'          => __( 'Email', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_email_value',
						'attribute_type' => 'email',
						'type'           => 'email',
						'class'          => '',
					),
					'signup_email_confirm'    => array(
						'label'          => __( 'Confirm Email', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_confirm_email_value',
						'attribute_type' => 'email',
						'type'           => 'email',
						'class'          => '',
					),
					'signup_password'         => array(
						'label'          => __( 'Password', 'buddyboss' ),
						'required'       => true,
						'value'          => '',
						'attribute_type' => 'password',
						'type'           => 'password',
						'class'          => 'password-entry',
					),
					'signup_password_confirm' => array(
						'label'          => __( 'Confirm Password', 'buddyboss' ),
						'required'       => true,
						'value'          => '',
						'attribute_type' => 'password',
						'type'           => 'password',
						'class'          => 'password-entry-confirm',
					),
				),
				'blog_details'    => array(
					'signup_blog_url'             => array(
						'label'          => __( 'Site URL', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_blog_url_value',
						'attribute_type' => 'slug',
						'type'           => 'text',
						'class'          => '',
					),
					'signup_blog_title'           => array(
						'label'          => __( 'Site Title', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_blog_title_value',
						'attribute_type' => 'title',
						'type'           => 'text',
						'class'          => '',
					),
					'signup_blog_privacy_public'  => array(
						'label'          => __( 'Yes', 'buddyboss' ),
						'required'       => false,
						'value'          => 'public',
						'attribute_type' => '',
						'type'           => 'radio',
						'class'          => '',
					),
					'signup_blog_privacy_private' => array(
						'label'          => __( 'No', 'buddyboss' ),
						'required'       => false,
						'value'          => 'private',
						'attribute_type' => '',
						'type'           => 'radio',
						'class'          => '',
					),
				),
			)
		);
	} elseif ( false === $email_opt && true === $password_opt ) {
		$fields = apply_filters(
			'bp_nouveau_get_signup_fields',
			array(
				'account_details' => array(
					'signup_email'            => array(
						'label'          => __( 'Email', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_email_value',
						'attribute_type' => 'email',
						'type'           => 'email',
						'class'          => '',
					),
					'signup_password'         => array(
						'label'          => __( 'Password', 'buddyboss' ),
						'required'       => true,
						'value'          => '',
						'attribute_type' => 'password',
						'type'           => 'password',
						'class'          => 'password-entry',
					),
					'signup_password_confirm' => array(
						'label'          => __( 'Confirm Password', 'buddyboss' ),
						'required'       => true,
						'value'          => '',
						'attribute_type' => 'password',
						'type'           => 'password',
						'class'          => 'password-entry-confirm',
					),
				),
				'blog_details'    => array(
					'signup_blog_url'             => array(
						'label'          => __( 'Site URL', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_blog_url_value',
						'attribute_type' => 'slug',
						'type'           => 'text',
						'class'          => '',
					),
					'signup_blog_title'           => array(
						'label'          => __( 'Site Title', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_blog_title_value',
						'attribute_type' => 'title',
						'type'           => 'text',
						'class'          => '',
					),
					'signup_blog_privacy_public'  => array(
						'label'          => __( 'Yes', 'buddyboss' ),
						'required'       => false,
						'value'          => 'public',
						'attribute_type' => '',
						'type'           => 'radio',
						'class'          => '',
					),
					'signup_blog_privacy_private' => array(
						'label'          => __( 'No', 'buddyboss' ),
						'required'       => false,
						'value'          => 'private',
						'attribute_type' => '',
						'type'           => 'radio',
						'class'          => '',
					),
				),
			)
		);
	} elseif ( true === $email_opt && false === $password_opt ) {
		$fields = apply_filters(
			'bp_nouveau_get_signup_fields',
			array(
				'account_details' => array(
					'signup_email'         => array(
						'label'          => __( 'Email', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_email_value',
						'attribute_type' => 'email',
						'type'           => 'email',
						'class'          => '',
					),
					'signup_email_confirm' => array(
						'label'          => __( 'Confirm Email', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_confirm_email_value',
						'attribute_type' => 'email',
						'type'           => 'email',
						'class'          => '',
					),
					'signup_password'      => array(
						'label'          => __( 'Password', 'buddyboss' ),
						'required'       => true,
						'value'          => '',
						'attribute_type' => 'password',
						'type'           => 'password',
						'class'          => 'password-entry',
					),
				),
				'blog_details'    => array(
					'signup_blog_url'             => array(
						'label'          => __( 'Site URL', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_blog_url_value',
						'attribute_type' => 'slug',
						'type'           => 'text',
						'class'          => '',
					),
					'signup_blog_title'           => array(
						'label'          => __( 'Site Title', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_blog_title_value',
						'attribute_type' => 'title',
						'type'           => 'text',
						'class'          => '',
					),
					'signup_blog_privacy_public'  => array(
						'label'          => __( 'Yes', 'buddyboss' ),
						'required'       => false,
						'value'          => 'public',
						'attribute_type' => '',
						'type'           => 'radio',
						'class'          => '',
					),
					'signup_blog_privacy_private' => array(
						'label'          => __( 'No', 'buddyboss' ),
						'required'       => false,
						'value'          => 'private',
						'attribute_type' => '',
						'type'           => 'radio',
						'class'          => '',
					),
				),
			)
		);
	} else {
		$fields = apply_filters(
			'bp_nouveau_get_signup_fields',
			array(
				'account_details' => array(
					'signup_email'    => array(
						'label'          => __( 'Email', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_email_value',
						'attribute_type' => 'email',
						'type'           => 'email',
						'class'          => '',
					),
					'signup_password' => array(
						'label'          => __( 'Password', 'buddyboss' ),
						'required'       => true,
						'value'          => '',
						'attribute_type' => 'password',
						'type'           => 'password',
						'class'          => 'password-entry',
					),
				),
				'blog_details'    => array(
					'signup_blog_url'             => array(
						'label'          => __( 'Site URL', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_blog_url_value',
						'attribute_type' => 'slug',
						'type'           => 'text',
						'class'          => '',
					),
					'signup_blog_title'           => array(
						'label'          => __( 'Site Title', 'buddyboss' ),
						'required'       => true,
						'value'          => 'bp_get_signup_blog_title_value',
						'attribute_type' => 'title',
						'type'           => 'text',
						'class'          => '',
					),
					'signup_blog_privacy_public'  => array(
						'label'          => __( 'Yes', 'buddyboss' ),
						'required'       => false,
						'value'          => 'public',
						'attribute_type' => '',
						'type'           => 'radio',
						'class'          => '',
					),
					'signup_blog_privacy_private' => array(
						'label'          => __( 'No', 'buddyboss' ),
						'required'       => false,
						'value'          => 'private',
						'attribute_type' => '',
						'type'           => 'radio',
						'class'          => '',
					),
				),
			)
		);
	}

	if ( ! bp_get_blog_signup_allowed() ) {
		unset( $fields['blog_details'] );
	}

	if ( isset( $fields[ $section ] ) ) {
		return $fields[ $section ];
	}

	return false;
}

/**
 * Get Some submit buttons data.
 *
 * @param string $action The action requested.
 *
 * @return array|false The list of the submit button parameters for the requested action
 *                     False if no actions were found.
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_submit_button( $action = '' ) {
	if ( empty( $action ) ) {
		return false;
	}

	/**
	 * Filter the Submit buttons to add your own.
	 *
	 * @param array $value The list of submit buttons.
	 *
	 * @return array|false
	 * @since BuddyPress 3.0.0
	 */
	$actions = apply_filters(
		'bp_nouveau_get_submit_button',
		array(
			'register'                      => array(
				'before'     => 'bp_before_registration_submit_buttons',
				'after'      => 'bp_after_registration_submit_buttons',
				'nonce'      => 'bp_new_signup',
				'attributes' => array(
					'name'  => 'signup_submit',
					'id'    => 'signup_submit',
					'value' => __( 'Create Account', 'buddyboss' ),
				),
			),
			'member-profile-edit'           => array(
				'before'     => '',
				'after'      => '',
				'nonce'      => 'bp_xprofile_edit',
				'attributes' => array(
					'name'  => 'profile-group-edit-submit',
					'id'    => 'profile-group-edit-submit',
					'value' => __( 'Save Changes', 'buddyboss' ),
				),
			),
			'member-capabilities'           => array(
				'before'     => 'bp_members_capabilities_account_before_submit',
				'after'      => 'bp_members_capabilities_account_after_submit',
				'nonce'      => 'capabilities',
				'attributes' => array(
					'name'  => 'capabilities-submit',
					'id'    => 'capabilities-submit',
					'value' => __( 'Save', 'buddyboss' ),
				),
			),
			'member-delete-account'         => array(
				'before'     => 'bp_members_delete_account_before_submit',
				'after'      => 'bp_members_delete_account_after_submit',
				'nonce'      => 'delete-account',
				'attributes' => array(
					'disabled' => 'disabled',
					'name'     => 'delete-account-button',
					'id'       => 'delete-account-button',
					'value'    => __( 'Delete Account', 'buddyboss' ),
				),
			),
			'members-general-settings'      => array(
				'before'     => 'bp_core_general_settings_before_submit',
				'after'      => 'bp_core_general_settings_after_submit',
				'nonce'      => 'bp_settings_general',
				'attributes' => array(
					'name'  => 'submit',
					'id'    => 'submit',
					'value' => __( 'Save Changes', 'buddyboss' ),
					'class' => 'auto',
				),
			),
			'member-notifications-settings' => array(
				'before'     => 'bp_members_notification_settings_before_submit',
				'after'      => 'bp_members_notification_settings_after_submit',
				'nonce'      => 'bp_settings_notifications',
				'attributes' => array(
					'name'  => 'submit',
					'id'    => 'submit',
					'value' => __( 'Save Changes', 'buddyboss' ),
					'class' => 'auto',
				),
			),
			'members-profile-settings'      => array(
				'before'     => 'bp_core_xprofile_settings_before_submit',
				'after'      => 'bp_core_xprofile_settings_after_submit',
				'nonce'      => 'bp_xprofile_settings',
				'attributes' => array(
					'name'  => 'xprofile-settings-submit',
					'id'    => 'submit',
					'value' => __( 'Save Changes', 'buddyboss' ),
					'class' => 'auto',
				),
			),
			'member-group-invites'          => array(
				'nonce'      => 'bp_nouveau_group_invites_settings',
				'attributes' => array(
					'name'  => 'member-group-invites-submit',
					'id'    => 'submit',
					'value' => __( 'Save', 'buddyboss' ),
					'class' => 'auto',
				),
			),
			'activity-new-comment'          => array(
				'after'      => 'bp_activity_entry_comments',
				'nonce'      => 'new_activity_comment',
				'nonce_key'  => '_wpnonce_new_activity_comment',
				'wrapper'    => false,
				'attributes' => array(
					'name'                => 'ac_form_submit',
					'value'               => __( 'Post', 'buddyboss' ),
					'data-add-edit-label' => __( 'Save', 'buddyboss' ),
				),
			),
			'member-data-export'            => array(
				'nonce'      => 'buddyboss_data_export_request',
				'attributes' => array(
					'name'  => 'member-data-export-submit',
					'id'    => 'submit',
					'value' => __( 'Request Data Export', 'buddyboss' ),
					'class' => 'auto',
				),
			),
			'member-invites-submit'         => array(
				'nonce'      => 'bp_member_invite_submit',
				'attributes' => array(
					'name'  => 'member-invite-submit',
					'id'    => 'submit',
					'value' => __( 'Send Invites', 'buddyboss' ),
					'class' => 'auto',
				),
			),
		)
	);

	if ( isset( $actions[ $action ] ) ) {
		return $actions[ $action ];
	}

	return false;
}

/**
 * Reorder a BuddyPress item nav according to a given list of nav item slugs
 *
 * @param object $nav The BuddyPress Item Nav object to reorder
 * @param array  $order A list of slugs ordered (eg: array( 'profile', 'activity', etc..) )
 * @param string $parent_slug A parent slug if it's a secondary nav we are reordering (case of the Groups single item)
 *
 * @return bool True on success. False otherwise.
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_set_nav_item_order( $nav = null, $order = array(), $parent_slug = '' ) {
	if ( ! is_object( $nav ) || empty( $order ) || ! is_array( $order ) ) {
		return false;
	}

	$position = 0;

	foreach ( $order as $slug ) {
		$position += 10;

		$key = $slug;
		if ( ! empty( $parent_slug ) ) {
			$key = $parent_slug . '/' . $key;
		}

		$item_nav = $nav->get( $key );

		if ( ! $item_nav ) {
			continue;
		}

		if ( (int) $item_nav->position !== (int) $position ) {
			$nav->edit_nav( array( 'position' => $position ), $slug, $parent_slug );
		}
	}

	return true;
}

/**
 * Return saved profile header buttons by order
 *
 * @since BuddyBoss 1.5.1
 *
 * @return mixed|void
 */

function bp_nouveau_get_user_profile_actions() {
	$bp_nouveau_appearance     = maybe_unserialize( bp_get_option( 'bp_nouveau_appearance' ) );
	$profile_header_btn_orders = isset( $bp_nouveau_appearance['user_profile_actions_order'] )
		? $bp_nouveau_appearance['user_profile_actions_order'] : array();

	/**
	 * Filter the header buttons
	 *
	 * @since BuddyBoss 1.5.1
	 */

	return apply_filters( 'bp_nouveau_get_user_profile_actions', $profile_header_btn_orders );
}
