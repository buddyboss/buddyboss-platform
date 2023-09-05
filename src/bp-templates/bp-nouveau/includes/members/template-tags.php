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
function bp_nouveau_before_members_directory_content() {
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

/**
 * Template tag to wrap all Legacy actions that was used
 * after the members directory content
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_after_members_directory_content() {
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

/**
 * Fire specific hooks into the single members templates
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $when   'before' or 'after'.
 * @param string $suffix Use it to add terms at the end of the hook name.
 */
function bp_nouveau_member_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a member hook.
	$hook[] = 'member';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Template tag to wrap the notification settings hook
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_member_email_notice_settings() {
	/**
	 * Fires at the top of the member template notification settings form.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'bp_notification_settings' );
}

/**
 * Output the action buttons for the displayed user profile
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_member_header_buttons( $args = array() ) {
	$bp_nouveau = bp_nouveau();

	if ( bp_is_user() ) {
		$args['type'] = 'profile';
	} else {
		$args['type'] = 'header';// we have no real need for this 'type' on header actions.
	}

	$members_buttons = bp_nouveau_get_members_buttons( $args );
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

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Output the action buttons for the displayed user profile
 *
 * @since BuddyBoss 1.7.3
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_member_header_bubble_buttons( $args = array() ) {
	$bp_nouveau = bp_nouveau();

	if ( bp_is_user() ) {
		$args['type'] = 'profile';
	} else {
		$args['type'] = 'header'; // we have no real need for this 'type' on header actions.
	}

	$members_buttons = bp_nouveau_get_members_buttons( $args );
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

	$output = sprintf( '<a href="#" class="bb_more_options_action"><i class="bb-icon-f bb-icon-ellipsis-h"></i></a><div class="bb_more_options_list">%s</div>', $output );

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Output the action buttons in member loops
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_members_loop_buttons( $args = array() ) {
	if ( empty( $GLOBALS['members_template'] ) ) {
		return;
	}

	$args['type'] = 'loop';
	$action       = 'bp_directory_members_actions';

	// Specific case for group members.
	if ( bp_is_active( 'groups' ) && bp_is_group_members() ) {
		$args['type'] = 'group_member';
		$action       = 'bp_group_members_list_item_action';

	} elseif ( bp_is_active( 'friends' ) && bp_is_user_friend_requests() ) {
		$args['type'] = 'friendship_request';
		$action       = 'bp_friend_requests_item_action';
	}

	$output = join( ' ', bp_nouveau_get_members_buttons( $args ) );

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

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Get the action buttons for the displayed user profile
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 *
 * @return array
 */
function bp_nouveau_get_members_buttons( $args ) {
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

	$user_id = bp_displayed_user_id();

	if ( 'loop' === $type || 'friendship_request' === $type ) {
		$user_id = bp_get_member_user_id();
	} elseif ( 'group_member' === $type ) {
		$user_id = bp_get_group_member_id();
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

	$bp_force_friendship_to_message = bp_force_friendship_to_message();

	if ( bp_is_active( 'friends' ) ) {
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
				$buttons['accept_friendship']['button_attr']['data-bp-nonce'] = bp_get_friend_accept_request_link();
				$buttons['reject_friendship']['button_attr']['data-bp-nonce'] = bp_get_friend_reject_request_link();
			} else {
				$buttons['accept_friendship']['button_attr']['href'] = bp_get_friend_accept_request_link();
				$buttons['reject_friendship']['button_attr']['href'] = bp_get_friend_reject_request_link();
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

			bp_get_add_friend_button( $user_id, false, $action_button_args );

			remove_filter( 'bp_get_add_friend_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

			if ( ! empty( bp_nouveau()->members->button_args ) ) {
				$button_args = bp_nouveau()->members->button_args;

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

				unset( bp_nouveau()->members->button_args );
			}
		}
	}

	if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) { // add follow button.

		/*
		 * This filter workaround is waiting for a core adaptation
		 * so that we can directly get the follow button arguments
		 * instead of the button.
		 *
		 * See https://buddypress.trac.wordpress.org/ticket/7126
		 */
		add_filter( 'bp_get_add_follow_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

		bp_get_add_follow_button( $user_id, bp_loggedin_user_id(), $action_button_args );

		remove_filter( 'bp_get_add_follow_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

		if ( ! empty( bp_nouveau()->members->button_args ) ) {
			$button_args = bp_nouveau()->members->button_args;

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

			unset( bp_nouveau()->members->button_args );
		}
	}

	// Only add The public and private messages when not in a loop.
	if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) {
		/*
		 * This filter workaround is waiting for a core adaptation
		 * so that we can directly get the public message button arguments
		 * instead of the button.
		 *
		 * See https://buddypress.trac.wordpress.org/ticket/7126
		 */

		if ( ! empty( bp_nouveau()->members->button_args ) ) {
			$button_args = bp_nouveau()->members->button_args;

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
			unset( bp_nouveau()->members->button_args );
		}
	}

	if (
		bp_is_active( 'messages' ) &&
		bb_messages_user_can_send_message(
			array(
				'sender_id'     => bp_loggedin_user_id(),
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

		bp_get_send_message_button( $message_button_args );

		remove_filter( 'bp_get_send_message_button_args', 'bp_nouveau_members_catch_button_args', 100, 1 );

		if ( ! empty( bp_nouveau()->members->button_args ) ) {
			$button_args = bp_nouveau()->members->button_args;

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
					'href'                 => wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() ) . 'compose/?r=' . bp_members_get_user_nicename( $user_id ) ),
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

			unset( bp_nouveau()->members->button_args );
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

	bp_get_add_switch_button( $user_id );

	remove_filter( 'bp_get_add_switch_button', 'bp_nouveau_members_catch_button_args', 100, 1 );

	if ( ! empty( bp_nouveau()->members->button_args ) ) {
		$button_args = bp_nouveau()->members->button_args;

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

		unset( bp_nouveau()->members->button_args );
	}

	if ( is_user_logged_in() && bp_is_active( 'moderation' ) ) {
		if ( bp_is_moderation_member_blocking_enable() ) {
			$buttons['member_block'] = bp_member_get_report_link(
				array(
					'parent_element' => $parent_element,
					'parent_attr'    => array(
						'id'    => 'user-block-' . bp_displayed_user_id(),
						'class' => $parent_class,
					),
					'button_element' => $button_element,
					'position'       => 29,
				)
			);
		}
		if ( bb_is_moderation_member_reporting_enable() ) {
			$buttons['member_report'] = bp_member_get_report_link(
				array(
					'parent_element' => $parent_element,
					'parent_attr'    => array(
						'id'    => 'user-report-' . bp_displayed_user_id(),
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
	if ( ! isset( bp_nouveau()->members->member_buttons ) || ! is_a( bp_nouveau()->members->member_buttons, 'BP_Buttons_Group' ) ) {
		$sort                                 = true;
		bp_nouveau()->members->member_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first entry, the order is set, we simply need to update the Buttons Group.
	} else {
		$sort = false;
		bp_nouveau()->members->member_buttons->update( $buttons_group );
	}

	$return = bp_nouveau()->members->member_buttons->get( $sort );

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

	$order = bp_nouveau_get_user_profile_actions();

	uksort(
		$return,
		function ( $key1, $key2 ) use ( $order ) {
			return ( array_search( $key1, $order ) <=> array_search( $key2, $order ) );
		}
	);

	return $return;
}

/**
 * Does the member has meta.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if the member has meta. False otherwise.
 */
function bp_nouveau_member_has_meta() {
	return (bool) bp_nouveau_get_member_meta();
}

/**
 * Display the member meta.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string HTML Output.
 */
function bp_nouveau_member_meta() {
	echo join( "\n", bp_nouveau_get_member_meta() );
}

/**
 * Get the member meta.
 *
 * @since BuddyPress 3.0.0
 *
 * @return array The member meta.
 */
function bp_nouveau_get_member_meta() {
	$meta    = array();
	$is_loop = false;

	if ( ! empty( $GLOBALS['members_template']->member ) ) {
		$member  = $GLOBALS['members_template']->member;
		$is_loop = true;
	} else {
		$member = bp_get_displayed_user();
	}

	if ( empty( $member->id ) ) {
		return $meta;
	}

	if ( empty( $member->template_meta ) ) {
		// It's a single user's header.
		if ( ! $is_loop ) {
			$register_date = date_i18n( 'F Y', strtotime( get_userdata( bp_displayed_user_id() )->user_registered ) );

			$meta['last_activity'] = sprintf(
				/* translators: %s: Member joined date. */
				'<span class="activity">' . esc_html__( 'Joined %s', 'buddyboss' ) . '</span>',
				esc_html( $register_date )
			);

			// We're in the members loop.
		} else {
			$meta = array(
				'last_activity' => sprintf( '%s', bp_get_member_last_active() ),
			);
		}

		// Make sure to include hooked meta.
		$extra_meta = bp_nouveau_get_hooked_member_meta();

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

/**
 * Load the appropriate content for the single member pages
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_member_template_part() {
	/**
	 * Fires before the display of member body content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_member_body' );

	if ( bp_is_user_front() ) {
		bp_displayed_user_front_template_part();

	} else {
		$template = 'plugins';

		if ( bp_is_user_activity() ) {
			$template = 'activity';
		} elseif ( bp_is_user_blogs() ) {
			$template = 'blogs';
		} elseif ( bp_is_user_friends() ) {
			$template = 'friends';
		} elseif ( bp_is_user_groups() ) {
			$template = 'groups';
		} elseif ( bp_is_user_messages() ) {
			$template = 'messages';
		} elseif ( bp_is_user_profile() ) {
			$template = 'profile';
		} elseif ( bp_is_user_notifications() ) {
			$template = 'notifications';
		} elseif ( bp_is_user_settings() ) {
			$template = 'settings';
		} elseif ( bp_is_user_invites() ) {
			$template = 'invites';
		} elseif ( bp_is_user_media() ) {
			$template = 'media';
		} elseif ( bp_is_user_document() ) {
			$template = 'document';
		} elseif ( bp_is_user_video() ) {
			$template = 'video';
		}

		bp_nouveau_member_get_template_part( $template );
	}

	/**
	 * Fires after the display of member body content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_member_body' );
}

/**
 * Use the appropriate Member header and enjoy a template hierarchy
 *
 * @since BuddyPress 3.0.0
 *
 * @return string HTML Output
 */
function bp_nouveau_member_header_template_part() {
	$template = 'member-header';

	if ( bp_displayed_user_use_cover_image_header() ) {
		$template = 'cover-image-header';
	}

	/**
	 * Fires before the display of a member's header.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_member_header' );

	// Get the template part for the header.
	bp_nouveau_member_get_template_part( $template );

	/**
	 * Fires after the display of a member's header.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_member_header' );

	bp_nouveau_template_notices();
}


/** WP Profile tags **********************************************************/

/**
 * Template tag to wrap all Legacy actions that was used
 * before and after the WP User's Profile.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_wp_profile_hooks( $type = 'before' ) {
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

/**
 * Does the displayed user has WP profile fields?
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if user has profile fields. False otherwise.
 */
function bp_nouveau_has_wp_profile_fields() {
	$user_id = bp_displayed_user_id();
	if ( ! $user_id ) {
		return false;
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	$fields              = bp_nouveau_get_wp_profile_fields( $user );
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
	$bp_nouveau                            = bp_nouveau();
	$bp_nouveau->members->wp_profile       = $user_profile_fields;
	$bp_nouveau->members->wp_profile_index = 0;

	return true;
}

/**
 * Check if there are still profile fields to output.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if the profile field exists. False otherwise.
 */
function bp_nouveau_wp_profile_fields() {
	$bp_nouveau = bp_nouveau();

	if ( isset( $bp_nouveau->members->wp_profile[ $bp_nouveau->members->wp_profile_index ] ) ) {
		return true;
	}

	$bp_nouveau->members->wp_profile_index = 0;
	unset( $bp_nouveau->members->wp_profile_current );

	return false;
}

/**
 * Set the current profile field and iterate into the loop.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_wp_profile_field() {
	$bp_nouveau = bp_nouveau();

	$bp_nouveau->members->wp_profile_current = $bp_nouveau->members->wp_profile[ $bp_nouveau->members->wp_profile_index ];
	$bp_nouveau->members->wp_profile_index  += 1;
}

/**
 * Output the WP profile field ID.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_wp_profile_field_id() {
	echo esc_attr( bp_nouveau_get_wp_profile_field_id() );
}
	/**
	 * Get the WP profile field ID.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return int the profile field ID.
	 */
function bp_nouveau_get_wp_profile_field_id() {
	$field = bp_nouveau()->members->wp_profile_current;

	/**
	 * Filters the WP profile field ID used for BuddyPress Nouveau.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $id Field ID.
	 */
	return apply_filters( 'bp_nouveau_get_wp_profile_field_id', $field->id );
}

/**
 * Output the WP profile field label.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_wp_profile_field_label() {
	echo esc_html( bp_nouveau_get_wp_profile_field_label() );
}

	/**
	 * Get the WP profile label.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string the profile field label.
	 */
function bp_nouveau_get_wp_profile_field_label() {
	$field = bp_nouveau()->members->wp_profile_current;

	/**
	 * Filters the WP profile field label used for BuddyPress Nouveau.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $label Field label.
	 */
	return apply_filters( 'bp_nouveau_get_wp_profile_field_label', $field->label );
}

/**
 * Output the WP profile field data.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_wp_profile_field_data() {
	$data = bp_nouveau_get_wp_profile_field_data();
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

/**
 * Get the WP profile field data.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string the profile field data.
 */
function bp_nouveau_get_wp_profile_field_data() {
	$field = bp_nouveau()->members->wp_profile_current;
	return $field->data;
}

/**
 * Get the user registered date meta.
 *
 * @since BuddyPress 1.9.1
 *
 * @param int $user_id User ID.
 *
 * @return string The user registered date.
 */
function bb_get_member_joined_date( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
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
