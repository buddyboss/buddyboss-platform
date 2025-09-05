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

		// Pin posts.
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

		// Activity search.
		$this->add_field( 'bb_enable_activity_search', __( 'Activity search', 'buddyboss' ), 'bb_admin_setting_callback_enable_activity_search' );

		// Activity filters.
		$this->add_field( 'bb_activity_filter_options', __( 'Activity feed filters', 'buddyboss' ), 'bb_admin_setting_callback_activity_filters' );

		// Timeline filters.
		$this->add_field( 'bb_activity_timeline_filter_options', __( 'Profile timeline filters', 'buddyboss' ), 'bb_admin_setting_callback_activity_timeline_filters' );

		// Activity sorting.
		$this->add_field( 'bb_activity_sorting_options', __( 'Activity sorting', 'buddyboss' ), 'bb_admin_setting_callback_activity_sorting' );

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

		// Activity Topics.
		$this->add_section(
			'bb_activity_topics',
			__( 'Activity Topics', 'buddyboss' ),
			'',
			array( $this, 'bb_admin_activity_topics_settings_tutorial' )
		);

		$this->add_field( 'bb_enable_activity_topics', __( 'Enable Topics', 'buddyboss' ), array( $this, 'bb_admin_setting_callback_enable_activity_topics' ), 'intval' );

		$this->add_field(
			'bb_activity_topic_required',
			__( 'Topic Required', 'buddyboss' ),
			array( $this, 'bb_admin_setting_callback_activity_topic_required' ),
			'intval',
			array(
				'class' => 'bb_enable_activity_topics_required ' . ( true === bb_is_enabled_activity_topics() ? '' : 'bp-hide' ),
			)
		);

		$this->add_field(
			'bb_activity_topics',
			__( 'Activity Topic', 'buddyboss' ),
			array( $this, 'bb_admin_setting_callback_activity_topics' ),
			'intval',
			array(
				'class' => 'bb_enable_activity_topics_required ' . ( true === bb_is_enabled_activity_topics() ? '' : 'bp-hide' ),
			)
		);

		if ( bp_is_active( 'groups' ) ) {
			// Group Activity Topics.
			$group_activity_topics_pro_class      = bb_get_pro_fields_class( 'group_activity_topics' );
			$group_activity_topics_notice         = bb_get_pro_label_notice( 'group_activity_topics' );
			$group_activity_topics_args           = array();
			$group_activity_topics_args['class']  = esc_attr( $group_activity_topics_pro_class ) . ' bb_enable_activity_topics_required' . ( true === bb_is_enabled_activity_topics() ? '' : ' bp-hide' );
			$group_activity_topics_args['notice'] = $group_activity_topics_notice;
			$this->add_field(
				'bb-enable-group-activity-topics',
				__( 'Group Topics', 'buddyboss' ) . $group_activity_topics_notice,
				array(
					$this,
					'bb_admin_setting_callback_enable_group_activity_topics',
				),
				'intval',
				$group_activity_topics_args
			);
		}

		/**
		 * Fires to register Activity topic settings fields.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param Object $this BP_Admin_Setting_Activity.
		 */
		do_action( 'bb_admin_setting_activity_topic_register_fields', $this );

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
			<a class="button" target="_blank" href="
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
	 * @param array $args Arguments.
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

	/**
	 * Enable activity topics.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_admin_setting_callback_enable_activity_topics() {
		?>
		<input id="bb_enable_activity_topics" name="bb_enable_activity_topics" type="checkbox" value="1" <?php checked( bb_is_enabled_activity_topics() ); ?> />
		<label for="bb_enable_activity_topics"><?php esc_html_e( 'Enable topics in activity feed', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Enable activity topic required.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_admin_setting_callback_activity_topic_required() {
		?>
		<input id="bb_activity_topic_required" name="bb_activity_topic_required" type="checkbox" value="1" <?php checked( bb_is_activity_topic_required() ); ?> />
		<label for="bb_activity_topic_required"><?php esc_html_e( 'Require users to select a topic before posting in activity feed.', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Activity topics.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_admin_setting_callback_activity_topics() {
		$topics               = bb_topics_manager_instance()->bb_get_topics(
			array(
				'item_type' => 'activity',
				'item_id'   => 0,
			)
		);
		$topics_limit_reached = bb_topics_manager_instance()->bb_topics_limit_reached(
			array(
				'item_type' => 'activity',
				'item_id'   => 0,
			)
		);
		$topics               = ! empty( $topics['topics'] ) ? $topics['topics'] : array();
		$total_topics         = is_array( $topics ) ? count( $topics ) : 0;
		?>
		<div class="bb-activity-topics-wrapper <?php echo $total_topics > 0 ? esc_attr( 'bb-has-topics' ) : ''; ?>">
			<div class="bb-activity-topics-content">
				<div class="bb-activity-topics-list">
					<?php
					if ( ! empty( $topics ) ) {
						foreach ( $topics as $topic ) {
							if ( ! is_object( $topic ) ) {
								continue;
							}
							$topic_attr = array(
								'topic_id'  => $topic->topic_id,
								'item_id'   => ! empty( $topic->item_id ) ? $topic->item_id : 0,
								'item_type' => ! empty( $topic->item_type ) ? $topic->item_type : 'activity',
							);
							?>
							<div class="bb-activity-topic-item" data-topic-id="<?php echo esc_attr( $topic->topic_id ); ?>">
								<div class="bb-topic-left">
									<span class="bb-topic-drag">
										<i class="bb-icon-grip-v"></i>
									</span>
									<span class="bb-topic-title"><?php echo esc_html( $topic->name ); ?></span>
								</div>
								<div class="bb-topic-right">
									<span class="bb-topic-access">
										<?php
										$permission_type = bb_activity_topics_manager_instance()->bb_activity_topic_permission_type( $topic->permission_type );
										if ( ! empty( $permission_type ) ) {
											$permission_type_value = current( $permission_type );
											echo esc_html( $permission_type_value );
										}
										?>
									</span>
									<div class="bb-topic-actions-wrapper">
										<span class="bb-topic-actions">
											<a href="#" class="bb-topic-actions_button" aria-label="<?php esc_attr_e( 'Actions', 'buddyboss' ); ?>">
												<i class="bb-icon-ellipsis-h"></i>
											</a>
										</span>
										<div class="bb-topic-more-dropdown">
											<a href="#" class="button edit bb-edit-topic bp-secondary-action bp-tooltip" title="<?php esc_html_e( 'Edit', 'buddyboss' ); ?>" data-topic-attr="<?php echo esc_attr( wp_json_encode( array_merge( $topic_attr, array( 'nonce' => wp_create_nonce( 'bb_edit_topic' ) ) ) ) ); ?>">
												<span class="bp-screen-reader-text"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></span>
												<span class="edit-label"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></span>
											</a>
											<a href="#" class="button delete bb-delete-topic bp-secondary-action bp-tooltip" title="<?php esc_html_e( 'Delete', 'buddyboss' ); ?>" data-topic-attr="<?php echo esc_attr( wp_json_encode( array_merge( $topic_attr, array( 'nonce' => wp_create_nonce( 'bb_delete_topic' ) ) ) ) ); ?>">
												<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></span>
												<span class="delete-label"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></span>
											</a>
										</div>
									</div>
								</div>
								<input disabled="" id="bb_activity_topics" name="bb_activity_topic_options[<?php echo esc_attr( $topic->slug ); ?>]" type="hidden" value="bb_activity_topic_options[<?php echo esc_attr( $topic->slug ); ?>]">
							</div>
							<?php
						}
					}
					?>
				</div>
				<?php
				$button_class = $topics_limit_reached ? 'bp-hide' : '';
				?>
				<button type="button" class="button button-secondary bb-add-topic <?php echo esc_attr( $button_class ); ?>">
					<i class="bb-icon-plus"></i>
					<?php esc_html_e( 'Add New Topic', 'buddyboss' ); ?>
				</button>
			</div>
			<p class="description bb-topic-limit-not-reached" <?php echo 0 === (int) $total_topics || $topics_limit_reached ? 'style="display: none;"' : ''; ?>><?php esc_html_e( 'You can add up to a maximum of 20 topics', 'buddyboss' ); ?></p>
			<p class="description bb-topic-limit-reached" <?php echo ! $topics_limit_reached ? 'style="display: none;"' : ''; ?>><?php esc_html_e( 'You have reached the maximum topic limit', 'buddyboss' ); ?></p>
		</div>
		<div id="bb-hello-backdrop" class="bb-hello-backdrop-activity-topic bb-modal-backdrop" style="display: none;"></div>
		<div id="bb-hello-container" class="bb-hello-activity-topic bb-modal-panel bb-modal-panel--activity-topic" role="dialog" aria-labelledby="bb-hello-activity-topic" style="display: none;">
			<div class="bb-hello-header">
				<div class="bb-hello-title">
					<h2 id="bb-hello-title" tabindex="-1">
						<?php esc_html_e( 'Create Topic', 'buddyboss' ); ?>
					</h2>
				</div>
				<div class="bb-hello-close">
					<button type="button" class="close-modal button" aria-label="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<div class="bb-hello-content">
				<div class="form-fields">
					<div class="form-field">
						<div class="field-label">
							<label for="bb_topic_name"><?php esc_html_e( 'Topic Name', 'buddyboss' ); ?></label>
						</div>
						<div class="field-input">
							<input type="text" id="bb_topic_name" name="bb_topic_name" />
						</div>
					</div>
					<div class="form-field">
						<div class="field-label">
							<label for="bb_permission_type"><?php esc_html_e( 'Who can post?', 'buddyboss' ); ?></label>
						</div>
						<div class="field-input">
							<?php
							$permission_type = bb_activity_topics_manager_instance()->bb_activity_topic_permission_type();
							if ( ! empty( $permission_type ) ) {
								foreach ( $permission_type as $key => $value ) {
									?>
									<div class="bb-topic-who-can-post-option">
										<input type="radio" id="bb_permission_type_<?php echo esc_attr( $key ); ?>" name="bb_permission_type" value="<?php echo esc_attr( $key ); ?>" <?php checked( 'anyone' === $key, true ); ?> />
										<label for="bb_permission_type_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></label>
									</div>
									<?php
								}
							}
							?>
						</div>
					</div>
				</div>
				<div class="bb-popup-buttons">
					<span id="bb_topic_cancel" class="button" tabindex="0">
						<?php esc_html_e( 'Cancel', 'buddyboss' ); ?>
					</span>
					<input type="hidden" id="bb_topic_id" name="bb_topic_id" value="0">
					<input type="hidden" id="bb_item_id" name="bb_item_id" value="0">
					<input type="hidden" id="bb_item_type" name="bb_item_type" value="activity">
					<input type="hidden" id="bb_action_from" name="bb_action_from" value="admin">
					<input type="hidden" id="bb_topic_nonce" name="bb_topic_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bb_add_topic' ) ); ?>">
					<button type="button" id="bb_topic_submit" class="button button-primary" disabled="disabled">
						<?php esc_html_e( 'Confirm', 'buddyboss' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Migrate Topic Modal -->
		<div id="bb-hello-topic-migrate-backdrop" class="bb-hello-backdrop-activity-topic-migrate bb-modal-backdrop" style="display: none;"></div>
		<div id="bb-hello-topic-migrate-container" class="bb-hello-activity-topic-migrate bb-modal-panel bb-modal-panel--activity-topic-migrate" role="dialog" aria-labelledby="bb-hello-activity-topic-migrate" style="display: none;">
			<div class="bb-hello-header">
				<div class="bb-hello-title">
					<h2 id="bb-hello-title" tabindex="-1">
						<?php esc_html_e( 'Deleting', 'buddyboss' ); ?>
					</h2>
				</div>
				<div class="bb-hello-close">
					<button type="button" class="close-modal button" aria-label="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<div class="bb-hello-content">
				<p class="bb-hello-content-description">
					<?php esc_html_e( 'Would you like to move all previously tagged posts into another topic?', 'buddyboss' ); ?>
				</p>
				<div class="bb-existing-topic-list" id="bb_existing_topic_list">
					<div class="form-fields">
						<div class="form-field">
							<div class="field-label">
								<input type="radio" name="bb_migrate_existing_topic" id="bb_migrate_existing_topic" value="migrate" checked>
								<label for="bb_migrate_existing_topic"><?php esc_html_e( 'Yes, move posts to another topic', 'buddyboss' ); ?></label>
							</div>
							<div class="field-input">
								<select name="bb_existing_topic_id" id="bb_existing_topic_id">
									<option value="0"><?php esc_html_e( 'Select topic', 'buddyboss' ); ?></option>
								</select>
							</div>
						</div>
						<div class="form-field">
							<div class="field-label">
								<input type="radio" name="bb_migrate_existing_topic" id="bb_migrate_uncategorized_topic" value="delete">
								<label for="bb_migrate_uncategorized_topic"><?php esc_html_e( 'No, delete the topic', 'buddyboss' ); ?></label>
							</div>
						</div>
					</div>
				</div>
				<div class="bb-popup-buttons">
					<span id="bb_topic_cancel" class="button" tabindex="0">
						<?php esc_html_e( 'Cancel', 'buddyboss' ); ?>
					</span>
					<input type="hidden" id="bb_topic_id" name="bb_topic_id" value="0">
					<input type="hidden" id="bb_item_id" name="bb_item_id" value="0">
					<input type="hidden" id="bb_item_type" name="bb_item_type" value="activity">
					<input type="hidden" id="bb_topic_nonce" name="bb_topic_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bb_migrate_topic' ) ); ?>">
					<button type="button" id="bb_topic_migrate" class="button button-primary" disabled="disabled">
						<?php esc_html_e( 'Confirm', 'buddyboss' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enable group activity topics.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_admin_setting_callback_enable_group_activity_topics() {
		$val    = function_exists( 'bb_is_enabled_group_activity_topics' ) && bb_is_enabled_group_activity_topics();
		$notice = ! empty( $args['notice'] ) ? $args['notice'] : '';
		?>
		<input id="bb_enable_group_activity_topics" name="<?php echo empty( $notice ) ? 'bb-enable-group-activity-topics' : ''; ?>" type="checkbox" value="1" <?php echo empty( $notice ) ? checked( $val, true, false ) : ''; ?> />
		<label for="bb_enable_group_activity_topics"><?php esc_html_e( 'Enable topics for groups.', 'buddyboss' ); ?></label>
		<p class="description"><?php esc_html_e( 'Allow group organizers to set categories for members to use in group posts.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Link to Activity Topics tutorial.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_admin_activity_topics_settings_tutorial() {
		?>
		<p>
			<a class="button" target="_blank" href="
			<?php
				echo esc_url(
					bp_get_admin_url(
						add_query_arg(
							array(
								'page'    => 'bp-help',
								'article' => 128458,
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
}

return new BP_Admin_Setting_Activity();
