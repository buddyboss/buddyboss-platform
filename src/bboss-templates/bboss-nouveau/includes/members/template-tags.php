<?php
/**
 * Members template tags
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Template tag to wrap all Legacy actions that was used
 * before the members directory content
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_before_members_directory_content() {
	/**
	 * Fires at the begining of the templates BP injected content.
	 *
	 * @since BuddyPress 2.3.0
	 */
	do_action( 'bp_before_directory_members_page' );

	/**
	 * Fires before the display of the members.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'bp_before_directory_members' );

	/**
	 * Fires before the display of the members content.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'bp_before_directory_members_content' );

	/**
	 * Fires before the display of the members list tabs.
	 *
	 * @since BuddyPress 1.8.0
	 */
	do_action( 'bp_before_directory_members_tabs' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_before_members_directory_content' ) && ! function_exists( 'bp_nouveau_before_members_directory_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_before_members_directory_content()}.
	 *
	 * @deprecated Use bboss_nouveau_before_members_directory_content() instead.
	 */
	function bp_nouveau_before_members_directory_content() {
		return bboss_nouveau_before_members_directory_content();
	}
}
// @bbwt-com-end compat-layer


/**
 * Template tag to wrap all Legacy actions that was used
 * after the members directory content
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_after_members_directory_content() {
	/**
	 * Fires and displays the members content.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'bp_directory_members_content' );

	/**
	 * Fires after the display of the members content.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'bp_after_directory_members_content' );

	/**
	 * Fires after the display of the members.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'bp_after_directory_members' );

	/**
	 * Fires at the bottom of the members directory template file.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_after_directory_members_page' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_after_members_directory_content' ) && ! function_exists( 'bp_nouveau_after_members_directory_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_after_members_directory_content()}.
	 *
	 * @deprecated Use bboss_nouveau_after_members_directory_content() instead.
	 */
	function bp_nouveau_after_members_directory_content() {
		return bboss_nouveau_after_members_directory_content();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire specific hooks into the single members templates
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $when   'before' or 'after'.
 * @param string $suffix Use it to add terms at the end of the hook name.
 */
function bboss_nouveau_member_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a member hook.
	$hook[] = 'member';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bboss_nouveau_hook( $hook );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_member_hook' ) && ! function_exists( 'bp_nouveau_member_hook' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_member_hook()}.
	 *
	 * @deprecated Use bboss_nouveau_member_hook() instead.
	 */
	function bp_nouveau_member_hook( $when = '', $suffix = '' ) {
		return bboss_nouveau_member_hook( $when, $suffix );
	}
}
// @bbwt-com-end compat-layer


