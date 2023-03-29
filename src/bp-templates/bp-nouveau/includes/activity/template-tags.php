<?php
/**
 * Activity Template tags
 *
 * @since   BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Before Activity's directory content legacy do_action hooks wrapper
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_before_activity_directory_content() {
	/**
	 * Fires at the begining of the templates BP injected content.
	 *
	 * @since BuddyPress 2.3.0
	 */
	do_action( 'bp_before_directory_activity' );

	/**
	 * Fires before the activity directory display content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_directory_activity_content' );
}

/**
 * After Activity's directory content legacy do_action hooks wrapper
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_after_activity_directory_content() {
	/**
	 * Fires after the display of the activity list.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_after_directory_activity_list' );

	/**
	 * Fires inside and displays the activity directory display content.
	 */
	do_action( 'bp_directory_activity_content' );

	/**
	 * Fires after the activity directory display content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_directory_activity_content' );

	/**
	 * Fires after the activity directory listing.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_after_directory_activity' );

	bp_get_template_part( 'common/js-templates/activity/comments' );
}

/**
 * Before Single Activity content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.2.2
 */
function bp_nouveau_before_single_activity_content() {
	/**
	 * Fires at the begining of the templates BP injected content.
	 *
	 * @since BuddyBoss 1.2.2
	 */
	do_action( 'bp_before_single_activity' );

	/**
	 * Fires before the single activity display content.
	 *
	 * @since BuddyBoss 1.2.2
	 */
	do_action( 'bp_before_single_activity_content' );
}

/**
 * After Single Activity content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.2.2
 */
function bp_nouveau_after_single_activity_content() {
	/**
	 * Fires after the single activity display content.
	 *
	 * @since BuddyBoss 1.2.2
	 */
	do_action( 'bp_after_single_activity_content' );

	/**
	 * Fires after the single activity listing.
	 *
	 * @since BuddyBoss 1.2.2
	 */
	do_action( 'bp_after_single_activity' );
}

/**
 * Enqueue needed scripts for the Activity Post Form
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_before_activity_post_form() {
	if ( bp_nouveau_current_user_can( 'publish_activity' ) ) {
		wp_enqueue_script( 'bp-nouveau-activity-post-form' );
	}

	/**
	 * Fires before the activity post form.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_activity_post_form' );
}

/**
 * Load JS Templates for the Activity Post Form
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_after_activity_post_form() {
	if ( bp_nouveau_current_user_can( 'publish_activity' ) ) {
		bp_get_template_part( 'common/js-templates/activity/form' );
	}

	/**
	 * Fires after the activity post form.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_activity_post_form' );
}

/**
 * Display the displayed user activity post form if needed
 *
 * @since BuddyPress 3.0.0
 *
 * @return string HTML.
 */
