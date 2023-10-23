<?php
/**
 * TutorLMS integration admin tab
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\TutorLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup TutorLMS integration admin tab class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_TutorLMS_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	/**
	 * Current section.
     *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var $current_section
	 */
	protected $current_section;

	/**
	 * Initialize
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function initialize() {
		$this->tab_order       = 52;
		$this->current_section = 'bb_tutorlms-integration';
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';

		add_filter( 'bb_admin_icons', array( $this, 'bb_tutorlms_admin_setting_icons' ), 10, 2 );
	}

	/**
	 * TutorLMS Integration is active?
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool
	 */
	public function is_active() {
		return (bool) apply_filters( 'bb_tutorlms_integration_is_active', true );
	}

	/**
	 * TutorLMS integration tab scripts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_admin_script() {

		$active_tab = bp_core_get_admin_active_tab();

		if ( 'bb-tutorlms' === $active_tab ) {
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl_css = is_rtl() ? '-rtl' : '';
			wp_enqueue_style( 'bb-tutorlms-admin', bb_tutorlms_integration_url( '/assets/css/bb-tutorlms-admin' . $rtl_css . $min . '.css' ), false, buddypress()->version );
			wp_enqueue_script( 'bb-tutorlms-admin', bb_tutorlms_integration_url( '/assets/js/bb-tutorlms-admin' . $min . '.js' ), false, buddypress()->version );
		}

		parent::register_admin_script();

	}

	/**
	 * Method to save the fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function settings_save() {
		$bb_tutorlms_arr = array();
		$fields = $this->bb_tutorlms_get_settings_fields();
		foreach ( (array) $fields as $section_id => $section_fields ) {
			foreach ( (array) $section_fields as $field_id => $field ) {
				if ( is_callable( $field['sanitize_callback'] ) ) {
					$value = $field['sanitize_callback']( $value );
				}
				if ( 'bb_tutorlms_group_sync_settings_section' === $section_id ) {
					$bb_tutorlms_arr[ $field_id ] = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : 0;
				}
				if ( 'bb_tutorlms_posts_activity_settings_section' === $section_id ) {
					$value = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : 0;
					bp_update_option( $field_id, $value );
				}
			}
		}
		bp_update_option( 'bb-tutorlms', $bb_tutorlms_arr );
	}

	/**
	 * Register setting fields for TutorLMS integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_fields() {

		$sections = $this->bb_tutorlms_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = $this->bb_tutorlms_get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title     = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback  = ! empty( $section['callback'] ) ? $section['callback'] : false;
			$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;

			// Add the section.
			$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback );

			// Loop through fields for this section.
			foreach ( (array) $fields as $field_id => $field ) {

				$field['args'] = isset( $field['args'] ) ? $field['args'] : array();

				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : array();
					$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
				}
			}
		}
	}

	/**
	 * Get setting sections for TutorLMS integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array $settings Settings sections for TutorLMS integration.
	 */
	public function bb_tutorlms_get_settings_sections() {
		$group_sync_title = __( 'TutorLMS <span>&rarr; Social groups</span>', 'buddyboss' );
		if ( ! function_exists( 'tutor' ) ) {
			$group_sync_title = __( 'TutorLMS <span>&rarr; requires plugin to activate</span>', 'buddyboss' );
		} elseif ( ! bp_is_active( 'groups' ) && function_exists( 'tutor' ) ) {
			$group_sync_title = __( 'TutorLMS <span>&rarr; Social Groups</span>', 'buddyboss' );
		}
		// TutorLMS group sync and Post activity feed sections.
		$settings['bb_tutorlms_group_sync_settings_section']     = array(
			'page'              => 'TutorLMS',
			'title'             => $group_sync_title,
			'tutorial_callback' => array( $this, 'bb_tutorlms_tutorlms_group_sync_tutorial' ),
		);
        if ( bp_is_active( 'activity' ) ) {
	        $settings['bb_tutorlms_posts_activity_settings_section'] = array(
		        'page'              => 'TutorLMS',
		        'title'             => __( 'Posts in Activity Feed', 'buddyboss' ),
		        'tutorial_callback' => array( $this, 'bb_tutorlms_tutorlms_posts_activity_tutorial' ),
	        );
        }

		return (array) apply_filters( 'bb_tutorlms_get_settings_sections', $settings );
	}

	/**
	 * Get setting fields for section in TutorLMS integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array|false $fields setting fields for section in TutorLMS integration false otherwise.
	 */
	public function bb_tutorlms_get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty.
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->bb_tutorlms_get_settings_fields();
		$fields = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

		return $fields;
	}

	/**
	 * Register setting fields for TutorLMS integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array $fields setting fields for tutorlms integration.
	 */
	public function bb_tutorlms_get_settings_fields() {
		$fields = array();

		$bb_tutorlms_group_sync_field['bb-tutorlms-enable'] = array(
			'title'             => __( 'TutorLMS Group Sync', 'buddyboss' ),
			'callback'          => array( $this, 'bb_tutorlms_group_sync_callback' ),
			'sanitize_callback' => 'string',
			'args'              => array(),
		);
		if ( bb_tutorlms_enable() ) {
			$bb_tutorlms_group_sync_field['bb-tutorlms-group-course-tab']      = array(
				'title'             => __( 'Group Course Tab', 'buddyboss' ),
				'callback'          => array( $this, 'bb_tutorlms_group_course_tab_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			);
			$bb_tutorlms_group_sync_field['bb-tutorlms-course-tab-visibility'] = array(
				'title'             => __( 'Tab Visibility', 'buddyboss' ),
				'callback'          => array( $this, 'bb_tutorlms_course_tab_visibility_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			);
			$bb_tutorlms_group_sync_field['bb-tutorlms-course-visibility']     = array(
				'title'             => __( 'Course Visibility', 'buddyboss' ),
				'callback'          => array( $this, 'bb_tutorlms_course_visibility_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			);
			if ( bp_is_active( 'activity' ) ) {
				$bb_tutorlms_group_sync_field['bb-tutorlms-course-activity-info']  = array(
					'title'             => __( 'Display Course Activity', 'buddyboss' ),
					'callback'          => array( $this, 'bb_tutorlms_display_course_activity_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array(),
				);
				$bb_tutorlms_group_sync_field['bb-tutorlms-user-enrolled-course']  = array(
					'title'             => ' ',
					'callback'          => array( $this, 'bb_tutorlms_user_enrolled_course_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => 'child-no-padding' ),
				);
				$bb_tutorlms_group_sync_field['bb-tutorlms-user-started-course']   = array(
					'title'             => ' ',
					'callback'          => array( $this, 'bb_tutorlms_user_started_course_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => 'child-no-padding' ),
				);
				$bb_tutorlms_group_sync_field['bb-tutorlms-user-completes-course'] = array(
					'title'             => ' ',
					'callback'          => array( $this, 'bb_tutorlms_user_completes_course_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => 'child-no-padding' ),
				);
				$bb_tutorlms_group_sync_field['bb-tutorlms-user-creates-lesson']   = array(
					'title'             => ' ',
					'callback'          => array( $this, 'bb_tutorlms_user_creates_lesson_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => 'child-no-padding' ),
				);
				$bb_tutorlms_group_sync_field['bb-tutorlms-user-updates-lesson']   = array(
					'title'             => ' ',
					'callback'          => array( $this, 'bb_tutorlms_user_updates_lesson_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => 'child-no-padding' ),
				);
				$bb_tutorlms_group_sync_field['bb-tutorlms-user-started-quiz']     = array(
					'title'             => ' ',
					'callback'          => array( $this, 'bb_tutorlms_user_started_quiz_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => 'child-no-padding' ),
				);
				$bb_tutorlms_group_sync_field['bb-tutorlms-user-finished-quiz']    = array(
					'title'             => ' ',
					'callback'          => array( $this, 'bb_tutorlms_user_finished_quiz_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => 'child-no-padding' ),
				);
			}
		}
		$fields['bb_tutorlms_group_sync_settings_section'] = $bb_tutorlms_group_sync_field;

		if ( function_exists( 'bb_tutorlms_get_post_types' ) ) {
			$tutorlms_post_types = bb_tutorlms_get_post_types();
			if ( ! empty( $tutorlms_post_types ) ) {
				$fields['bb_tutorlms_posts_activity_settings_section']['information'] = array(
					'title'             => esc_html__( 'Custom Posts', 'buddyboss' ),
					'callback'          => array( $this, 'bb_tutorlms_posts_activity_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => 'hidden-header' ),
				);
				foreach ( $tutorlms_post_types as $post_type ) {
					$option_name         = bb_post_type_feed_option_name( $post_type );
					$post_type_obj       = get_post_type_object( $post_type );
					$child_comment_class = ! bp_is_post_type_feed_enable( $post_type ) ? 'bp-display-none' : '';
					$child_option_name   = bb_post_type_feed_comment_option_name( $post_type );

					// Main post type.
					$fields['bb_tutorlms_posts_activity_settings_section'][ $option_name ] = array(
						'title'             => ' ',
						'callback'          => array( $this, 'bb_tutorlms_posts_activity_field_callback' ),
						'sanitize_callback' => 'string',
						'args'              => array(
							'action'        => 'post',
							'post_type'     => $post_type,
							'option_name'   => $option_name,
							'post_type_obj' => $post_type_obj,
							'class'         => 'th-hide child-no-padding',
						),
					);

					// Comment of post type.
					$fields['bb_tutorlms_posts_activity_settings_section'][ $child_option_name ] = array(
						'title'             => ' ',
						'callback'          => array( $this, 'bb_tutorlms_posts_activity_field_callback' ),
						'sanitize_callback' => 'string',
						'args'              => array(
							'action'        => 'comment',
							'post_type'     => $post_type,
							'option_name'   => $child_option_name,
							'post_type_obj' => $post_type_obj,
							'class'         => 'th-hide child-no-padding child-custom-post-type bp-child-post-type ' . esc_attr( $child_comment_class ),
						),
					);
				}
			}
		}

		return (array) apply_filters( 'bb_tutorlms_get_settings_fields', $fields );
	}

	/**
	 * Link to TutorLMS Group Sync Settings tutorial.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_tutorlms_group_sync_tutorial() {
		?>
        <p>
            <a class="button" href="
			<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => '125826',
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
	 * Link to TutorLMS Posts Activity tutorial.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_tutorlms_posts_activity_tutorial() {
		?>
        <p>
            <a class="button" href="
			<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => '125826',
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
	 * Callback function TutorLMS group sync.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_group_sync_callback() {
		?>
        <input name="bb-tutorlms-enable" id="bb-tutorlms-enable" type="checkbox" value="1" <?php checked( bb_tutorlms_enable() ); ?>/>
        <label for="bb-tutorlms-enable">
			<?php esc_html_e( 'Enable TutorLMS course to be used within social groups', 'buddyboss' ); ?>
        </label>
        <?php
	}

	/**
	 * Callback function TutorLMS group course tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_group_course_tab_callback() {
		?>
        <input name="bb-tutorlms-group-course-tab" id="bb-tutorlms-group-course-tab" type="checkbox" value="1" <?php checked( bb_tutorlms_group_course_tab() ); ?>/>
        <label for="bb-tutorlms-group-course-tab">
			<?php esc_html_e( 'Display "Courses" tab in Social Groups', 'buddyboss' ); ?>
        </label>
        <p class="description">
			<?php esc_html_e( 'Course organizers have the option to manage whether they want a course tab to show and which courses specifically they would like to show.', 'buddyboss' ); ?>
        </p>
		<?php
	}

	/**
	 * Callback function TutorLMS course tab visibility.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
    public function bb_tutorlms_course_tab_visibility_callback() {
	    ?>
        <input name="bb-tutorlms-course-tab-visibility" id="bb-tutorlms-course-tab-visibility" type="checkbox" value="1" <?php checked( bb_tutorlms_course_tab_visibility() ); ?>/>
        <label for="bb-tutorlms-course-tab-visibility">
		    <?php esc_html_e( 'Allow group organizers to hide the "Course" tab during course creation and from the manage course screen.', 'buddyboss' ); ?>
        </label>
	    <?php
    }

	/**
	 * Callback function TutorLMS course visibility.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_course_visibility_callback() {
		?>
        <input name="bb-tutorlms-course-visibility" id="bb-tutorlms-course-visibility" type="checkbox" value="1" <?php checked( bb_tutorlms_course_visibility() ); ?>/>
        <label for="bb-tutorlms-course-visibility">
			<?php esc_html_e( 'Allow group organizers to choose which courses to show within the course tab.', 'buddyboss' ); ?>
        </label>
		<?php
	}

	/**
	 * Callback function TutorLMS display course activity.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
    public function bb_tutorlms_display_course_activity_callback() {
        ?>
        <p class="description">
		    <?php esc_html_e( 'Any option selected below will show in group creation and group manage screens to allow group organizer to enable or disable course activity posts for their own group.', 'buddyboss' ); ?>
        </p>
        <?php
    }

	/**
	 * Callback function TutorLMS user enrolled course.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_user_enrolled_course_callback() {
		?>
        <input name="bb-tutorlms-user-enrolled-course" id="bb-tutorlms-user-enrolled-course" type="checkbox" value="1" <?php checked( bb_tutorlms_user_enrolled_course() ); ?>/>
        <label for="bp-zoom-enable-groups">
			<?php esc_html_e( 'User enrolled in a course', 'buddyboss' ); ?>
        </label>
		<?php
	}

	/**
	 * Callback function TutorLMS user started a course.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_user_started_course_callback() {
		?>
        <input name="bb-tutorlms-user-started-course" id="bb-tutorlms-user-started-course" type="checkbox" value="1" <?php checked( bb_tutorlms_user_started_course() ); ?>/>
        <label for="bp-zoom-enable-groups">
			<?php esc_html_e( 'User started a course', 'buddyboss' ); ?>
        </label>
		<?php
	}

	/**
	 * Callback function TutorLMS user completes a course.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_user_completes_course_callback() {
		?>
        <input name="bb-tutorlms-user-completes-course" id="bb-tutorlms-user-completes-course" type="checkbox" value="1" <?php checked( bb_tutorlms_user_completes_course() ); ?>/>
        <label for="bp-zoom-enable-groups">
			<?php esc_html_e( 'User completes a course', 'buddyboss' ); ?>
        </label>
		<?php
	}

	/**
	 * Callback function TutorLMS user creates a lesson.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_user_creates_lesson_callback() {
		?>
        <input name="bb-tutorlms-user-creates-lesson" id="bb-tutorlms-user-creates-lesson" type="checkbox" value="1" <?php checked( bb_tutorlms_user_creates_lesson() ); ?>/>
        <label for="bp-zoom-enable-groups">
			<?php esc_html_e( 'User creates a lesson', 'buddyboss' ); ?>
        </label>
		<?php
	}

	/**
	 * Callback function TutorLMS user updates a lesson.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_user_updates_lesson_callback() {
		?>
        <input name="bb-tutorlms-user-updates-lesson" id="bb-tutorlms-user-updates-lesson" type="checkbox" value="1" <?php checked( bb_tutorlms_user_updates_lesson() ); ?>/>
        <label for="bp-zoom-enable-groups">
			<?php esc_html_e( 'User updates a lesson', 'buddyboss' ); ?>
        </label>
		<?php
	}

	/**
	 * Callback function TutorLMS user started a quiz.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_user_started_quiz_callback() {
		?>
        <input name="bb-tutorlms-user-started-quiz" id="bb-tutorlms-user-started-quiz" type="checkbox" value="1" <?php checked( bb_tutorlms_user_started_quiz() ); ?>/>
        <label for="bp-zoom-enable-groups">
			<?php esc_html_e( 'User started a quiz', 'buddyboss' ); ?>
        </label>
		<?php
	}

	/**
	 * Callback function TutorLMS user finished a quiz.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_user_finished_quiz_callback() {
		?>
        <input name="bb-tutorlms-user-finished-quiz" id="bb-tutorlms-user-finished-quiz" type="checkbox" value="1" <?php checked( bb_tutorlms_user_finished_quiz() ); ?>/>
        <label for="bp-zoom-enable-groups">
			<?php esc_html_e( 'User finished a quiz', 'buddyboss' ); ?>
        </label>
		<?php
	}

	/**
	 * Callback function TutorLMS post types.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_posts_activity_callback() {
		?>
        <p class="description">
			<?php esc_html_e( 'Select which custom post types show in the activity feed when members instructors and site owners publish them, you can select whether or not to show comments in these activity posts.', 'buddyboss' ); ?>
        </p>
		<?php
	}

	/**
	 * Tutor LMS posts activity feed fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Args of array.
	 *
	 * @return void
	 */
	public function bb_tutorlms_posts_activity_field_callback( $args ) {
		$action        = $args['action'];
		$post_type     = $args['post_type'];
		$option_name   = $args['option_name'];
		$post_type_obj = $args['post_type_obj'];
		$input_class   = 'bp-feed-post-type-checkbox';
		$lable         = $post_type_obj->labels->name;
		$checked       = bp_is_post_type_feed_enable( $post_type, false );
		if ( 'comment' === $action ) {
			$input_class = 'bp-feed-post-type-commenet-checkbox bp-feed-post-type-comment-' . esc_attr( $post_type );
			$lable       = sprintf( esc_html__( 'Enable %s comments in the activity feed.', 'buddyboss' ), esc_html( $post_type_obj->labels->name ) );
			$checked     = bb_is_post_type_feed_comment_enable( $post_type, false );
		}
		?>
        <input class="<?php echo esc_attr( $input_class ); ?> <?php echo esc_attr( $option_name ); ?>"
               data-post_type="<?php echo esc_attr( $post_type ); ?>" name="<?php echo esc_attr( $option_name ); ?>"
               id="<?php echo esc_attr( $option_name ); ?>" type="checkbox"
               value="1" <?php checked( $checked ); ?>/>
        <label for="<?php echo esc_attr( $option_name ); ?>">
			<?php echo $lable; ?>
        </label>
		<?php
	}

	/**
	 * Added icon for the TutorLMS admin settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $meta_icon Icon class.
	 * @param string $id        Section ID.
	 *
	 * @return mixed|string
	 */
	public function bb_tutorlms_admin_setting_icons( $meta_icon, $id = '' ) {
		if ( 'bb_tutorlms_group_sync_settings_section' === $id ) {
			$meta_icon = 'bb-icon-bf bb-icon-brand-tutorlms';
		}

		return $meta_icon;
	}

	/**
	 * Output the form html on the setting page (not including submit button).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function form_html() {
		// Check license is valid.
		if ( ! function_exists( 'tutor' ) || ! bp_is_active( 'groups' ) ) {
			if ( is_file( $this->intro_template ) ) {
				require $this->intro_template;
			}
		} else {
			parent::form_html();
		}
	}
}
