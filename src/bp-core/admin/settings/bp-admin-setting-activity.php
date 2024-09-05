<?php
/**
 * Add admin Activity settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main activity settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Activity extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Activity', 'buddyboss' );
		$this->tab_name  = 'bp-activity';
		$this->tab_order = 40;
	}

	public function is_active() {
		return bp_is_active( 'activity' );
	}

	public function settings_save() {

		// Get old values for cpt and check if it disabled then keep it and later will save it.
		$cpt_types          = apply_filters( 'bb_activity_global_setting_comment_cpt', array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-assignment', 'groups', 'lesson' ) );
		$filtered_cpt_types = array_values(
			array_filter(
				array_map(
					function ( $post_type ) {
						if ( ! bb_activity_is_enabled_cpt_global_comment( $post_type ) ) {
							return $post_type;
						}
					},
					$cpt_types
				)
			)
		);

		$old_cpt_comments_values = array();
		foreach ( $filtered_cpt_types as $cpt ) {
			$option_name                             = bb_post_type_feed_comment_option_name( $cpt );
			$old_cpt_comments_values[ $option_name ] = bp_get_option( $option_name, false );
		}

		parent::settings_save();

		bb_cpt_feed_enabled_disabled();

		// Do not override the setting which previously saved.
		if ( ! empty( $old_cpt_comments_values ) ) {
			foreach ( $old_cpt_comments_values as $cpt_comment_key => $cpt_comment_val ) {
				bp_update_option( $cpt_comment_key, $cpt_comment_val );
			}
		}

	}

	public function register_fields() {

		$this->add_section( 'bp_activity', __( 'Activity Settings', 'buddyboss' ), '', 'bp_activity_settings_tutorial' );

		// Allow Activity edit setting.
		$this->add_field( '_bp_enable_activity_edit', __( 'Edit Activity', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_edit', 'intval' );
		$this->add_field( '_bp_activity_edit_time', __( 'Edit Activity Time Limit', 'buddyboss' ), '__return_true', 'intval', array(
			'class' => 'hidden',
		) );

		// Allow subscriptions setting.
		$this->add_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddyboss' ), 'bp_admin_setting_callback_heartbeat', 'intval' );

		// Close comments.
		$this->add_field( '_bb_enable_close_activity_comments', __( 'Close comments', 'buddyboss' ), array( $this, 'bb_admin_setting_callback_enable_close_activity_comments' ), 'intval' );

		// Allow scopes/tabs.
		$this->add_field( '_bp_enable_activity_tabs', __( 'Activity tabs', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_tabs', 'intval' );

		// Allow scopes/tabs.
		$this->add_field( '_bb_enable_activity_pinned_posts', __( 'Pinned Post', 'buddyboss' ), 'bb_admin_setting_callback_enable_activity_pinned_posts', 'intval' );

		// Allow Poll.
		$polls_pro_class     = bb_get_pro_fields_class( 'polls' );
		$polls_notice        = bb_get_pro_label_notice( 'polls' );
		$poll_args           = array();
		$poll_args['class']  = esc_attr( $polls_pro_class );
		$poll_args['notice'] = $polls_notice;
		$this->add_field( '_bb_enable_activity_post_polls', __( 'Polls', 'buddyboss' ) . $polls_notice, array( $this, 'bb_admin_setting_callback_enable_activity_post_polls' ), 'intval', $poll_args );

		$pro_class     = bb_get_pro_fields_class( 'schedule_posts' );
		$args          = array();
		$args['class'] = esc_attr( $pro_class );

		$schedule_posts_pro_notice = bb_get_pro_label_notice( 'schedule_posts' );
		$schedule_posts_field_name = empty( $schedule_posts_pro_notice ) ? '_bb_enable_activity_schedule_posts' : '';
		$this->add_field( $schedule_posts_field_name, __( 'Schedule posts', 'buddyboss' ) . $schedule_posts_pro_notice, array( $this, 'bb_admin_setting_callback_enable_activity_schedule_posts' ), 'intval', $args );

		// Allow follow.
		$this->add_field( '_bp_enable_activity_follow', __( 'Follow', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_follow', 'intval' );

		// Allow link preview.
		$this->add_field( '_bp_enable_activity_link_preview', __( 'Link Previews', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_link_preview', 'intval' );

		// Relevant Activity Feeds.
		$this->add_field( '_bp_enable_relevant_feed', __( 'Relevant Activity', 'buddyboss' ), 'bp_admin_setting_callback_enable_relevant_feed', 'intval' );

		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			// $this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}

		$this->add_section(
			'bb_activity_comments',
			__( 'Activity Comments', 'buddyboss' ),
			'',
			array( $this, 'bb_admin_activity_comments_settings_tutorial' ),
			sprintf(
				wp_kses_post(
					__( 'WordPress post and custom post types will inherit from your WordPress %s settings.', 'buddyboss' )
				),
				'<a href="options-discussion.php" target="_blank" >' . esc_html__( 'Discussion', 'buddyboss' ) . '</a>'
			)
		);

		$this->add_field( '_bb_enable_activity_comments', __( 'Enable comments', 'buddyboss' ), array( $this, 'bb_admin_setting_callback_enable_activity_comments' ), 'intval' );

		// Allow Activity comment threading setting.
		$this->add_field( '_bb_enable_activity_comment_threading', __( 'Comment threading', 'buddyboss' ), array( $this, 'bb_admin_setting_callback_comment_threading' ), 'intval' );
		$this->add_field( '_bb_activity_comment_threading_depth', __( 'Comment threading depth', 'buddyboss' ), '__return_true', 'intval', array( 'class' => 'hidden' ) );

		$this->add_field( '_bb_activity_comment_visibility', __( 'Comment visibility', 'buddyboss' ), array( $this, 'bb_admin_setting_callback_comment_visibility' ), 'intval' );
		$this->add_field( '_bb_activity_comment_loading', __( 'Comments loading', 'buddyboss' ), array( $this, 'bb_admin_setting_callback_comment_loading' ), 'intval' );

		// Allow Activity comment edit setting.
		$this->add_field( '_bb_enable_activity_comment_edit', __( 'Edit Activity comments', 'buddyboss' ), 'bb_admin_setting_callback_enable_activity_comment_edit', 'intval' );
		$this->add_field( '_bb_activity_comment_edit_time', __( 'Edit Comment Time Limit', 'buddyboss' ), '__return_true', 'intval', array(
			'class' => 'hidden',
		) );

		$this->add_section( 'bp_custom_post_type', __( 'Posts in Activity Feeds', 'buddyboss' ) );

		// create field for default Platform activity feed.
		$get_default_platform_activity_types = bp_platform_default_activity_types();
		$is_first                            = true;
		foreach ( $get_default_platform_activity_types as $type ) {
			$name          = $type['activity_name'];
			$class         = ( true === $is_first ) ? 'child-no-padding-first' : 'child-no-padding';
			$type['class'] = $class;
			$this->add_field( "bp-feed-platform-$name", ( true === $is_first ) ? __( 'BuddyBoss Platform', 'buddyboss' ) : '', 'bp_feed_settings_callback_platform', 'intval', $type );
			$is_first = false;
		}

		// flag for adding conditional CSS class.
		$count       = 0;
		$description = 0;

		foreach ( bb_feed_post_types() as $key => $post_type ) {

			$fields = array();

			$fields['args'] = array(
				'post_type'   => $post_type,
				'description' => false,
			);

			$post_type_option_name = bb_post_type_feed_option_name( $post_type );
			$comment_option_name   = bb_post_type_feed_comment_option_name( $post_type );

			if ( 'post' === $post_type ) {
				$fields['args']['class'] = 'child-no-padding-first';
				// create field for each of custom post type.
				$this->add_field( $post_type_option_name, __( 'WordPress', 'buddyboss' ), 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );

				$fields['args']['class'] = 'child-no-padding bp-display-none';
				// Activity commenting on post and comments.
				$this->add_field( $comment_option_name, '&#65279;', 'bb_feed_settings_callback_post_type_comments', 'intval', $fields['args'] );

			} else {
				if ( 0 === $description ) {
					$fields['args']['description'] = true;
					$description                   = 1;
				}
				if ( 0 === $count ) {
					$fields['args']['class'] = 'child-no-padding-first';
					// create field for each of custom post type.
					$this->add_field( $post_type_option_name, __( 'Custom Post Types', 'buddyboss' ), 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );

					$fields['args']['class'] = 'child-no-padding bp-display-none child-custom-post-type';
					$this->add_field( $comment_option_name, '', 'bb_feed_settings_callback_post_type_comments', 'intval', $fields['args'] );
				} else {

					$fields['args']['class'] = 'child-no-padding';
					// create field for each of custom post type.
					$this->add_field( $post_type_option_name, '&#65279;', 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );

					$fields['args']['class'] = 'child-no-padding bp-display-none child-custom-post-type';
					$this->add_field( $comment_option_name, '', 'bb_feed_settings_callback_post_type_comments', 'intval', $fields['args'] );
				}
				$count ++;
			}
		}

		/**
		 * Fires to register Activity tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Activity.
		 */
		do_action( 'bp_admin_setting_activity_register_fields', $this );
	}

	/**
	 * Enable close activity comments settings.
	 *
	 * @since BuddyBoss 2.5.80
	 */
	public function bb_admin_setting_callback_enable_close_activity_comments() {
		?>

		<input id="_bb_enable_close_activity_comments" name="_bb_enable_close_activity_comments" type="checkbox" value="1" <?php checked( bb_is_close_activity_comments_enabled( true ) ); ?> />
		<label for="_bb_enable_close_activity_comments"><?php esc_html_e( 'Allow your users to stop users commenting on their posts', 'buddyboss' ); ?></label>

		<?php
	}

	/**
	 * Enable activity comments.
	 *
	 * @since BuddyBoss 2.5.80
	 */
	public function bb_admin_setting_callback_enable_activity_comments() {
		?>
		<input id="_bb_enable_activity_comments" name="_bb_enable_activity_comments" type="checkbox" value="1" <?php checked( bb_is_activity_comments_enabled() ); ?> />
		<label for="_bb_enable_activity_comments"><?php esc_html_e( 'Allow members to comment on activity posts', 'buddyboss' ); ?></label>
		<p class="description"><?php esc_html_e( 'Comments on an individual activity post can be closed or disabled all together by site admins.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Enable activity comment threading.
	 *
	 * @since BuddyBoss 2.5.80
	 */
	public function bb_admin_setting_callback_comment_threading() {
		$options = array( 1, 2, 3, 4 );
		$depth   = bb_get_activity_comment_threading_depth();
		ob_start();
		?>
		<select name="_bb_activity_comment_threading_depth">
			<?php
			foreach ( $options as $depth_level ) {
				echo '<option value="' . esc_attr( $depth_level ) . '" ' . selected( $depth, $depth_level, false ) . '>' . esc_html( $depth_level ) . '</option>';
			}
			?>
		</select>
		<?php
		$select = ob_get_clean();
		?>

		<input id="_bb_enable_activity_comment_threading" name="_bb_enable_activity_comment_threading" type="checkbox" value="1" <?php checked( bb_is_activity_comment_threading_enabled() ); ?> />
		<label for="_bb_enable_activity_comment_threading">
			<?php printf( esc_html__( 'Organize replies into threads %s levels deep', 'buddyboss' ), $select ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Replies to an activity comment will be shown in separate threads, except when replying to a comment at the deepest level. This only applies to platform not app.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Activity comment visibility.
	 *
	 * @since BuddyBoss 2.5.80
	 */
	public function bb_admin_setting_callback_comment_visibility() {
		$options = array(
			array(
				'label' => esc_attr__( 'None', 'buddyboss' ),
				'value' => 0,
			),
			array(
				'label' => 1,
				'value' => 1,
			),
			array(
				'label' => 2,
				'value' => 2,
			),
			array(
				'label' => 3,
				'value' => 3,
			),
			array(
				'label' => 4,
				'value' => 4,
			),
			array(
				'label' => 5,
				'value' => 5,
			),
		);
		$setting = bb_get_activity_comment_visibility();
		ob_start();
		?>
		<select id="_bb_activity_comment_visibility" name="_bb_activity_comment_visibility">
			<?php
			foreach ( $options as $option ) {
				$value         = isset( $option['value'] ) ? $option['value'] : 0;
				$setting_level = isset( $option['label'] ) ? $option['label'] : 0;
				echo '<option value="' . esc_attr( $value ) . '" ' . selected( $setting, $value, false ) . '>' . esc_html( $setting_level ) . '</option>';
			}
			?>
		</select>
		<?php
		$select = ob_get_clean();
		?>

		<label for="_bb_activity_comment_visibility">
			<?php printf( esc_html__( 'Display a maximum %s comments per post in activity feeds', 'buddyboss' ), $select ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Load more using the "View more comments" links. Higher comments counts will increase the time it takes members to scroll through activity feeds. This only applies to platform not app.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Activity comment loading.
	 *
	 * @since BuddyBoss 2.5.80
	 */
	public function bb_admin_setting_callback_comment_loading() {
		$options = apply_filters( 'bb_activity_comment_loading_options', array( 5, 10, 15, 20, 25, 30 ) );
		$setting = bb_get_activity_comment_loading();
		ob_start();
		?>
		<select id="_bb_activity_comment_loading" name="_bb_activity_comment_loading">
			<?php
			foreach ( $options as $setting_level ) {
				echo '<option value="' . esc_attr( $setting_level ) . '" ' . selected( $setting, $setting_level, false ) . '>' . esc_html( $setting_level ) . '</option>';
			}
			?>
		</select>
		<?php
		$select = ob_get_clean();
		?>

		<label for="_bb_activity_comment_loading">
			<?php printf( esc_html__( 'Load %s additional comments on each request', 'buddyboss' ), $select ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Increasing the number of comments retrieved in each request may negatively impact site performance.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Link to Activity Comments tutorial
	 *
	 * @since BuddyBoss 2.5.80
	 */
	public function bb_admin_activity_comments_settings_tutorial() {
		?>
		<p>
			<a class="button" href="
			<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 127431,
						),
						'admin.php'
					)
				)
			);
			?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Allow schedule activity posts.
	 *
	 * @since BuddyBoss 2.6.10
	 */
	public function bb_admin_setting_callback_enable_activity_schedule_posts() {
		$val    = function_exists( 'bb_is_enabled_activity_schedule_posts_filter' ) ? bb_is_enabled_activity_schedule_posts_filter() : false;
		$notice = bb_get_pro_label_notice( 'schedule_posts' );
		?>
			<input id="bb_enable_activity_schedule_posts" name="<?php echo empty( $notice ) ? '_bb_enable_activity_schedule_posts' : ''; ?>" type="checkbox" value="1" <?php echo empty( $notice ) ? checked( $val, true, false ) : ''; ?> />
			<label for="bb_enable_activity_schedule_posts"><?php esc_html_e( 'Allow group owners and moderators to schedule their posts', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Allow activity poll.
	 *
	 * @since BuddyBoss 2.6.90
	 */
	public function bb_admin_setting_callback_enable_activity_post_polls( $args ) {
		$val    = function_exists( 'bb_is_enabled_activity_post_polls' ) ? bb_is_enabled_activity_post_polls( false ) : false;
		$notice = ! empty( $args['notice'] ) ? $args['notice'] : '';
		?>
		<input id="bb_enable_activity_post_polls" name="<?php echo empty( $notice ) ? '_bb_enable_activity_post_polls' : ''; ?>" type="checkbox" value="1" <?php echo empty( $notice ) ? checked( $val, true, false ) : ''; ?> />
		<label for="bb_enable_activity_post_polls"><?php esc_html_e( 'Allow group owners and moderators to post polls', 'buddyboss' ); ?></label>
		<?php
	}
}

return new BP_Admin_Setting_Activity();