function bp_nouveau_activity_member_post_form() {

	/**
	 * Fires before the display of the member activity post form.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_member_activity_post_form' );

	if ( is_user_logged_in() && bp_is_user_activity() ) {
		bp_get_template_part( 'activity/post-form' );
	}

	/**
	 * Fires after the display of the member activity post form.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_member_activity_post_form' );
}

/**
 * Fire specific hooks into the activity entry template
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_activity_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's an activity entry hook
	$hook[] = 'activity';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Checks if an activity of the loop has some content.
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if the activity has some content. False Otherwise.
 */
function bp_nouveau_activity_has_content() {
	return bp_activity_has_content() || (bool) has_action( 'bp_activity_entry_content' );
}

/**
 * Output the Activity content into the loop.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_content() {
	if ( bp_nouveau_activity_has_content() ) {
		bp_activity_content_body();
	}

	/**
	 * Fires after the display of an activity entry content.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_activity_entry_content' );
}

/**
 * Output the Activity timestamp into the bp-timestamp attribute.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_timestamp() {
	echo esc_attr( bp_nouveau_get_activity_timestamp() );
}

/**
 * Get the Activity timestamp.
 *
 * @since BuddyPress 3.0.0
 *
 * @return integer The Activity timestamp.
 */
function bp_nouveau_get_activity_timestamp() {
	/**
	 * Filter here to edit the activity timestamp.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param integer $value The Activity timestamp.
	 */
	return apply_filters( 'bp_nouveau_get_activity_timestamp', strtotime( bp_get_activity_date_recorded() ) );
}

/**
 * Output the state buttons inside an Activity Loop.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_state() {

	$activity_id     = bp_get_activity_id();
	$like_text       = bp_activity_get_favorite_users_string( $activity_id );
	$comment_count   = bp_activity_get_comment_count();
	$favorited_users = bp_activity_get_favorite_users_tooltip_string( $activity_id );

	?>
	<div class="activity-state <?php echo $like_text ? 'has-likes' : ''; ?> <?php echo $comment_count ? 'has-comments' : ''; ?>">
		<a href="javascript:void(0);" class="activity-state-likes">
			<span class="like-text hint--bottom hint--medium hint--multiline" data-hint="<?php echo ( $favorited_users ) ? $favorited_users : ''; ?>">
				<?php echo $like_text ?: ''; ?>
			</span>
		</a>
		<?php if ( bp_activity_can_comment() ) :
			?>
			<span class="ac-state-separator">&middot;</span>
			<?php
			$activity_state_comment_class['activity_state_comment_class'] = 'activity-state-comments';
			$activity_state_class            = apply_filters( 'bp_nouveau_get_activity_comment_buttons_activity_state', $activity_state_comment_class, $activity_id );
			?>
			<a href="#" class="<?php echo esc_attr( trim( implode( ' ', $activity_state_class ) ) ); ?>">
				<span class="comments-count">
					<?php
					if ( $comment_count > 1 ) {
						printf( _x( '%d Comments', 'placeholder: activity comments count', 'buddyboss' ), $comment_count );
					} else {
						printf( _x( '%d Comment', 'placeholder: activity comment count', 'buddyboss' ), $comment_count );
					}
					?>
				</span>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Output the action buttons inside an Activity inner content.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 */
function bb_nouveau_activity_inner_buttons( $args = array() ) {

	$output = join( ' ', bb_nouveau_get_activity_inner_buttons( $args ) );

	ob_start();

	/**
	 * Fires at the end of the activity entry meta data area.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_activity_inner_meta' );

	$output .= ob_get_clean();

	$has_content = trim( $output, ' ' );
	if ( ! $has_content ) {
		return;
	}

	if ( empty( $args ) ) {
		$args = array( 'container_classes' => array( 'activity-inner-meta' ) );
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Get the action buttons inside an Activity inner content,
 *
 * @since BuddyBoss 1.7.2
 *
 * @param array $args Button attributes.
 *
 * @return array
 */
function bb_nouveau_get_activity_inner_buttons( $args ) {
	global $activities_template;

	$buttons = array();

	if ( empty( $activities_template ) ) {
		return $buttons;
	}

	$activity_id    = bp_get_activity_id();
	$activity_type  = bp_get_activity_type();

	if ( ! $activity_id ) {
		return $buttons;
	}

	/**
	 * Filter to add your buttons, use the position argument to choose where to insert it.
	 *
	 * @since BuddyBoss 1.7.2
	 *
	 * @param array $buttons     The list of buttons.
	 * @param int   $activity_id The current activity ID.
	 */
	$buttons_group = apply_filters( 'bb_nouveau_get_activity_inner_buttons', $buttons, $activity_id, $args );

	if ( ! $buttons_group ) {
		return $buttons;
	}

	// It's the first entry of the loop, so build the Group and sort it.
	if ( ! isset( bp_nouveau()->activity->inner_buttons ) || ! is_a( bp_nouveau()->activity->inner_buttons, 'BP_Buttons_Group' ) ) {
		$sort                                 = true;
		bp_nouveau()->activity->inner_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first entry, the order is set, we simply need to update the Buttons Group.
	} else {
		$sort = false;
		bp_nouveau()->activity->inner_buttons->update( $buttons_group );
	}

	$return = bp_nouveau()->activity->inner_buttons->get( $sort );

	return $return;
}

/**
 * Output the action buttons inside an Activity Loop.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_activity_entry_buttons( $args = array() ) {
	$output = join( ' ', bp_nouveau_get_activity_entry_buttons( $args ) );

	ob_start();

	/**
	 * Fires at the end of the activity entry meta data area.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_activity_entry_meta' );

	$output .= ob_get_clean();

	$has_content = trim( $output, ' ' );
	if ( ! $has_content ) {
		return;
	}

	if ( ! $args ) {
		$args = array( 'classes' => array( 'activity-meta' ) );
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Get the action buttons inside an Activity Loop,
 *
 * @since BuddyPress 3.0.0
 * @todo  This function is too large and needs refactoring and reviewing.
 */
function bp_nouveau_get_activity_entry_buttons( $args ) {
	$buttons = array();

	if ( ! isset( $GLOBALS['activities_template'] ) ) {
		return $buttons;
	}

	$activity_id    = bp_get_activity_id();
	$activity_type  = bp_get_activity_type();
	$parent_element = '';
	$button_element = 'a';

	if ( ! $activity_id ) {
		return $buttons;
	}

	/*
	 * If the container is set to 'ul' force the $parent_element to 'li',
	 * else use parent_element args if set.
	 *
	 * This will render li elements around anchors/buttons.
	 */
	if ( isset( $args['container'] ) && 'ul' === $args['container'] ) {
		$parent_element = 'li';
	} elseif ( ! empty( $args['parent_element'] ) ) {
		$parent_element = $args['parent_element'];
	}

	$parent_attr = ( ! empty( $args['parent_attr'] ) ) ? $args['parent_attr'] : array();

	/*
	 * If we have an arg value for $button_element passed through
	 * use it to default all the $buttons['button_element'] values
	 * otherwise default to 'a' (anchor)
	 * Or override & hardcode the 'element' string on $buttons array.
	 *
	 */
	if ( ! empty( $args['button_element'] ) ) {
		$button_element = $args['button_element'];
	}

	if ( bp_activity_can_favorite() && bp_is_activity_like_active() ) {

		// If button element set attr needs to be data-* else 'href'.
		if ( 'button' === $button_element ) {
			$key = 'data-bp-nonce';
		} else {
			$key = 'href';
		}

		if ( ! bp_get_activity_is_favorite() ) {
			$fav_args = array(
				'parent_element' => $parent_element,
				'parent_attr'    => $parent_attr,
				'button_element' => $button_element,
				'link_class'     => 'button fav bp-secondary-action',
				// 'data_bp_tooltip'  => __( 'Like', 'buddyboss' ),
				'link_text'      => __( 'Like', 'buddyboss' ),
				'aria-pressed'   => 'false',
				'link_attr'      => bp_get_activity_favorite_link(),
			);

		} else {
			$fav_args = array(
				'parent_element' => $parent_element,
				'parent_attr'    => $parent_attr,
				'button_element' => $button_element,
				'link_class'     => 'button unfav bp-secondary-action',
				// 'data_bp_tooltip' => __( 'Unlike', 'buddyboss' ),
				'link_text'      => __( 'Unlike', 'buddyboss' ),
				'aria-pressed'   => 'true',
				'link_attr'      => bp_get_activity_unfavorite_link(),
			);
		}

		$buttons['activity_favorite'] = array(
			'id'                => 'activity_favorite',
			'position'          => 4,
			'component'         => 'activity',
			'parent_element'    => $parent_element,
			'parent_attr'       => $parent_attr,
			'must_be_logged_in' => true,
			'button_element'    => $fav_args['button_element'],
			'link_text'         => sprintf( '<span class="bp-screen-reader-text">%1$s</span>  <span class="like-count">%2$s</span>', esc_html( $fav_args['link_text'] ), esc_html( $fav_args['link_text'] ) ),
			'button_attr'       => array(
				$key           => $fav_args['link_attr'],
				'class'        => $fav_args['link_class'],
				// 'data-bp-tooltip' => $fav_args['data_bp_tooltip'],
				'aria-pressed' => $fav_args['aria-pressed'],
			),
		);
	}

	/*
	 * The view conversation button and the comment one are sharing
	 * the same id because when display_comments is on stream mode,
	 * it is not possible to comment an activity comment and as we
	 * are updating the links to avoid sorting the activity buttons
	 * for each entry of the loop, it's a convenient way to make
	 * sure the right button will be displayed.
	 */

	// Add the Comment button if the user can comment.
	if ( bp_activity_can_comment() ) {
		if ( 'activity_comment' === $activity_type ) {
			$buttons['activity_conversation'] = array(
				'id'                => 'activity_conversation',
				'position'          => 5,
				'component'         => 'activity',
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'must_be_logged_in' => false,
				'button_element'    => $button_element,
				'button_attr'       => array(
					'class'               => 'button view bp-secondary-action bp-tooltip',
					'data-bp-tooltip'     => __( 'View Conversation', 'buddyboss' ),
					'data-bp-tooltip-pos' => 'up',
				),
				'link_text'         => sprintf(
					'<span class="bp-screen-reader-text">%1$s</span>',
					__( 'View Conversation', 'buddyboss' )
				),
			);

			// If button element set add url link to data-attr.
			if ( 'button' === $button_element ) {
				$buttons['activity_conversation']['button_attr']['data-bp-url'] = bp_get_activity_thread_permalink();
			} else {
				$buttons['activity_conversation']['button_attr']['href'] = bp_get_activity_thread_permalink();
				$buttons['activity_conversation']['button_attr']['role'] = 'button';
			}

			/*
			* We always create the Button to make sure we always have the right numbers of buttons,
			* no matter the previous activity had less.
			*/
		} else {
			$buttons['activity_conversation'] = array(
				'id'                => 'activity_conversation',
				'position'          => 5,
				'component'         => 'activity',
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'must_be_logged_in' => true,
				'button_element'    => $button_element,
				'button_attr'       => array(
					'id'            => 'acomment-comment-' . $activity_id,
					'class'         => 'button acomment-reply bp-primary-action',
					'aria-expanded' => 'false',
				),
				'link_text'         => sprintf(
					'<span class="bp-screen-reader-text">%1$s</span> <span class="comment-count">%2$s</span>',
					__( 'Comment', 'buddyboss' ),
					__( 'Comment', 'buddyboss' )
				),
			);

			// If button element set add href link to data-attr.
			if ( 'button' === $button_element ) {
				$buttons['activity_conversation']['button_attr']['data-bp-url'] = bp_get_activity_comment_link();
			} else {
				$buttons['activity_conversation']['button_attr']['href'] = bp_get_activity_comment_link();
				$buttons['activity_conversation']['button_attr']['role'] = 'button';
			}
		}
	}

	/**
	 * Filter to add your buttons, use the position argument to choose where to insert it.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $buttons     The list of buttons.
	 * @param int   $activity_id The current activity ID.
	 */
	$buttons_group = apply_filters( 'bp_nouveau_get_activity_entry_buttons', $buttons, $activity_id );

	if ( ! $buttons_group ) {
		return $buttons;
	}

	// It's the first entry of the loop, so build the Group and sort it.
	if ( ! isset( bp_nouveau()->activity->entry_buttons ) || ! is_a( bp_nouveau()->activity->entry_buttons, 'BP_Buttons_Group' ) ) {
		$sort                                 = true;
		bp_nouveau()->activity->entry_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first entry, the order is set, we simply need to update the Buttons Group.
	} else {
		$sort = false;
		bp_nouveau()->activity->entry_buttons->update( $buttons_group );
	}

	$return = bp_nouveau()->activity->entry_buttons->get( $sort );

	if ( ! $return ) {
		return array();
	}

	/**
	 * Leave a chance to adjust the $return
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $return      The list of buttons ordered.
	 * @param int   $activity_id The current activity ID.
	 */
	do_action_ref_array( 'bp_nouveau_return_activity_entry_buttons', array( &$return, $activity_id ) );

	return $return;
}

/**
 * Output Activity Comments if any
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_comments() {
	global $activities_template;

	if ( empty( $activities_template->activity->children ) ) {
		return;
	}

	bp_nouveau_activity_recurse_comments( $activities_template->activity );
}

/**
 * Loops through a level of activity comments and loads the template for each.
 *
 * Note: This is an adaptation of the bp_activity_recurse_comments() BuddyPress core function
 *
 * @since BuddyPress 3.0.0
 *
 * @param object $comment The activity object currently being recursed.
 */
function bp_nouveau_activity_recurse_comments( $comment ) {
	global $activities_template;

	if ( empty( $comment->children ) ) {
		return;
	}

	/**
	 * Filters the opening tag for the template that lists activity comments.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value Opening tag for the HTML markup to use.
	 */
	echo apply_filters( 'bp_activity_recurse_comments_start_ul', '<ul>' );

	foreach ( (array) $comment->children as $comment_child ) {

		// Put the comment into the global so it's available to filters.
		$activities_template->activity->current_comment = $comment_child;

		/**
		 * Fires before the display of an activity comment.
		 *
		 * @since BuddyPress 1.5.0
		 */
		do_action( 'bp_before_activity_comment' );

		bp_get_template_part( 'activity/comment' );

		/**
		 * Fires after the display of an activity comment.
		 *
		 * @since BuddyPress 1.5.0
		 */
		do_action( 'bp_after_activity_comment' );

		unset( $activities_template->activity->current_comment );
	}

	/**
	 * Filters the closing tag for the template that list activity comments.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value Closing tag for the HTML markup to use.
	 */
	echo apply_filters( 'bp_activity_recurse_comments_end_ul', '</ul>' );
}

/**
 * Ouptut the Activity comment action string
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_comment_action() {
	echo bp_nouveau_get_activity_comment_action();
}

/**
 * Get the Activity comment action string
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_activity_comment_action() {

	/**
	 * Filter to edit the activity comment action.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $value HTML Output
	 */
	return apply_filters(
		'bp_nouveau_get_activity_comment_action',
		sprintf(
			/* translators: 1: user profile link, 2: user name, 3: activity permalink, 4: activity recorded date, 5: activity timestamp, 6: activity human time since */
			__( '<a class="author-name" href="%1$s">%2$s</a> <a href="%3$s" class="activity-time-since"><time class="time-since" datetime="%4$s" data-bp-timestamp="%5$d">%6$s</time></a>', 'buddyboss' ),
			esc_url( bp_get_activity_comment_user_link() ),
			esc_html( bp_get_activity_comment_name() ),
			esc_url( bp_get_activity_comment_permalink() ),
			esc_attr( bp_get_activity_comment_date_recorded_raw() ),
			esc_attr( strtotime( bp_get_activity_comment_date_recorded_raw() ) ),
			esc_attr( bp_get_activity_comment_date_recorded() )
		)
	);
}

/**
 * Load the Activity comment form
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_comment_form() {
	bp_get_template_part( 'activity/comment-form' );
}

/**
 * Output the action buttons for the activity comments
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_activity_comment_buttons( $args = array() ) {
	$output = join( ' ', bp_nouveau_get_activity_comment_buttons( $args ) );

	ob_start();
	/**
	 * Fires after the defualt comment action options display.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_activity_comment_options' );

	$output     .= ob_get_clean();
	$has_content = trim( $output, ' ' );
	if ( ! $has_content ) {
		return;
	}

	if ( ! $args ) {
		$args = array( 'classes' => array( 'acomment-options' ) );
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Get the action buttons for the activity comments
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 *
 * @return array
 */
function bp_nouveau_get_activity_comment_buttons( $args ) {
	$buttons = array();

	if ( ! isset( $GLOBALS['activities_template'] ) ) {
		return $buttons;
	}

	$activity_comment_id = bp_get_activity_comment_id();
	$activity_id         = bp_get_activity_id();

	if ( ! $activity_comment_id || ! $activity_id ) {
		return $buttons;
	}

	/*
	 * If the 'container' is set to 'ul'
	 * set a var $parent_element to li
	 * otherwise simply pass any value found in args
	 * or set var false.
	 */
	if ( 'ul' === $args['container'] ) {
		$parent_element = 'li';
	} elseif ( ! empty( $args['parent_element'] ) ) {
		$parent_element = $args['parent_element'];
	} else {
		$parent_element = false;
	}

	$parent_attr = ( ! empty( $args['parent_attr'] ) ) ? $args['parent_attr'] : array();

	/*
	 * If we have an arg value for $button_element passed through
	 * use it to default all the $buttons['button_element'] values
	 * otherwise default to 'a' (anchor).
	 */
	if ( ! empty( $args['button_element'] ) ) {
		$button_element = $args['button_element'];
	} else {
		$button_element = 'a';
	}

	$buttons = array();

	$buttons['activity_comment_reply'] = array(
			'id'                => 'activity_comment_reply',
			'position'          => 5,
			'component'         => 'activity',
			'must_be_logged_in' => true,
			'parent_element'    => $parent_element,
			'parent_attr'       => $parent_attr,
			'button_element'    => $button_element,
			'link_text'         => esc_html__( 'Reply', 'buddyboss' ),
			'button_attr'       => array(
					'class' => "acomment-reply bp-primary-action",
					'id'    => sprintf( 'acomment-reply-%1$s-from-%2$s', $activity_id, $activity_comment_id ),
			),
	);

	// If button element set add nonce link to data-attr attr
	if ( 'button' === $button_element ) {
		$buttons['activity_comment_reply']['button_attr']['data-bp-act-reply-nonce']         = sprintf( '#acomment-%s', $activity_comment_id );
	} else {
		$buttons['activity_comment_reply']['button_attr']['href']  = sprintf( '#acomment-%s', $activity_comment_id );
	}

	/**
	 * Filter to add your buttons, use the position argument to choose where to insert it.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $buttons             The list of buttons.
	 * @param int   $activity_comment_id The current activity comment ID.
	 * @param int   $activity_id         The current activity ID.
	 */
	$buttons_group = apply_filters( 'bp_nouveau_get_activity_comment_buttons', $buttons, $activity_comment_id, $activity_id );

	if ( ! $buttons_group ) {
		return $buttons;
	}

	// It's the first comment of the loop, so build the Group and sort it.
	if ( ! isset( bp_nouveau()->activity->comment_buttons ) || ! is_a( bp_nouveau()->activity->comment_buttons, 'BP_Buttons_Group' ) ) {
		$sort                                   = true;
		bp_nouveau()->activity->comment_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first comment, the order is set, we simply need to update the Buttons Group.
	} else {
		$sort = false;
		bp_nouveau()->activity->comment_buttons->update( $buttons_group );
	}

	$return = bp_nouveau()->activity->comment_buttons->get( $sort );

	if ( ! $return ) {
		return array();
	}

	/**
	 * If post comment / Activity comment sync is on, it's safer
	 * to unset the comment button just before returning it.
	 */
	if ( ! bp_activity_can_comment_reply( bp_activity_current_comment() ) ) {
		unset( $return['activity_comment_reply'] );
	}

	/**
	 * Leave a chance to adjust the $return
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $return              The list of buttons ordered.
	 * @param int   $activity_comment_id The current activity comment ID.
	 * @param int   $activity_id         The current activity ID.
	 */
	do_action_ref_array(
		'bp_nouveau_return_activity_comment_buttons',
		array(
			&$return,
			$activity_comment_id,
			$activity_id,
		)
	);

	return $return;
}

/**
 * Get the action buttons for the activity blog post comments
 *
 * @since BuddyBoss 1.7.2
 *
 * @param object $comment Blog post comment.
 * @param array  $args    Comment meta.
 *
 * @return void
 */
function bb_nouveau_activity_comment_meta_buttons( $comment, $args ) {
	$buttons = array();

	/*
	 * If we have an arg value for $button_element passed through
	 * use it to default all the $buttons['button_element'] values
	 * otherwise default to 'a' (anchor).
	 */
	if ( ! empty( $args['button_element'] ) ) {
		$button_element = $args['button_element'];
	} else {
		$button_element = 'a';
	}

	// Button attributes.
	$buttons['activity_comment_reply'] = array(
		'id'                => 'activity_comment_reply',
		'position'          => 5,
		'component'         => 'activity',
		'must_be_logged_in' => true,
		'parent_element'    => false,
		'parent_attr'       => array(),
		'button_element'    => $button_element,
		'link_text'         => __( 'Reply', 'buddyboss' ),
		'button_attr'       => array(
			'data-comment_id'      => $comment->comment_ID,
			'data-comment_post_id' => $comment->comment_post_ID,
			'data-action-type'     => 'submit',
			'class'                => 'acomment-reply bp-primary-action',
			'id'                   => sprintf( 'acomment-reply-from-%1$s', $comment->comment_ID ),
		),
	);

	$nonce = wp_nonce_url( trailingslashit( bp_get_activity_directory_permalink() . 'delete/' . $comment->comment_ID ) . '?cid=' . $comment->comment_ID, 'bp_activity_delete_link' );

	// If button element set add nonce link to data-attr attr.
	if ( 'button' === $button_element ) {
		$buttons['activity_comment_reply']['button_attr']['data-bp-act-reply-nonce']         = sprintf( '#acomment-%s', $comment->comment_ID );
		$buttons['activity_comment_delete']['button_attr']['data-bp-act-reply-delete-nonce'] = $nonce;
	} else {
		$buttons['activity_comment_reply']['button_attr']['href']  = sprintf( '#acomment-%s', $comment->comment_ID );
		$buttons['activity_comment_delete']['button_attr']['href'] = $nonce;
	}

	// It's the first entry of the loop, so build the Group and sort it.
	bp_nouveau()->activity->comment_buttons = new BP_Buttons_Group( $buttons );
	$returns                                = bp_nouveau()->activity->comment_buttons->get( true );

	$content = '';

	// All button in single content.
	foreach ( $returns as $return ) {
		$content .= $return;
	}

	echo empty( $content ) ? '' : '<div class="bp-generic-meta activity-meta action">' . $content . '</div>';
}

/**
 * Output the privacy option inside an Activity Loop.
 *
 * @since BuddyBoss 1.2.3
 */
function bp_nouveau_activity_privacy() {
	if ( bp_activity_user_can_edit( false, true ) && ! bp_is_group() ) {

		if ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() ) {
			return;
		}

		$privacy                   = bp_get_activity_privacy();
		$media_activity            = ( 'media' === $privacy || ( isset( $_REQUEST['action'] ) && ( 'media_get_activity' === $_REQUEST['action'] || 'media_get_media_description' === $_REQUEST['action'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$document_activity         = ( 'document' === $privacy || ( isset( $_REQUEST['action'] ) && ( 'document_get_activity' === $_REQUEST['action'] || 'document_get_document_description' === $_REQUEST['action'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$video_activity            = ( 'video' === $privacy || ( isset( $_REQUEST['action'] ) && 'video_get_activity' === $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$parent_activity_id        = false;
		$parent_activity_permalink = false;
		$group_id                  = false;
		$album_id                  = false;
		$album_url                 = '';
		$folder_id                 = false;
		$folder_url                = '';

		// Get media privacy to show.
		if ( bp_is_active( 'media' ) ) {
			if ( $media_activity ) {
				$media_id = BP_Media::get_activity_media_id( bp_get_activity_id() );
				$media    = new BP_Media( $media_id );

				if ( ! empty( $media ) ) {
					$privacy  = $media->privacy;
					$group_id = $media->group_id;
					$album_id = $media->album_id;

					if ( ! empty( $album_id ) ) {
						$album     = new BP_Media_Album( $album_id );
						$privacy   = $album->privacy;
						$album_url = trailingslashit( bp_core_get_user_domain( $album->user_id ) . bp_get_media_slug() . '/albums/' . $album_id );
					} else {
						$parent_activity_id        = get_post_meta( $media->attachment_id, 'bp_media_parent_activity_id', true );
						$parent_activity_permalink = bp_activity_get_permalink( $parent_activity_id );
					}
				}
			}

			if ( $document_activity ) {
				$document_id = BP_Document::get_activity_document_id( bp_get_activity_id() );
				$document    = new BP_Document( $document_id );
				if ( ! empty( $document ) ) {
					$privacy   = $document->privacy;
					$group_id  = $document->group_id;
					$folder_id = $document->folder_id;

					if ( ! empty( $folder_id ) ) {
						$folder_id  = bp_document_get_root_parent_id( $folder_id );
						$folder     = new BP_Document_Folder( $folder_id );
						$privacy    = $folder->privacy;
						$folder_url = trailingslashit( bp_core_get_user_domain( $folder->user_id ) . bp_get_document_slug() . '/folders/' . $folder_id );
					} else {
						$parent_activity_id        = get_post_meta( $document->attachment_id, 'bp_document_parent_activity_id', true );
						$parent_activity_permalink = bp_activity_get_permalink( $parent_activity_id );
					}
				}
			}

			if ( $video_activity ) {
				$video_id = BP_Video::get_activity_video_id( bp_get_activity_id() );
				$video    = new BP_Video( $video_id );

				if ( ! empty( $video ) ) {
					$privacy  = $video->privacy;
					$group_id = $video->group_id;
					$album_id = $video->album_id;

					if ( ! empty( $album_id ) ) {
						$album     = new BP_Video_Album( $album_id );
						$privacy   = $album->privacy;
						$album_url = trailingslashit( bp_core_get_user_domain( $album->user_id ) . bp_get_media_slug() . '/albums/' . $album_id );
					} else {
						$parent_activity_id        = get_post_meta( $video->attachment_id, 'bp_video_parent_activity_id', true );
						$parent_activity_permalink = bp_activity_get_permalink( $parent_activity_id );
					}
				}
			}

			$activity_album_id = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_album_activity', true );
			if ( ! empty( $activity_album_id ) ) {
				$album_id       = $activity_album_id;
				$album          = new BP_Media_Album( $album_id );
				$privacy        = $album->privacy;
				$album_url      = trailingslashit( bp_core_get_user_domain( $album->user_id ) . bp_get_media_slug() . '/albums/' . $album_id );
				$media_activity = true;
			} else {
				$media_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_ids', true );
				if ( ! empty( $media_ids ) ) {
					$media_ids = explode( ',', $media_ids );
					$media_id  = ! empty( $media_ids ) ? $media_ids[0] : false;
					$media     = new BP_Media( $media_id );

					if ( ! empty( $media->album_id ) ) {
						$album_id       = $media->album_id;
						$album          = new BP_Media_Album( $album_id );
						$privacy        = $album->privacy;
						$album_url      = trailingslashit( bp_core_get_user_domain( $album->user_id ) . bp_get_media_slug() . '/albums/' . $album_id );
						$media_activity = true;
						bp_activity_update_meta( bp_get_activity_id(), 'bp_media_album_activity', $album_id );
					}
				}
			}

			$activity_video_album_id = bp_activity_get_meta( bp_get_activity_id(), 'bp_video_album_activity', true );
			if ( ! empty( $activity_video_album_id ) ) {
				$album_id       = $activity_video_album_id;
				$album          = new BP_Video_Album( $album_id );
				$privacy        = $album->privacy;
				$album_url      = trailingslashit( bp_core_get_user_domain( $album->user_id ) . bp_get_media_slug() . '/albums/' . $album_id );
				$media_activity = true;
			} else {
				$video_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_video_ids', true );
				if ( ! empty( $video_ids ) ) {
					$video_ids = explode( ',', $video_ids );
					$video_id  = ! empty( $video_ids ) ? $video_ids[0] : false;
					$video     = new BP_Video( $video_id );

					if ( ! empty( $video->album_id ) ) {
						$album_id       = $video->album_id;
						$album          = new BP_Video_Album( $album_id );
						$privacy        = $album->privacy;
						$album_url      = trailingslashit( bp_core_get_user_domain( $album->user_id ) . bp_get_media_slug() . '/albums/' . $album_id );
						$media_activity = true;
						bp_activity_update_meta( bp_get_activity_id(), 'bp_video_album_activity', $album_id );
					}
				}
			}

			$activity_folder_id = bp_activity_get_meta( bp_get_activity_id(), 'bp_document_folder_activity', true );
			if ( ! empty( $activity_folder_id ) ) {
				$folder_id         = $activity_folder_id;
				$folder_id         = bp_document_get_root_parent_id( $folder_id );
				$folder            = new BP_Document_Folder( $folder_id );
				$privacy           = $folder->privacy;
				$folder_url        = trailingslashit( bp_core_get_user_domain( $folder->user_id ) . bp_get_document_slug() . '/folders/' . $folder_id );
				$document_activity = true;
			} else {
				$document_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_document_ids', true );
				if ( ! empty( $document_ids ) ) {
					$document_ids = explode( ',', $document_ids );
					$document_id  = ! empty( $document_ids ) ? $document_ids[0] : false;
					$document     = new BP_Document( $document_id );

					if ( ! empty( $document->folder_id ) ) {
						$folder_id         = $document->folder_id;
						$folder_id         = bp_document_get_root_parent_id( $folder_id );
						$folder            = new BP_Document_Folder( $folder_id );
						$privacy           = $folder->privacy;
						$folder_url        = trailingslashit( bp_core_get_user_domain( $folder->user_id ) . bp_get_document_slug() . '/folders/' . $folder_id );
						$document_activity = true;
						bp_activity_update_meta( bp_get_activity_id(), 'bp_document_folder_activity', $folder_id );
					}
				}
			}
		}

		if ( ( $media_activity || $document_activity || $video_activity ) && empty( $group_id ) && $parent_activity_id ) {
			$parent_activity = new BP_Activity_Activity( $parent_activity_id );

			if ( ! empty( $parent_activity->id ) ) {
				$group_id = $parent_activity->item_id;
			}
		}

		if ( ! empty( $group_id ) ) {
			return;
		}

		$privacy_items = bp_activity_get_visibility_levels();

		if ( ( $media_activity || $video_activity ) && ( ( $parent_activity_id && $parent_activity_permalink && bb_user_can_create_activity() ) || ( $album_id && ! empty( $album_url ) ) ) ) {
			?>
			<div class="bb-media-privacy-wrap">
				<span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo ! empty( $privacy_items[ $privacy ] ) ? esc_attr( $privacy_items[ $privacy ] ) : esc_attr( $privacy ); ?>">
					<span class="privacy selected <?php echo esc_attr( $privacy ); ?>"></span>
				</span>
				<ul class="activity-privacy">
					<?php if ( $album_id && ! empty( $album_url ) ) : ?>
						<li class="bb-edit-privacy" data-value="<?php echo esc_url( $album_url ); ?>">
							<a href="<?php echo esc_url( $album_url ); ?>" data-value="<?php echo esc_url( $album_url ); ?>"><?php esc_html_e( 'Edit Album Privacy', 'buddyboss' ); ?></a>
						</li>
					<?php elseif ( $parent_activity_id && $parent_activity_permalink ) : ?>
						<li class="bb-edit-privacy" data-value="<?php echo esc_url( $parent_activity_permalink ); ?>">
							<a href="<?php echo esc_url( $parent_activity_permalink ); ?>" data-value="<?php echo esc_url( $parent_activity_permalink ); ?>"><?php esc_html_e( 'Edit Post Privacy', 'buddyboss' ); ?></a>
						</li>
					<?php endif; ?>
				</ul>
			</div>
			<?php
		} elseif ( $document_activity && ( ( $parent_activity_id && $parent_activity_permalink && bb_user_can_create_activity() ) || ( $folder_id && ! empty( $folder_url ) ) ) ) {
			?>
			<div class="bb-media-privacy-wrap">
				<span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo ! empty( $privacy_items[ $privacy ] ) ? esc_attr( $privacy_items[ $privacy ] ) : esc_attr( $privacy ); ?>">
					<span class="privacy selected <?php echo esc_attr( $privacy ); ?>"></span>
				</span>
				<ul class="activity-privacy">
					<?php
					if ( $folder_id && ! empty( $folder_url ) ) :
						$folder_url = $folder_url . '#openEditFolder';
						?>
						<li data-value="<?php echo esc_url( $folder_url ); ?>" class="bb-edit-privacy <?php echo esc_attr( $privacy ); ?>">
							<a data-value="<?php echo esc_url( $folder_url ); ?>" href="<?php echo esc_url( $folder_url ); ?>"><?php esc_html_e( 'Edit Folder Privacy', 'buddyboss' ); ?></a>
						</li>
					<?php elseif ( $parent_activity_id && $parent_activity_permalink ) : ?>
						<li data-value="<?php echo esc_url( $parent_activity_permalink ); ?>" class="bb-edit-privacy <?php echo esc_attr( $privacy ); ?>">
							<a data-value="<?php echo esc_url( $parent_activity_permalink ); ?>" href="<?php echo esc_url( $parent_activity_permalink ); ?>"><?php esc_html_e( 'Edit Post Privacy', 'buddyboss' ); ?></a>
						</li>
					<?php endif; ?>
				</ul>
			</div>
			<?php
		} elseif ( bb_user_can_create_activity() ) {
			?>
			<div class="bb-media-privacy-wrap">
				<span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo ! empty( $privacy_items[ $privacy ] ) ? esc_attr( $privacy_items[ $privacy ] ) : esc_attr( $privacy ); ?>">
					<span class="privacy selected <?php echo esc_attr( $privacy ); ?>"></span>
				</span>
				<?php
				$class = 'activity-privacy';
				if ( $media_activity ) {
					$class = 'media-privacy';
				} elseif ( $document_activity ) {
					$class = 'document-privacy';
				}
				?>
				<ul class="<?php echo esc_attr( $class ); ?>">
					<?php
					foreach ( $privacy_items as $item_key => $privacy_item ) {
						?>
						<li data-value="<?php echo esc_attr( $item_key ); ?>" class="<?php echo esc_attr( $item_key ); ?> <?php echo $item_key === $privacy ? 'selected' : ''; ?>"><?php echo esc_html( $privacy_item ); ?></li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php
		}
	}
}

/**
 * Get log is edited activity.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param bool $echo        Whether to print or not.
 * @param int  $activity_id Activity id.
 *
 * @return string text.
 */
function bp_nouveau_activity_is_edited( $activity_id = 0, $echo = true ) {

	if ( empty( $activity_id ) ) {
		$activity_id = bp_get_activity_id();
	}

	if ( empty( $activity_id ) ) {
		return;
	}

	$is_edited = bp_activity_get_meta( $activity_id, '_is_edited', true );

	if ( $is_edited ) {
		$activity_text = '<span class="bb-activity-edited-text"> ' . __( '(edited)', 'buddyboss' ) . ' </span>';

	} else {
		$activity_text = null;
	}

	$rendered_text = apply_filters( 'bp_nouveau_activity_is_edited', $activity_text, $activity_id );

	if ( $echo ) {
		echo $rendered_text;
	} else {
		return $rendered_text;
	}
}

/**
 * Fetch and update the video description.
 *
 * @since BuddyBoss 1.5.7
 *
 * @param int $activity_id The current activity ID.
 */
function bp_nouveau_video_activity_description( $activity_id = 0 ) {
	if ( empty( $activity_id ) ) {
		$activity_id = bp_get_activity_id();
	}

	if ( empty( $activity_id ) ) {
		return;
	}

	$attachment_id = BP_Video::get_activity_attachment_id( $activity_id );
	$video_id      = BP_Video::get_activity_video_id( $activity_id );

	if ( empty( $attachment_id ) ) {
		return;
	}

	$content = get_post_field( 'post_content', $attachment_id );

	echo '<div class="activity-media-description">' .
		 '<div class="bp-media-activity-description">' . $content . '</div>'; // phpcs:ignore

	if ( bp_activity_user_can_edit( false, true ) ) {
		?>

		<a class="bp-add-media-activity-description <?php echo( ! empty( $content ) ? 'show-edit' : 'show-add' ); ?>" href="#">
			<span class="bb-icon-l bb-icon-edit "></span>
			<span class="add"><?php esc_html_e( 'Add a description', 'buddyboss' ); ?></span>
			<span class="edit"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></span>
		</a>

		<?php
	}

	if ( bp_activity_user_can_edit( false, true ) ) {
		?>

		<div class="bp-edit-media-activity-description" style="display: none;">
			<div class="innerWrap">
				<textarea id="add-activity-description" title="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>" class="textInput" name="caption_text" placeholder="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>" role="textbox"><?php echo wp_kses_post( $content ); ?></textarea>
			</div>
			<div class="in-profile description-new-submit">
				<input type="hidden" id="bp-attachment-id" value="<?php echo esc_attr( $attachment_id ); ?>">
				<input type="submit" id="bp-activity-description-new-submit" class="button small" name="description-new-submit" value="<?php esc_html_e( 'Done Editing', 'buddyboss' ); ?>">
				<input type="reset" id="bp-activity-description-new-reset" class="text-button small" value="<?php esc_html_e( 'Cancel', 'buddyboss' ); ?>">
			</div>
		</div>

		<?php
	}

	echo '</div>';
	if ( ! empty( $video_id ) ) {
		$video_privacy    = bb_media_user_can_access( $video_id, 'video' );
		$can_download_btn = true === (bool) $video_privacy['can_download'];
		if ( $can_download_btn ) {
			$download_url = bp_video_download_link( $attachment_id, $video_id );
			if ( $download_url ) {
				?>
				<a class="download-media" href="<?php echo esc_url( $download_url ); ?>"> <?php esc_html_e( 'Download', 'buddyboss' ); ?></a>
				<?php
			}
		}
	}
}

/**
 * Fetch and update the media description.
 *
 * @since BuddyBoss 1.3.5
 *
 * @param int $activity_id The current activity ID.
 */
function bp_nouveau_activity_description( $activity_id = 0 ) {
	if ( empty( $activity_id ) ) {
		$activity_id = bp_get_activity_id();
	}

	if ( empty( $activity_id ) ) {
		return;
	}

	$attachment_id = BP_Media::get_activity_attachment_id( $activity_id );
	$media_id      = BP_Media::get_activity_media_id( $activity_id );

	if ( empty( $attachment_id ) ) {
		return;
	}

	$content = get_post_field( 'post_content', $attachment_id );

	echo '<div class="activity-media-description">' .
		 '<div class="bp-media-activity-description">' . $content . '</div>';

	if ( bp_activity_user_can_edit( false, true ) ) {
		?>

		<a class="bp-add-media-activity-description <?php echo( ! empty( $content ) ? 'show-edit' : 'show-add' ); ?>" href="#">
			<span class="bb-icon-l bb-icon-edit"></span>
			<span class="add"><?php _e( 'Add a description', 'buddyboss' ); ?></span>
			<span class="edit"><?php _e( 'Edit', 'buddyboss' ); ?></span>
		</a>
		<div class="bp-edit-media-activity-description" style="display: none;">
			<div class="innerWrap">
				<textarea id="add-activity-description"
					  title="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>"
					  class="textInput"
					  name="caption_text"
					  placeholder="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>"
					  role="textbox"><?php echo $content; ?></textarea>
			</div>
			<div class="in-profile description-new-submit">
				<input type="hidden" id="bp-attachment-id" value="<?php echo $attachment_id; ?>">
				<input type="submit" id="bp-activity-description-new-submit" class="button small" name="description-new-submit" value="<?php esc_html_e( 'Done Editing', 'buddyboss' ); ?>">
				<input type="reset" id="bp-activity-description-new-reset" class="text-button small" value="<?php esc_html_e( 'Cancel', 'buddyboss' ); ?>">
			</div>
		</div>

		<?php
	}

	echo '</div>';
	if ( ! empty( $media_id ) ) {
		$media_privacy    = bb_media_user_can_access( $media_id, 'photo' );
		$can_download_btn = ( true === (bool) $media_privacy['can_download'] ) ? true : false;
		if ( $can_download_btn ) {
			$download_url = bp_media_download_link( $attachment_id, $media_id );
			if ( $download_url ) {
				?>
				<a class="download-media"
				   href="<?php echo esc_url( $download_url ); ?>">
					<?php _e( 'Download', 'buddyboss' ); ?>
				</a>
				<?php
			}
		}
	}
}

/**
 * Fetch and update the document description.
 *
 * @since BuddyBoss 1.3.5
 *
 * @param int $activity_id The current activity ID.
 */
function bp_nouveau_document_activity_description( $activity_id = 0 ) {
	if ( empty( $activity_id ) ) {
		$activity_id = bp_get_activity_id();
	}

	if ( empty( $activity_id ) ) {
		return;
	}

	$attachment_id = BP_Document::get_activity_attachment_id( $activity_id );
	$document_id   = BP_Document::get_activity_document_id( $activity_id );

	if ( empty( $attachment_id ) ) {
		return;
	}

	$content = get_post_field( 'post_content', $attachment_id );

	echo '<div class="activity-media-description">' .
		 '<div class="bp-media-activity-description">' . $content . '</div>';

	if ( bp_activity_user_can_edit( false, true ) ) {
		?>

		<a class="bp-add-media-activity-description <?php echo( ! empty( $content ) ? 'show-edit' : 'show-add' ); ?>"
		   href="#">
			   <span class="bb-icon-l bb-icon-edit"></span>
			<span class="add"><?php _e( 'Add a description', 'buddyboss' ); ?></span>
			<span class="edit"><?php _e( 'Edit', 'buddyboss' ); ?></span>
		</a>
		<div class="bp-edit-media-activity-description" style="display: none;">
			<div class="innerWrap">
						<textarea id="add-activity-description"
								  title="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>"
								  class="textInput"
								  name="caption_text"
								  placeholder="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>"
								  role="textbox"><?php echo $content; ?></textarea>
			</div>
			<div class="in-profile description-new-submit">
								<input type="hidden" id="bp-attachment-id" value="<?php echo $attachment_id; ?>">
				<input type="submit" id="bp-activity-description-new-submit" class="button small"
					   name="description-new-submit" value="<?php esc_html_e( 'Done Editing', 'buddyboss' ); ?>">
				<input type="reset" id="bp-activity-description-new-reset" class="text-button small"
					   value="<?php esc_html_e( 'Cancel', 'buddyboss' ); ?>">
			</div>
		</div>

		<?php
	}

	echo '</div>';
	if ( ! empty( $document_id ) ) {
		$document_privacy = bb_media_user_can_access( $document_id, 'document' );
		$can_download_btn = ( true === (bool) $document_privacy['can_download'] ) ? true : false;
		if ( $can_download_btn ) {
			$download_url = bp_document_download_link( $attachment_id, $document_id );
			if ( $download_url ) {
				?>
				<a class="download-document"
				   href="<?php echo esc_url( $download_url ); ?>">
					<?php _e( 'Download', 'buddyboss' ); ?>
				</a>
				<?php
			}
		}
	}
}

/**
 * Clear activity body content
 *
 * @since BuddyBoss 1.4.0
 *
 * @param BP_Activity_Activity $activity Activity object.
 *
 * @param integer              $content  Activity Content
 *
 * @return bool
 */
function bp_nouveau_clear_activity_content_body( $content, $activity ) {
	return false;
}

/**
 * Output the Activity timestamp into the bp-timestamp attribute.
 *
 * @since BuddyBoss 1.5.0
 */
function bp_nouveau_edit_activity_data() {
	echo bp_nouveau_get_edit_activity_data();
}

/**
 * Get the Activity edit data.
 *
 * @since BuddyBoss 1.5.0
 *
 * @return string The Activity edit data.
 */
function bp_nouveau_get_edit_activity_data() {
	return htmlentities( wp_json_encode( bp_activity_get_edit_data( bp_get_activity_id() ) ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
}

/**
 * Output the top action buttons inside an Activity Loop.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 */
function bb_nouveau_activity_entry_bubble_buttons( $args = array() ) {
	$output = join( ' ', bb_nouveau_get_activity_entry_bubble_buttons( $args ) );

	ob_start();

	/**
	 * Fires at the end of the activity entry top meta data area.
	 *
	 * @since BuddyBoss 1.7.2
	 */
	do_action( 'bp_activity_entry_top_meta' );

	$output .= ob_get_clean();

	$has_content = trim( $output, ' ' );
	if ( ! $has_content ) {
		return;
	}

	if ( ! $args ) {
		$args = array( 'container_classes' => array( 'bb-activity-more-options-wrap' ) );
	}

	$output = sprintf( '<span class="bb-activity-more-options-action" data-balloon-pos="up" data-balloon="%s"><i class="bb-icon-f bb-icon-ellipsis-h"></i></span><div class="bb-activity-more-options">%s</div>', esc_html__( 'More Options', 'buddyboss' ), $output );

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Get the action buttons inside an Activity Loop,
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 *
 * @since BuddyBoss 1.7.2
 */
function bb_nouveau_get_activity_entry_bubble_buttons( $args ) {
	$buttons = array();
	if ( ! isset( $GLOBALS['activities_template'] ) ) {
		return $buttons;
	}

	$activity_id    = bp_get_activity_id();
	$activity_type  = bp_get_activity_type();
	$parent_element = '';
	$button_element = 'a';

	if ( ! $activity_id ) {
		return $buttons;
	}

	/*
	 * If the container is set to 'ul' force the $parent_element to 'li',
	 * else use parent_element args if set.
	 *
	 * This will render li elements around anchors/buttons.
	 */
	if ( isset( $args['container'] ) && 'ul' === $args['container'] ) {
		$parent_element = 'li';
	} elseif ( ! empty( $args['parent_element'] ) ) {
		$parent_element = $args['parent_element'];
	}

	$parent_attr = ( ! empty( $args['parent_attr'] ) ) ? $args['parent_attr'] : array();

	/*
	 * If we have an arg value for $button_element passed through
	 * use it to default all the $buttons['button_element'] values
	 * otherwise default to 'a' (anchor)
	 * Or override & hardcode the 'element' string on $buttons array.
	 *
	 */
	if ( ! empty( $args['button_element'] ) ) {
		$button_element = $args['button_element'];
	}

	// Add activity edit button.
	if ( 'activity_comment' !== $activity_type && bp_activity_user_can_edit() && bp_is_activity_edit_enabled() ) {
		$buttons['activity_edit'] = array(
			'id'                => 'activity_edit',
			'position'          => 30,
			'component'         => 'activity',
			'parent_element'    => $parent_element,
			'parent_attr'       => $parent_attr,
			'must_be_logged_in' => true,
			'button_element'    => $button_element,
			'button_attr'       => array(
				'href'  => '#',
				'class' => 'button edit edit-activity bp-secondary-action bp-tooltip',
				'title' => __( 'Edit Activity', 'buddyboss' ),
			),
			'link_text'         => sprintf(
				'<span class="bp-screen-reader-text">%1$s</span><span class="edit-label">%2$s</span>',
				__( 'Edit Activity', 'buddyboss' ),
				__( 'Edit', 'buddyboss' )
			),
		);
	}

	if ( bp_is_active( 'moderation' ) ) {
		$buttons['activity_report'] = bp_activity_get_report_link(
			array(
				'position'       => 33,
				'parent_element' => $parent_element,
				'parent_attr'    => $parent_attr,
				'button_element' => $button_element,
			)
		);
	}

	if ( $activity_type !== 'activity_comment' && ! empty( $_REQUEST['action'] ) && ( 'video_get_activity' === $_REQUEST['action'] ) ) {
		$video_id       = BP_Video::get_activity_video_id( $activity_id );
		$video_group_id = bp_get_video_group_id();
		if ( ! empty( $video_id ) ) {
			$attachment_id = BP_Video::get_activity_attachment_id( $activity_id );
			if ( ! empty( $attachment_id ) ) {
				$video_privacy = bb_media_user_can_access( $video_id, 'video' );
				$can_edit      = true === (bool) $video_privacy['can_edit'];
				if ( $can_edit && ( bb_user_can_create_video() || $video_group_id > 0 ) ) {
					$parent_activity_id          = get_post_meta( $attachment_id, 'bp_video_parent_activity_id', true );
					$attachment_urls             = bb_video_get_attachments_symlinks( $attachment_id, $video_id );
					$buttons['change_thumbnail'] = array(
						'id'                => 'change_thumbnail',
						'component'         => 'video',
						'position'          => 10,
						'must_be_logged_in' => true,
						'parent_element'    => $parent_element,
						'parent_attr'       => $parent_attr,
						'button_element'    => $button_element,
						'link_text'         => sprintf(
							'<span class="bp-screen-reader-text">%1$s</span><span class="change-label">%2$s</span>',
							esc_html__( 'Change Thumbnail', 'buddyboss' ),
							esc_html__( 'Change Thumbnail', 'buddyboss' )
						),
						'button_attr'       => array(
							'data-action'              => 'video',
							'data-parent-activity-id'  => $parent_activity_id,
							'data-video-attachments'   => wp_json_encode( $attachment_urls ),
							'data-video-attachment-id' => $attachment_id,
							'data-video-id'            => $video_id,
							'class'                    => 'ac-video-thumbnail-edit'
						),
					);
				}
			}
		}
	}

	if ( bp_activity_user_can_delete() ) {
		$delete_args = array();

		/*
		 * As the delete link is filterable we need this workaround
		 * to try to intercept the edits the filter made and build
		 * a button out of it.
		 */
		if ( has_filter( 'bp_get_activity_delete_link' ) ) {
			preg_match( '/<a\s[^>]*>(.*)<\/a>/siU', bp_get_activity_delete_link(), $link );

			if ( ! empty( $link[0] ) && ! empty( $link[1] ) ) {
				$delete_args['link_text'] = $link[1];
				$subject                  = str_replace( $delete_args['link_text'], '', $link[0] );
			}

			preg_match_all( '/([\w\-]+)=([^"\'> ]+|([\'"]?)(?:[^\3]|\3+)+?\3)/', $subject, $attrs );

			if ( ! empty( $attrs[1] ) && ! empty( $attrs[2] ) ) {
				foreach ( $attrs[1] as $key_attr => $key_value ) {
					$delete_args[ 'link_' . $key_value ] = trim( $attrs[2][ $key_attr ], '"' );
				}
			}

			$delete_args = bp_parse_args(
				$delete_args,
				array(
					'link_text'   => '',
					'button_attr' => array(
						'link_id'         => '',
						'link_href'       => '',
						'link_class'      => '',
						'link_rel'        => 'nofollow',
						'data_bp_tooltip' => '',
					),
				)
			);
		}

		if ( empty( $delete_args['link_href'] ) ) {
			$delete_args = array(
				'button_element'  => $button_element,
				'link_id'         => '',
				'link_class'      => 'button item-button bp-secondary-action delete-activity confirm',
				'link_rel'        => 'nofollow',
				'data_bp_tooltip' => __( 'Delete', 'buddyboss' ),
				'link_text'       => __( 'Delete', 'buddyboss' ),
			);

			// If button element set add nonce link to data-attr attr.
			if ( 'button' === $button_element ) {
				$delete_args['data-attr'] = bp_get_activity_delete_url();
				$delete_args['link_href'] = '';
			} else {
				$delete_args['link_href'] = bp_get_activity_delete_url();
				$delete_args['data-attr'] = '';
			}
		}

		$buttons['activity_delete'] = array(
			'id'                => 'activity_delete',
			'component'         => 'activity',
			'parent_element'    => $parent_element,
			'parent_attr'       => $parent_attr,
			'must_be_logged_in' => true,
			'button_element'    => $button_element,
			'button_attr'       => array(
				'id'            => $delete_args['link_id'],
				'href'          => $delete_args['link_href'],
				'class'         => $delete_args['link_class'],
				'data-bp-nonce' => $delete_args['data-attr'],
			),
			'link_text'         => sprintf(
				'<span class="bp-screen-reader-text">%s</span><span class="delete-label">%s</span>',
				esc_html( $delete_args['data_bp_tooltip'] ),
				esc_html( $delete_args['data_bp_tooltip'] )
			),
		);
	}

	/**
	 * Filter to add your buttons, use the position argument to choose where to insert it.
	 *
	 * @since BuddyBoss 1.7.2
	 *
	 * @param array $buttons     The list of buttons.
	 * @param int   $activity_id The current activity ID.
	 */
	$buttons_group = apply_filters( 'bb_nouveau_get_activity_entry_bubble_buttons', $buttons, $activity_id );

	if ( ! $buttons_group ) {
		return $buttons;
	}

	// It's the first entry of the loop, so build the Group and sort it.
	if ( ! isset( bp_nouveau()->activity->entry_buttons ) || ! is_a( bp_nouveau()->activity->entry_buttons, 'BP_Buttons_Group' ) ) {
		$sort                                 = true;
		bp_nouveau()->activity->entry_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first entry, the order is set, we simply need to update the Buttons Group.
	} else {
		$sort = false;
		bp_nouveau()->activity->entry_buttons->update( $buttons_group );
	}

	$return = bp_nouveau()->activity->entry_buttons->get( $sort );

	if ( ! $return ) {
		return array();
	}

	// Remove the Delete button if the user can't delete.
	if ( ! bp_activity_user_can_delete() ) {
		unset( $return['activity_delete'] );
	}

	/**
	 * Leave a chance to adjust the $return
	 *
	 * @since BuddyBoss 1.7.2
	 *
	 * @param array $return      The list of buttons ordered.
	 * @param int   $activity_id The current activity ID.
	 */
	do_action_ref_array( 'bb_nouveau_return_activity_entry_bubble_buttons', array( &$return, $activity_id ) );

	return $return;
}

/**
 * Output the action buttons for the activity comments
 *
 * @since BuddyBoss 1.7.3
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 */
function bb_nouveau_activity_comment_bubble_buttons( $args = array() ) {
	$output = join( ' ', bb_nouveau_get_activity_comment_bubble_buttons( $args ) );

	ob_start();
	/**
	 * Fires after the default comment action options display.
	 *
	 * @since BuddyBoss 1.7.3
	 */
	do_action( 'bp_activity_comment_bubble_options' );

	$output      .= ob_get_clean();
	$has_content = trim( $output, ' ' );

	if ( ! $has_content ) {
		return;
	}

	if ( ! $args ) {
		$args = array( 'container_classes' => array( 'bb-activity-more-options-wrap' ) );
	}

	$output = sprintf( '<span class="bb-activity-more-options-action" data-balloon-pos="up" data-balloon="%s"><i class="bb-icon-f bb-icon-ellipsis-h"></i></span><div class="bb-activity-more-options">%s</div>', esc_html__( 'More Options', 'buddyboss' ), $output );

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Get the top action buttons for the activity comments
 *
 * @since BuddyBoss 1.7.3
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 *
 * @return array
 */
function bb_nouveau_get_activity_comment_bubble_buttons( $args ) {

	$buttons = array();

	if ( ! isset( $GLOBALS['activities_template'] ) ) {
		return $buttons;
	}

	$activity_comment_id = bp_get_activity_comment_id();
	$activity_id         = bp_get_activity_id();

	if ( ! $activity_comment_id || ! $activity_id ) {
		return $buttons;
	}

	/*
	 * If the 'container' is set to 'ul'
	 * set a var $parent_element to li
	 * otherwise simply pass any value found in args
	 * or set var false.
	 */
	if ( ! empty( $args['container'] ) && 'ul' === $args['container'] ) {
		$parent_element = 'li';
	} elseif ( ! empty( $args['parent_element'] ) ) {
		$parent_element = $args['parent_element'];
	} else {
		$parent_element = false;
	}

	$parent_attr = ( ! empty( $args['parent_attr'] ) ) ? $args['parent_attr'] : array();

	/*
	 * If we have an arg value for $button_element passed through
	 * use it to default all the $buttons['button_element'] values
	 * otherwise default to 'a' (anchor).
	 */
	if ( ! empty( $args['button_element'] ) ) {
		$button_element = $args['button_element'];
	} else {
		$button_element = 'a';
	}

	$buttons = array();

	if ( bp_is_active( 'moderation' ) ) {
		$buttons['activity_comment_report'] = bp_activity_comment_get_report_link(
			array(
				'parent_element' => $parent_element,
				'parent_attr'    => $parent_attr,
				'button_element' => $button_element,
			)
		);
	}

	// If button element set add nonce link to data-attr attr
	if ( 'button' === $button_element ) {
		$buttons['activity_comment_reply']['button_attr']['data-bp-act-reply-nonce']         = sprintf( '#acomment-%s', $activity_comment_id );
	} else {
		$buttons['activity_comment_reply']['button_attr']['href']  = sprintf( '#acomment-%s', $activity_comment_id );
	}

	if ( bp_activity_user_can_delete() ) {

		$buttons['activity_comment_delete'] = array(
			'id'                => 'activity_comment_delete',
			'component'         => 'activity',
			'must_be_logged_in' => true,
			'parent_element'    => $parent_element,
			'parent_attr'       => $parent_attr,
			'button_element'    => $button_element,
			'link_text'         => esc_html__( 'Delete', 'buddyboss' ),
			'button_attr'       => array(
				'class' => 'delete acomment-delete confirm bp-secondary-action',
				'rel'   => 'nofollow',
			),
		);

		// If button element set add nonce link to data-attr attr.
		if ( 'button' === $button_element ) {
			$buttons['activity_comment_delete']['button_attr']['data-bp-act-reply-delete-nonce'] = bp_get_activity_comment_delete_link();
		} else {
			$buttons['activity_comment_delete']['button_attr']['href'] = bp_get_activity_comment_delete_link();
		}
	}
	/**
	 * Filter to add your buttons, use the position argument to choose where to insert it.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param array $buttons             The list of buttons.
	 * @param int   $activity_comment_id The current activity comment ID.
	 * @param int   $activity_id         The current activity ID.
	 */
	$buttons_group = apply_filters( 'bb_nouveau_get_activity_comment_bubble_buttons', $buttons, $activity_comment_id, $activity_id );

	if ( ! $buttons_group ) {
		return $buttons;
	}

	// It's the first comment of the loop, so build the Group and sort it.
	if ( ! isset( bp_nouveau()->activity->comment_buttons ) || ! is_a( bp_nouveau()->activity->comment_buttons, 'BP_Buttons_Group' ) ) {
		$sort                                   = true;
		bp_nouveau()->activity->comment_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first comment, the order is set, we simply need to update the Buttons Group.
	} else {
		$sort = false;
		bp_nouveau()->activity->comment_buttons->update( $buttons_group );
	}

	$return = bp_nouveau()->activity->comment_buttons->get( $sort );

	if ( ! $return ) {
		return array();
	}



	/**
	 * Leave a chance to adjust the $return
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param array $return              The list of buttons ordered.
	 * @param int   $activity_comment_id The current activity comment ID.
	 * @param int   $activity_id         The current activity ID.
	 */
	do_action_ref_array(
		'bb_nouveau_return_activity_comment_bubble_buttons',
		array(
			&$return,
			$activity_comment_id,
			$activity_id,
		)
	);

	return $return;
}