/**
 * Template tag to wrap the notification settings hook
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_member_email_notice_settings() {
	/**
	 * Fires at the top of the member template notification settings form.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'bp_notification_settings' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_member_email_notice_settings' ) && ! function_exists( 'bp_nouveau_member_email_notice_settings' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_member_email_notice_settings()}.
	 *
	 * @deprecated Use bboss_nouveau_member_email_notice_settings() instead.
	 */
	function bp_nouveau_member_email_notice_settings() {
		return bboss_nouveau_member_email_notice_settings();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the action buttons for the displayed user profile
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args See bboss_nouveau_wrapper() for the description of parameters.
 */
function bboss_nouveau_member_header_buttons( $args = array() ) {
	$bp_nouveau = bboss_nouveau();

	if ( bboss_is_user() ) {
		$args['type'] = 'profile';
	} else {
		$args['type'] = 'header';// we have no real need for this 'type' on header actions.
	}

	$members_buttons = bboss_nouveau_get_members_buttons( $args );
	$output          = join( ' ', array_slice( $members_buttons, 0, 1 ) );

	/**
	 * On the member's header we need to reset the group button's global
	 * once displayed as the friends component will use the member's loop
	 */
	if ( ! empty( $bp_nouveau->members->member_buttons ) ) {
		unset( $bp_nouveau->members->member_buttons );
	}

	ob_start();
	/**
	 * Fires in the member header actions section.
	 *
	 * @since BuddyPress 1.2.6
	 */
	do_action( 'bp_member_header_actions' );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	if ( ! $args ) {
		$args = array(
			'id'      => 'item-buttons',
			'classes' => false,
		);
	}

	bboss_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_member_header_buttons' ) && ! function_exists( 'bp_nouveau_member_header_buttons' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_member_header_buttons()}.
	 *
	 * @deprecated Use bboss_nouveau_member_header_buttons() instead.
	 */
	function bp_nouveau_member_header_buttons( $args = array() ) {
		return bboss_nouveau_member_header_buttons( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the action buttons for the displayed user profile
 *
 * @since BuddyBoss 1.7.3
 *
 * @param array $args See bboss_nouveau_wrapper() for the description of parameters.
 */
function bboss_nouveau_member_header_bubble_buttons( $args = array() ) {
	$bp_nouveau = bboss_nouveau();

	if ( bboss_is_user() ) {
		$args['type'] = 'profile';
	} else {
		$args['type'] = 'header'; // we have no real need for this 'type' on header actions.
	}

	$members_buttons = bboss_nouveau_get_members_buttons( $args );
	$output          = join( ' ', array_slice( $members_buttons, 1 ) );

	/**
	 * On the member's header we need to reset the group button's global
	 * once displayed as the friends component will use the member's loop
	 */
	if ( ! empty( $bp_nouveau->members->member_buttons ) ) {
		unset( $bp_nouveau->members->member_buttons );
	}

	ob_start();
	/**
	 * Fires in the member header actions section.
	 *
	 * @since BuddyBoss 1.7.3
	 */
	do_action( 'bp_member_header_bubble_actions' );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	if ( ! $args ) {
		$args = array(
			'container_id' => 'item-bubble-buttons',
			'classes'      => false,
		);
	}

	ob_start();
	bboss_get_template_part( 'common/more-options-view' );
	$template_part_content = ob_get_clean();

	$output = sprintf( '<a href="#" class="bb_more_options_action" aria-label="%1$s"><i class="bb-icon-f bb-icon-ellipsis-h"></i></a><div class="bb_more_options_list bb_more_dropdown"> %2$s %3$s</div><div class="bb_more_dropdown_overlay"></div>', esc_attr__( 'More Options', 'buddyboss' ), $template_part_content, $output );

	bboss_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_member_header_bubble_buttons' ) && ! function_exists( 'bp_nouveau_member_header_bubble_buttons' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_member_header_bubble_buttons()}.
	 *
	 * @deprecated Use bboss_nouveau_member_header_bubble_buttons() instead.
	 */
	function bp_nouveau_member_header_bubble_buttons( $args = array() ) {
		return bboss_nouveau_member_header_bubble_buttons( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the action buttons in member loops
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args See bboss_nouveau_wrapper() for the description of parameters.
 */
function bboss_nouveau_members_loop_buttons( $args = array() ) {
	if ( empty( $GLOBALS['members_template'] ) ) {
		return;
	}

	$args['type'] = 'loop';
	$action       = 'bp_directory_members_actions';

	// Specific case for group members.
	if ( bboss_is_active( 'groups' ) && bboss_is_group_members() ) {
		$args['type'] = 'group_member';
		$action       = 'bp_group_members_list_item_action';

	} elseif ( bboss_is_active( 'friends' ) && bboss_is_user_friend_requests() ) {
		$args['type'] = 'friendship_request';
		$action       = 'bp_friend_requests_item_action';
	}

	$output = join( ' ', bboss_nouveau_get_members_buttons( $args ) );

	ob_start();
	/**
	 * Fires inside the members action HTML markup to display actions.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( $action );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	bboss_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_members_loop_buttons' ) && ! function_exists( 'bp_nouveau_members_loop_buttons' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_members_loop_buttons()}.
	 *
	 * @deprecated Use bboss_nouveau_members_loop_buttons() instead.
	 */
	function bp_nouveau_members_loop_buttons( $args = array() ) {
		return bboss_nouveau_members_loop_buttons( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Get the action buttons for the displayed user profile
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args See bboss_nouveau_wrapper() for the description of parameters.
 *
 * @return array
 */
function bboss_nouveau_get_members_buttons( $args ) {
	$buttons                    = array();
	$type                       = ( ! empty( $args['type'] ) ) ? $args['type'] : '';
	$prefix_link_text           = $args['prefix_link_text'] ?? '';
	$postfix_link_text          = $args['postfix_link_text'] ?? '';
	$is_tooltips                = $args['is_tooltips'] ?? false;
	$tooltips                   = $args['data-balloon'] ?? '';
	$tooltips_pos               = $args['data-balloon-pos'] ?? '';
	$hover_type                 = $args['button_attr']['hover_type'] ?? false;
	$hover_data_title           = $args['button_attr']['data-title'] ?? '';
	$hover_data_title_displayed = $args['button_attr']['data-title-displayed'] ?? '';
	$add_pre_post_text          = $args['button_attr']['add_pre_post_text'] ?? true;

	// @todo Not really sure why BP Legacy needed to do this...
	if ( 'profile' === $type && is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return $buttons;
	}

	$user_id = bboss_displayed_user_id();

	if ( 'loop' === $type || 'friendship_request' === $type ) {
		$user_id = bboss_get_member_user_id();
	} elseif ( 'group_member' === $type ) {
		$user_id = bboss_get_group_member_id();
	}

	if ( ! $user_id ) {
		return $buttons;
	}

	/*
	 * If the 'container' is set to 'ul'
	 * set a var $parent_element to li
	 * otherwise simply pass any value found in args
	 * or set var false.
	 */
	$parent_element = false;

	if ( ! empty( $args['container'] ) && 'ul' === $args['container'] ) {
		$parent_element = 'li';
	} elseif ( ! empty( $args['parent_element'] ) ) {
		$parent_element = $args['parent_element'];
	}

	/*
	 * If we have an arg value for $button_element passed through
	 * use it to default all the $buttons['button_element'] values
	 * otherwise default to 'a' (anchor)
	 * Or override & hardcode the 'element' string on $buttons array.
	 *
	 * Icons sets a class for icon display if not using the button element
	 */
	$icons = '';
	if ( ! empty( $args['button_element'] ) ) {
		$button_element = $args['button_element'];
	} else {
		$button_element = 'button';
		$icons          = ' icons';
	}

	// If we pass through parent classes add them to $button array.
	$parent_class = '';
	if ( ! empty( $args['parent_attr']['class'] ) ) {
		$parent_class = $args['parent_attr']['class'];
	}

	$action_button_args = array(
		'prefix_link_text'  => $prefix_link_text,
		'postfix_link_text' => $postfix_link_text,
		'is_tooltips'       => $is_tooltips,
		'data-balloon-pos'  => $tooltips_pos,
		'button_attr'       => array(
			'hover_type'           => $hover_type,
			'data-title'           => $hover_data_title,
			'data-title-displayed' => $hover_data_title_displayed,
			'add_pre_post_text'    => $add_pre_post_text,
		),
	);

	$bp_force_friendship_to_message = bboss_force_friendship_to_message();

	if ( bboss_is_active( 'friends' ) ) {
		// It's the member's connection requests screen.
		if ( 'friendship_request' === $type ) {
			$buttons = array(
				'accept_friendship' => array(
					'id'                => 'accept_friendship',
					'position'          => 5,
					'component'         => 'friends',
					'must_be_logged_in' => true,
					'parent_element'    => $parent_element,
					'link_text'         => esc_html__( 'Accept', 'buddyboss' ),
					'parent_attr'       => array(
						'id'    => '',
						'class' => $parent_class,
					),
					'button_element'    => $button_element,
					'button_attr'       => array(
						'class'                => 'button accept',
						'rel'                  => '',
						'data-balloon'         => $is_tooltips ? $tooltips : '',
						'data-balloon-pos'     => $tooltips_pos,
						'hover_type'           => $hover_type,
						'data-title'           => ! $hover_type ? '' : $hover_data_title,
						'data-title-displayed' => ! $hover_type ? '' : $hover_data_title_displayed,
						'add_pre_post_text'    => $add_pre_post_text,
					),
					'prefix_link_text'  => $prefix_link_text,
					'postfix_link_text' => $postfix_link_text,
					'is_tooltips'       => $is_tooltips,
				),
				'reject_friendship' => array(
					'id'                => 'reject_friendship',
					'position'          => 15,
					'component'         => 'friends',
					'must_be_logged_in' => true,
					'parent_element'    => $parent_element,
					'link_text'         => esc_html__( 'Ignore', 'buddyboss' ),
					'parent_attr'       => array(
						'id'    => '',
						'class' => $parent_class,
					),
					'button_element'    => $button_element,
					'button_attr'       => array(
						'class'                => 'button reject',
						'rel'                  => '',
						'data-balloon'         => $is_tooltips ? $tooltips : '',
						'data-balloon-pos'     => $tooltips_pos,
						'hover_type'           => $hover_type,
						'data-title'           => ! $hover_type ? '' : $hover_data_title,
						'data-title-displayed' => ! $hover_type ? '' : $hover_data_title_displayed,
						'add_pre_post_text'    => $add_pre_post_text,
					),
					'prefix_link_text'  => $prefix_link_text,
					'postfix_link_text' => $postfix_link_text,
					'is_tooltips'       => $is_tooltips,
				),
			);

			// If button element set add nonce link to data attr.
			if ( 'button' === $button_element ) {
				$buttons['accept_friendship']['button_attr']['data-bp-nonce'] = bboss_get_friend_accept_request_link();
				$buttons['reject_friendship']['button_attr']['data-bp-nonce'] = bboss_get_friend_reject_request_link();
			} else {
				$buttons['accept_friendship']['button_attr']['href'] = bboss_get_friend_accept_request_link();
				$buttons['reject_friendship']['button_attr']['href'] = bboss_get_friend_reject_request_link();
			}

			// It's any other members screen.
		} else {
			/*
			 * This filter workaround is waiting for a core adaptation
			 * so that we can directly get the friends button arguments
			 * instead of the button.
			 *
			 * See https://buddypress.trac.wordpress.org/ticket/7126
			 */
			add_filter( 'bp_get_add_friend_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

			bboss_get_add_friend_button( $user_id, false, $action_button_args );

			remove_filter( 'bp_get_add_friend_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

			if ( ! empty( bboss_nouveau()->members->button_args ) ) {
				$button_args = bboss_nouveau()->members->button_args;

				$buttons['member_friendship'] = array(
					'id'                  => 'member_friendship',
					'position'            => 5,
					'component'           => $button_args['component'],
					'key'                 => $button_args['id'],
					'must_be_logged_in'   => $button_args['must_be_logged_in'],
					'block_self'          => $button_args['block_self'],
					'potential_friend_id' => $button_args['potential_friend_id'],
					'parent_element'      => $parent_element,
					'link_text'           => $button_args['link_text'],
					'parent_attr'         => array(
						'id'    => $button_args['wrapper_id'],
						'class' => $parent_class . ' ' . $button_args['wrapper_class'],
					),
					'button_element'      => $button_element,
					'button_attr'         => array(
						'id'    => $button_args['link_id'],
						'class' => $button_args['link_class'],
						'rel'   => $button_args['link_rel'],
						'title' => '',
					),
					'prefix_link_text'    => $button_args['prefix_link_text'] ?? '',
					'postfix_link_text'   => $button_args['postfix_link_text'] ?? '',
					'is_tooltips'         => $button_args['is_tooltips'] ?? false,
				);

				if ( ! empty( $button_args['button_attr'] ) ) {
					foreach ( $button_args['button_attr'] as $title => $value ) {
						$buttons['member_friendship']['button_attr'][ $title ] = $value;
					}
				}

				// If button element set add nonce link to data attr.
				if ( 'button' === $button_element && 'awaiting_response' !== $button_args['id'] ) {
					$buttons['member_friendship']['button_attr']['data-bp-nonce'] = $button_args['link_href'];
				} else {
					$buttons['member_friendship']['button_element']      = 'a';
					$buttons['member_friendship']['button_attr']['href'] = $button_args['link_href'];
				}

				unset( bboss_nouveau()->members->button_args );
			}
		}
	}

	if ( bboss_is_active( 'activity' ) && bboss_is_activity_follow_active() ) { // add follow button.

		/*
		 * This filter workaround is waiting for a core adaptation
		 * so that we can directly get the follow button arguments
		 * instead of the button.
		 *
		 * See https://buddypress.trac.wordpress.org/ticket/7126
		 */
		add_filter( 'bp_get_add_follow_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

		bboss_get_add_follow_button( $user_id, bboss_loggedin_user_id(), $action_button_args );

		remove_filter( 'bp_get_add_follow_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

		if ( ! empty( bboss_nouveau()->members->button_args ) ) {
			$button_args = bboss_nouveau()->members->button_args;

			$buttons['member_follow'] = array(
				'id'                => 'member_follow',
				'position'          => 10,
				'component'         => $button_args['component'],
				'must_be_logged_in' => $button_args['must_be_logged_in'],
				'block_self'        => $button_args['block_self'],
				'parent_element'    => $parent_element,
				'link_text'         => $button_args['link_text'],
				'parent_attr'       => array(
					'id'    => $button_args['wrapper_id'],
					'class' => $parent_class . ' ' . $button_args['wrapper_class'],
				),
				'button_element'    => $button_element,
				'button_attr'       => array(
					'id'    => $button_args['link_id'],
					'class' => $button_args['link_class'],
					'rel'   => $button_args['link_rel'],
					'title' => '',
				),
				'prefix_link_text'  => $button_args['prefix_link_text'] ?? '',
				'postfix_link_text' => $button_args['postfix_link_text'] ?? '',
				'is_tooltips'       => $button_args['is_tooltips'] ?? false,
			);

			if ( ! empty( $button_args['button_attr'] ) ) {
				foreach ( $button_args['button_attr'] as $title => $value ) {
					$buttons['member_follow']['button_attr'][ $title ] = $value;
				}
			}

			// If button element set add nonce link to data attr.
			if ( 'button' === $button_element ) {
				$buttons['member_follow']['button_attr']['data-bp-nonce'] = $button_args['link_href'];
			} else {
				$buttons['member_follow']['button_element']      = 'a';
				$buttons['member_follow']['button_attr']['href'] = $button_args['link_href'];
			}

			unset( bboss_nouveau()->members->button_args );
		}
	}

	// Only add The public and private messages when not in a loop.
	if ( bboss_is_active( 'activity' ) && bboss_activity_do_mentions() ) {
		/*
		 * This filter workaround is waiting for a core adaptation
		 * so that we can directly get the public message button arguments
		 * instead of the button.
		 *
		 * See https://buddypress.trac.wordpress.org/ticket/7126
		 */

		if ( ! empty( bboss_nouveau()->members->button_args ) ) {
			$button_args = bboss_nouveau()->members->button_args;

			/*
			 * This button should remain as an anchor link.
			 * Hardcode the use of anchor elements if button arg passed in for other elements.
			 */
			$buttons['public_message'] = array(
				'id'                => $button_args['id'],
				'position'          => 15,
				'component'         => $button_args['component'],
				'must_be_logged_in' => $button_args['must_be_logged_in'],
				'block_self'        => $button_args['block_self'],
				'parent_element'    => $parent_element,
				'button_element'    => 'a',
				'link_text'         => $button_args['link_text'],
				'parent_attr'       => array(
					'id'    => $button_args['wrapper_id'],
					'class' => $parent_class,
				),
				'button_attr'       => array(
					'href'                 => $button_args['link_href'],
					'id'                   => '',
					'class'                => $button_args['link_class'],
					'data-balloon'         => $is_tooltips ? $tooltips : '',
					'data-balloon-pos'     => $tooltips_pos,
					'hover_type'           => $hover_type,
					'data-title'           => ! $hover_type ? '' : $hover_data_title,
					'data-title-displayed' => ! $hover_type ? '' : $hover_data_title_displayed,
					'add_pre_post_text'    => $add_pre_post_text,
				),
				'prefix_link_text'  => $prefix_link_text,
				'postfix_link_text' => $postfix_link_text,
				'is_tooltips'       => $is_tooltips,
			);
			unset( bboss_nouveau()->members->button_args );
		}
	}

	if (
		bboss_is_active( 'messages' ) &&
		bboss_messages_user_can_send_message(
			array(
				'sender_id'     => bboss_loggedin_user_id(),
				'recipients_id' => $user_id,
			)
		)
	) {

		$message_button_args = array();
		if ( ! empty( $prefix_link_text ) || ! empty( $postfix_link_text ) ) {
			$message_button_args = array(
				'prefix_link_text'  => $prefix_link_text,
				'postfix_link_text' => $postfix_link_text,
			);
		}
		/**
		 * This filter workaround is waiting for a core adaptation
		 * so that we can directly get the private messages button arguments
		 * instead of the button.
			 *
		 * @see https://buddypress.trac.wordpress.org/ticket/7126
		 */
		add_filter( 'bp_get_send_message_button_args', 'bp_nouveau_members_catch_button_args', 100, 1 );

		bboss_get_send_message_button( $message_button_args );

		remove_filter( 'bp_get_send_message_button_args', 'bp_nouveau_members_catch_button_args', 100, 1 );

		if ( ! empty( bboss_nouveau()->members->button_args ) ) {
			$button_args = bboss_nouveau()->members->button_args;

			/*
			 * This button should remain as an anchor link.
			 * Hardcode the use of anchor elements if button arg passed in for other elements.
			 */
			$buttons['private_message'] = array(
				'id'                       => $button_args['id'],
				'position'                 => 25,
				'component'                => $button_args['component'],
				'must_be_logged_in'        => $button_args['must_be_logged_in'],
				'message_receiver_user_id' => $button_args['message_receiver_user_id'],
				'block_self'               => $button_args['block_self'],
				'parent_element'           => $parent_element,
				'button_element'           => 'a',
				'link_text'                => $button_args['link_text'],
				'parent_attr'              => array(
					'id'    => $button_args['wrapper_id'],
					'class' => $parent_class,
				),
				'button_attr'              => array(
					'href'                 => wp_nonce_url( trailingslashit( bboss_loggedin_user_domain() . bboss_get_messages_slug() ) . 'compose/?r=' . bboss_members_get_user_nicename( $user_id ) ),
					'id'                   => false,
					'class'                => $button_args['link_class'],
					'rel'                  => '',
					'title'                => '',
					'data-balloon'         => $is_tooltips ? $tooltips : '',
					'data-balloon-pos'     => $tooltips_pos,
					'hover_type'           => $hover_type,
					'data-title'           => ! $hover_type ? '' : $hover_data_title,
					'data-title-displayed' => ! $hover_type ? '' : $hover_data_title_displayed,
					'add_pre_post_text'    => $add_pre_post_text,
				),
				'prefix_link_text'         => $prefix_link_text,
				'postfix_link_text'        => $postfix_link_text,
				'is_tooltips'              => $is_tooltips,
			);

			unset( bboss_nouveau()->members->button_args );
		}
	}

	/*
	* This filter workaround is waiting for a core adaptation
	* so that we can directly get the follow button arguments
	* instead of the button.
	*
	* See https://buddypress.trac.wordpress.org/ticket/7126
	*/
	add_filter( 'bp_get_add_switch_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

	bboss_get_add_switch_button( $user_id );

	remove_filter( 'bp_get_add_switch_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

	if ( ! empty( bboss_nouveau()->members->button_args ) ) {
		$button_args = bboss_nouveau()->members->button_args;

		$buttons['member_switch'] = array(
			'id'                => 'member_switch',
			'position'          => 30,
			'component'         => $button_args['component'],
			'must_be_logged_in' => $button_args['must_be_logged_in'],
			'block_self'        => $button_args['block_self'],
			'parent_element'    => $parent_element,
			'link_href'         => $button_args['link_href'],
			'link_text'         => $button_args['link_text'],
			'parent_attr'       => array(
				'id'    => $button_args['wrapper_id'],
				'class' => $parent_class . ' ' . $button_args['wrapper_class'],
			),
			'button_element'    => 'a',
			'button_attr'       => array(
				'id'    => $button_args['link_id'],
				'class' => $button_args['link_class'],
				'rel'   => $button_args['link_rel'],
				'title' => '',
			),
		);

		if ( ! empty( $button_args['button_attr'] ) ) {
			foreach ( $button_args['button_attr'] as $title => $value ) {
				$buttons['member_switch']['button_attr'][ $title ] = $value;
			}
		}

		// If button element set add nonce link to data attr.
		if ( 'button' === $button_element ) {
			$buttons['member_switch']['button_attr']['data-bp-nonce'] = $button_args['link_href'];
		} else {
			$buttons['member_switch']['button_element']      = 'a';
			$buttons['member_switch']['button_attr']['href'] = $button_args['link_href'];
		}

		unset( bboss_nouveau()->members->button_args );
	}

	if ( is_user_logged_in() && bboss_is_active( 'moderation' ) ) {
		if ( bboss_is_moderation_member_blocking_enable() ) {
			$buttons['member_block'] = bboss_member_get_report_link(
				array(
					'parent_element' => $parent_element,
					'parent_attr'    => array(
						'id'    => 'user-block-' . bboss_displayed_user_id(),
						'class' => $parent_class,
					),
					'button_element' => $button_element,
					'position'       => 29,
				)
			);
		}
		if ( bboss_is_moderation_member_reporting_enable() ) {
			$buttons['member_report'] = bboss_member_get_report_link(
				array(
					'parent_element' => $parent_element,
					'parent_attr'    => array(
						'id'    => 'user-report-' . bboss_displayed_user_id(),
						'class' => $parent_class,
					),
					'button_element' => $button_element,
					'position'       => 25,
					'report_user'    => true,
				)
			);
		}
	}

	/**
	 * Filter to add your buttons, use the position argument to choose where to insert it.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array  $buttons The list of buttons.
	 * @param int    $user_id The displayed user ID.
	 * @param string $type    Whether we're displaying a members loop or a user's page
	 */
	$buttons_group = apply_filters( 'bp_nouveau_get_members_buttons', $buttons, $user_id, $type );
	if ( ! $buttons_group ) {
		return $buttons;
	}

	// It's the first entry of the loop, so build the Group and sort it.
	if ( ! isset( bboss_nouveau()->members->member_buttons ) || ! is_a( bboss_nouveau()->members->member_buttons, 'BBoss_Buttons_Group' ) ) {
		$sort                                 = true;
		bboss_nouveau()->members->member_buttons = new BBoss_Buttons_Group( $buttons_group );

		// It's not the first entry, the order is set, we simply need to update the Buttons Group.
	} else {
		$sort = false;
		bboss_nouveau()->members->member_buttons->update( $buttons_group );
	}

	$return = bboss_nouveau()->members->member_buttons->get( $sort );

	if ( ! $return ) {
		return array();
	}

	/**
	 * Leave a chance to adjust the $return
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array  $return  The list of buttons ordered.
	 * @param int    $user_id The displayed user ID.
	 * @param string $type    Whether we're displaying a members loop or a user's page
	 */
	do_action_ref_array( 'bp_nouveau_return_members_buttons', array( &$return, $user_id, $type ) );

	$order = bboss_nouveau_get_user_profile_actions();

	uksort(
		$return,
		function ( $key1, $key2 ) use ( $order ) {
			return ( array_search( $key1, $order ) <=> array_search( $key2, $order ) );
		}
	);

	return $return;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_get_members_buttons' ) && ! function_exists( 'bp_nouveau_get_members_buttons' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_get_members_buttons()}.
	 *
	 * @deprecated Use bboss_nouveau_get_members_buttons() instead.
	 */
	function bp_nouveau_get_members_buttons( $args ) {
		return bboss_nouveau_get_members_buttons( $args );
	}
}
// @bbwt-com-end compat-layer


/**
 * Does the member has meta.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if the member has meta. False otherwise.
 */
function bboss_nouveau_member_has_meta() {
	return (bool) bboss_nouveau_get_member_meta();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_member_has_meta' ) && ! function_exists( 'bp_nouveau_member_has_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_member_has_meta()}.
	 *
	 * @deprecated Use bboss_nouveau_member_has_meta() instead.
	 */
	function bp_nouveau_member_has_meta() {
		return bboss_nouveau_member_has_meta();
	}
}
// @bbwt-com-end compat-layer


/**
 * Display the member meta.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string HTML Output.
 */
function bboss_nouveau_member_meta() {
	echo wp_kses_post( join( "
", bboss_nouveau_get_member_meta() ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_member_meta' ) && ! function_exists( 'bp_nouveau_member_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_member_meta()}.
	 *
	 * @deprecated Use bboss_nouveau_member_meta() instead.
	 */
	function bp_nouveau_member_meta() {
		return bboss_nouveau_member_meta();
	}
}
// @bbwt-com-end compat-layer


/**
 * Get the member meta.
 *
 * @since BuddyPress 3.0.0
 *
 * @return array The member meta.
 */
function bboss_nouveau_get_member_meta() {
	$meta    = array();
	$is_loop = false;

	if ( ! empty( $GLOBALS['members_template']->member ) ) {
		$member  = $GLOBALS['members_template']->member;
		$is_loop = true;
	} else {
		$member = bboss_get_displayed_user();
	}

	if ( empty( $member->id ) ) {
		return $meta;
	}

	if ( empty( $member->template_meta ) ) {
		// It's a single user's header.
		if ( ! $is_loop ) {
			$register_date = date_i18n( 'F Y', strtotime( get_userdata( bboss_displayed_user_id() )->user_registered ) );

			$meta['last_activity'] = sprintf(
				/* translators: %s: Member joined date. */
				'<span class="activity">' . esc_html__( 'Joined %s', 'buddyboss' ) . '</span>',
				esc_html( $register_date )
			);

			// We're in the members loop.
		} else {
			$meta = array(
				'last_activity' => sprintf( '%s', bboss_get_member_last_active() ),
			);
		}

		// Make sure to include hooked meta.
		$extra_meta = bboss_nouveau_get_hooked_member_meta();

		if ( $extra_meta ) {
			$meta['extra'] = $extra_meta;
		}

		/**
		 * Filter to add/remove Member meta.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array  $meta    The list of meta to output.
		 * @param object $member  The member object
		 * @param bool   $is_loop True if in the members loop. False otherwise.
		 */
		$member->template_meta = apply_filters( 'bp_nouveau_get_member_meta', $meta, $member, $is_loop );
	}

	return $member->template_meta;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_get_member_meta' ) && ! function_exists( 'bp_nouveau_get_member_meta' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_get_member_meta()}.
	 *
	 * @deprecated Use bboss_nouveau_get_member_meta() instead.
	 */
	function bp_nouveau_get_member_meta() {
		return bboss_nouveau_get_member_meta();
	}
}
// @bbwt-com-end compat-layer


/**
 * Load the appropriate content for the single member pages
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_member_template_part() {
	/**
	 * Fires before the display of member body content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_member_body' );

	if ( bboss_is_user_front() ) {
		bboss_displayed_user_front_template_part();

	} else {
		$template = 'plugins';

		if ( bboss_is_user_activity() ) {
			$template = 'activity';
		} elseif ( bboss_is_user_blogs() ) {
			$template = 'blogs';
		} elseif ( bboss_is_user_friends() ) {
			$template = 'friends';
		} elseif ( bboss_is_user_groups() ) {
			$template = 'groups';
		} elseif ( bboss_is_user_messages() ) {
			$template = 'messages';
		} elseif ( bboss_is_user_profile() ) {
			$template = 'profile';
		} elseif ( bboss_is_user_notifications() ) {
			$template = 'notifications';
		} elseif ( bboss_is_user_settings() ) {
			$template = 'settings';
		} elseif ( bboss_is_user_invites() ) {
			$template = 'invites';
		} elseif ( bboss_is_user_media() ) {
			$template = 'media';
		} elseif ( bboss_is_active( 'document' ) && bboss_is_user_document() ) {
			$template = 'document';
		} elseif ( bboss_is_active( 'video' ) && bboss_is_user_video() ) {
			$template = 'video';
		}

		bboss_nouveau_member_get_template_part( $template );
	}

	/**
	 * Fires after the display of member body content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_member_body' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_member_template_part' ) && ! function_exists( 'bp_nouveau_member_template_part' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_member_template_part()}.
	 *
	 * @deprecated Use bboss_nouveau_member_template_part() instead.
	 */
	function bp_nouveau_member_template_part() {
		return bboss_nouveau_member_template_part();
	}
}
// @bbwt-com-end compat-layer


/**
 * Use the appropriate Member header and enjoy a template hierarchy
 *
 * @since BuddyPress 3.0.0
 *
 * @return string HTML Output
 */
function bboss_nouveau_member_header_template_part() {
	$template = 'member-header';

	if ( bboss_displayed_user_use_cover_image_header() ) {
		$template = 'cover-image-header';
	}

	/**
	 * Fires before the display of a member's header.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_member_header' );

	// Get the template part for the header.
	bboss_nouveau_member_get_template_part( $template );

	/**
	 * Fires after the display of a member's header.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_member_header' );

	bboss_nouveau_template_notices();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_member_header_template_part' ) && ! function_exists( 'bp_nouveau_member_header_template_part' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_member_header_template_part()}.
	 *
	 * @deprecated Use bboss_nouveau_member_header_template_part() instead.
	 */
	function bp_nouveau_member_header_template_part() {
		return bboss_nouveau_member_header_template_part();
	}
}
// @bbwt-com-end compat-layer



/** WP Profile tags **********************************************************/

/**
 * Template tag to wrap all Legacy actions that was used
 * before and after the WP User's Profile.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_wp_profile_hooks( $type = 'before' ) {
	if ( 'before' === $type ) {
		/**
		 * Fires before the display of member profile loop content.
		 *
		 * @since BuddyPress 1.2.0
		 */
		do_action( 'bp_before_profile_loop_content' );

		/**
		 * Fires before the display of member profile field content.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_before_profile_field_content' );
	} else {
		/**
		 * Fires after the display of member profile field content.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_after_profile_field_content' );

		/**
		 * Fires and displays the profile field buttons.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_profile_field_buttons' );

		/**
		 * Fires after the display of member profile loop content.
		 *
		 * @since BuddyPress 1.2.0
		 */
		do_action( 'bp_after_profile_loop_content' );
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_wp_profile_hooks' ) && ! function_exists( 'bp_nouveau_wp_profile_hooks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_wp_profile_hooks()}.
	 *
	 * @deprecated Use bboss_nouveau_wp_profile_hooks() instead.
	 */
	function bp_nouveau_wp_profile_hooks( $type = 'before' ) {
		return bboss_nouveau_wp_profile_hooks( $type );
	}
}
// @bbwt-com-end compat-layer


/**
 * Does the displayed user has WP profile fields?
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if user has profile fields. False otherwise.
 */
function bboss_nouveau_has_wp_profile_fields() {
	$user_id = bboss_displayed_user_id();
	if ( ! $user_id ) {
		return false;
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	$fields              = bboss_nouveau_get_wp_profile_fields( $user );
	$user_profile_fields = array();

	foreach ( $fields as $key => $field ) {
		if ( empty( $user->{$key} ) ) {
			continue;
		}

		$user_profile_fields[] = (object) array(
			'id'    => 'wp_' . $key,
			'label' => $field,
			'data'  => $user->{$key},
		);
	}

	if ( ! $user_profile_fields ) {
		return false;
	}

	// Keep it for a later use.
	$bp_nouveau                            = bboss_nouveau();
	$bp_nouveau->members->wp_profile       = $user_profile_fields;
	$bp_nouveau->members->wp_profile_index = 0;

	return true;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_has_wp_profile_fields' ) && ! function_exists( 'bp_nouveau_has_wp_profile_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_has_wp_profile_fields()}.
	 *
	 * @deprecated Use bboss_nouveau_has_wp_profile_fields() instead.
	 */
	function bp_nouveau_has_wp_profile_fields() {
		return bboss_nouveau_has_wp_profile_fields();
	}
}
// @bbwt-com-end compat-layer


/**
 * Check if there are still profile fields to output.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if the profile field exists. False otherwise.
 */
function bboss_nouveau_wp_profile_fields() {
	$bp_nouveau = bboss_nouveau();

	if ( isset( $bp_nouveau->members->wp_profile[ $bp_nouveau->members->wp_profile_index ] ) ) {
		return true;
	}

	$bp_nouveau->members->wp_profile_index = 0;
	unset( $bp_nouveau->members->wp_profile_current );

	return false;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_wp_profile_fields' ) && ! function_exists( 'bp_nouveau_wp_profile_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_wp_profile_fields()}.
	 *
	 * @deprecated Use bboss_nouveau_wp_profile_fields() instead.
	 */
	function bp_nouveau_wp_profile_fields() {
		return bboss_nouveau_wp_profile_fields();
	}
}
// @bbwt-com-end compat-layer


/**
 * Set the current profile field and iterate into the loop.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_wp_profile_field() {
	$bp_nouveau = bboss_nouveau();

	$bp_nouveau->members->wp_profile_current = $bp_nouveau->members->wp_profile[ $bp_nouveau->members->wp_profile_index ];
	$bp_nouveau->members->wp_profile_index  += 1;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_wp_profile_field' ) && ! function_exists( 'bp_nouveau_wp_profile_field' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_wp_profile_field()}.
	 *
	 * @deprecated Use bboss_nouveau_wp_profile_field() instead.
	 */
	function bp_nouveau_wp_profile_field() {
		return bboss_nouveau_wp_profile_field();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the WP profile field ID.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_wp_profile_field_id() {
	echo esc_attr( bboss_nouveau_get_wp_profile_field_id() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_wp_profile_field_id' ) && ! function_exists( 'bp_nouveau_wp_profile_field_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_wp_profile_field_id()}.
	 *
	 * @deprecated Use bboss_nouveau_wp_profile_field_id() instead.
	 */
	function bp_nouveau_wp_profile_field_id() {
		return bboss_nouveau_wp_profile_field_id();
	}
}
// @bbwt-com-end compat-layer

	/**
	 * Get the WP profile field ID.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return int the profile field ID.
	 */
function bboss_nouveau_get_wp_profile_field_id() {
	$field = bboss_nouveau()->members->wp_profile_current;

	/**
	 * Filters the WP profile field ID used for BuddyPress Nouveau.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $id Field ID.
	 */
	return apply_filters( 'bp_nouveau_get_wp_profile_field_id', $field->id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_get_wp_profile_field_id' ) && ! function_exists( 'bp_nouveau_get_wp_profile_field_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_get_wp_profile_field_id()}.
	 *
	 * @deprecated Use bboss_nouveau_get_wp_profile_field_id() instead.
	 */
	function bp_nouveau_get_wp_profile_field_id() {
		return bboss_nouveau_get_wp_profile_field_id();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the WP profile field label.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_wp_profile_field_label() {
	echo esc_html( bboss_nouveau_get_wp_profile_field_label() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_wp_profile_field_label' ) && ! function_exists( 'bp_nouveau_wp_profile_field_label' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_wp_profile_field_label()}.
	 *
	 * @deprecated Use bboss_nouveau_wp_profile_field_label() instead.
	 */
	function bp_nouveau_wp_profile_field_label() {
		return bboss_nouveau_wp_profile_field_label();
	}
}
// @bbwt-com-end compat-layer


	/**
	 * Get the WP profile label.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string the profile field label.
	 */
function bboss_nouveau_get_wp_profile_field_label() {
	$field = bboss_nouveau()->members->wp_profile_current;

	/**
	 * Filters the WP profile field label used for BuddyPress Nouveau.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $label Field label.
	 */
	return apply_filters( 'bp_nouveau_get_wp_profile_field_label', $field->label );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_get_wp_profile_field_label' ) && ! function_exists( 'bp_nouveau_get_wp_profile_field_label' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_get_wp_profile_field_label()}.
	 *
	 * @deprecated Use bboss_nouveau_get_wp_profile_field_label() instead.
	 */
	function bp_nouveau_get_wp_profile_field_label() {
		return bboss_nouveau_get_wp_profile_field_label();
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the WP profile field data.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_wp_profile_field_data() {
	$data = bboss_nouveau_get_wp_profile_field_data();
	$data = make_clickable( $data );

	echo wp_kses(
		/**
		 * Filters a WP profile field value.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $data The profile field data value.
		 */
		apply_filters( 'bp_nouveau_get_wp_profile_field_data', $data ),
		array(
			'a' => array(
				'href' => true,
				'rel'  => true,
			),
		)
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_wp_profile_field_data' ) && ! function_exists( 'bp_nouveau_wp_profile_field_data' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_wp_profile_field_data()}.
	 *
	 * @deprecated Use bboss_nouveau_wp_profile_field_data() instead.
	 */
	function bp_nouveau_wp_profile_field_data() {
		return bboss_nouveau_wp_profile_field_data();
	}
}
// @bbwt-com-end compat-layer


/**
 * Get the WP profile field data.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string the profile field data.
 */
function bboss_nouveau_get_wp_profile_field_data() {
	$field = bboss_nouveau()->members->wp_profile_current;
	return $field->data;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_get_wp_profile_field_data' ) && ! function_exists( 'bp_nouveau_get_wp_profile_field_data' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_get_wp_profile_field_data()}.
	 *
	 * @deprecated Use bboss_nouveau_get_wp_profile_field_data() instead.
	 */
	function bp_nouveau_get_wp_profile_field_data() {
		return bboss_nouveau_get_wp_profile_field_data();
	}
}
// @bbwt-com-end compat-layer


/**
 * Get the user registered date meta.
 *
 * @since BuddyPress 1.9.1
 *
 * @param int $user_id User ID.
 *
 * @return string The user registered date.
 */
function bboss_get_member_joined_date( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bboss_displayed_user_id();
	}

	$register_date        = date_i18n( 'M Y', strtotime( get_userdata( $user_id )->user_registered ) );
	$user_registered_date = sprintf(
		/* translators: 1: User registered date. */
		esc_html__( 'Joined %s', 'buddyboss' ),
		esc_html( $register_date )
	);

	/**
	 * Filters the user registered date meta.
	 *
	 * @since BuddyPress 1.9.1
	 *
	 * @param string The user registered date meta.
	 * @param string The user registered date.
	 */
	return apply_filters( 'bb_get_member_joined_date', $user_registered_date, $register_date );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_get_member_joined_date' ) && ! function_exists( 'bb_get_member_joined_date' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_member_joined_date()}.
	 *
	 * @deprecated Use bboss_get_member_joined_date() instead.
	 */
	function bb_get_member_joined_date( $user_id = 0 ) {
		return bboss_get_member_joined_date( $user_id );
	}
}
// @bbwt-com-end compat-layer

