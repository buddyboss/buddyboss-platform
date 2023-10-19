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

		add_filter( 'bb_admin_icons', array( $this, 'admin_setting_icons' ), 10, 2 );
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
		}

		parent::register_admin_script();

	}

	/**
	 * Method to save the fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function settings_save() {

		$bb_tutorlms = isset( $_POST['bb-tutorlms'] ) ? map_deep( wp_unslash( $_POST['bb-tutorlms'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $bb_tutorlms ) ) {

			$settings = bb_get_tutorlms_settings();

			$settings = bp_parse_args( $bb_tutorlms, $settings );
			bp_update_option( 'bb-tutorlms', $settings );
		}
	}

	/**
	 * Register setting fields for TutorLMS integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_fields() {

		$sections = $this->get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = $this->get_settings_fields_for_section( $section_id );

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
	public function get_settings_sections() {
		$group_sync_title = __( 'TutorLMS <span>&rarr; Social groups</span>', 'buddyboss' );
		if ( ! function_exists( 'tutor' ) ) {
			$group_sync_title = __( 'TutorLMS <span>&rarr; requires plugin to activate</span>', 'buddyboss' );
        } elseif ( ! bp_is_active( 'groups' ) && function_exists( 'tutor' ) ) {
			$group_sync_title = __( 'TutorLMS <span>&rarr; Social Groups</span>', 'buddyboss' );
		}
		$settings = array(
			'bb_tutorlms_group_sync_settings_section' => array(
				'page'              => 'TutorLMS',
				'title'             => $group_sync_title,
				'tutorial_callback' => array( $this, 'setting_callback_tutorlms_tutorial' ),
			),
		);

		return $settings;
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
	public function get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty.
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->get_settings_fields();
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
	public function get_settings_fields() {
		$fields = array();

		$fields['bb_tutorlms_group_sync_settings_section'] = array(
			'bb-tutorlms-enable'                => array(
				'title'             => __( 'TutorLMS Group Sync', 'buddyboss' ),
				'callback'          => array( $this, 'bb_tutorlms_group_sync_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-tutorlms-group-course-tab'      => array(
				'title'             => __( 'Group Course Tab', 'buddyboss' ),
				'callback'          => array( $this, 'bb_tutorlms_group_course_tab_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-tutorlms-course-tab-visibility' => array(
				'title'             => __( 'Tab Visibility', 'buddyboss' ),
				'callback'          => array( $this, 'bb_tutorlms_course_tab_visibility_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-tutorlms-course-visibility'     => array(
				'title'             => __( 'Course Visibility', 'buddyboss' ),
				'callback'          => array( $this, 'bb_tutorlms_course_visibility_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-tutorlms-course-activity'     => array(
				'title'             => __( 'Display Course Activity', 'buddyboss' ),
				'callback'          => array( $this, 'bb_tutorlms_display_course_activity_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
		);
        return $fields;
	}

	/**
	 * Callback function TutorLMS group sync.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_tutorlms_group_sync_callback() {
		?>
        <input name="bb-tutorlms[bb-tutorlms-enable]" id="bb-tutorlms-enable" type="checkbox" value="1" <?php checked( bb_tutorlms_enable() ); ?>/>
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
        <input name="bb-tutorlms[bb-tutorlms-group-course-tab]" id="bb-tutorlms-group-course-tab" type="checkbox" value="1" <?php checked( bb_tutorlms_group_course_tab() ); ?>/>
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
        <input name="bb-tutorlms[bb-tutorlms-course-tab-visibility]" id="bb-tutorlms-course-tab-visibility" type="checkbox" value="1" <?php checked( bb_tutorlms_course_tab_visibility() ); ?>/>
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
        <input name="bb-tutorlms[bb-tutorlms-course-visibility]" id="bb-tutorlms-course-visibility" type="checkbox" value="1" <?php checked( bb_tutorlms_course_visibility() ); ?>/>
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
        <tr class="child-no-padding">
            <th scope="row"></th>
            <td>
                <input name="bb-tutorlms[bb-tutorlms-user-enrolled-course]" id="bb-tutorlms-user-enrolled-course" type="checkbox" value="1" <?php checked( bb_tutorlms_user_enrolled_course() ); ?>/>
                <label for="bp-zoom-enable-groups">
		            <?php esc_html_e( 'User enrolled in a course', 'buddyboss' ); ?>
                </label>
            </td>
        </tr>
        <tr class="child-no-padding">
            <th scope="row"></th>
            <td>
                <input name="bb-tutorlms[bb-tutorlms-user-started-course]" id="bb-tutorlms-user-started-course" type="checkbox" value="1" <?php checked( bb_tutorlms_user_started_course() ); ?>/>
                <label for="bp-zoom-enable-groups">
		            <?php esc_html_e( 'User started a course', 'buddyboss' ); ?>
                </label>
            </td>
        </tr>
        <tr class="child-no-padding">
            <th scope="row"></th>
            <td>
                <input name="bb-tutorlms[bb-tutorlms-user-completes-course]" id="bb-tutorlms-user-completes-course" type="checkbox" value="1" <?php checked( bb_tutorlms_user_completes_course() ); ?>/>
                <label for="bp-zoom-enable-groups">
		            <?php esc_html_e( 'User completes a course', 'buddyboss' ); ?>
                </label>
            </td>
        </tr>
        <tr class="child-no-padding">
            <th scope="row"></th>
            <td>
                <input name="bb-tutorlms[bb-tutorlms-user-creates-lesson]" id="bb-tutorlms-user-creates-lesson" type="checkbox" value="1" <?php checked( bb_tutorlms_user_creates_lesson() ); ?>/>
                <label for="bp-zoom-enable-groups">
		            <?php esc_html_e( 'User creates a lesson', 'buddyboss' ); ?>
                </label>
            </td>
        </tr>
        <tr class="child-no-padding">
            <th scope="row"></th>
            <td>
                <input name="bb-tutorlms[bb-tutorlms-user-updates-lesson]" id="bb-tutorlms-user-updates-lesson" type="checkbox" value="1" <?php checked( bb_tutorlms_user_updates_lesson() ); ?>/>
                <label for="bp-zoom-enable-groups">
		            <?php esc_html_e( 'User updates a lesson', 'buddyboss' ); ?>
                </label>
            </td>
        </tr>
        <tr class="child-no-padding">
            <th scope="row"></th>
            <td>
                <input name="bb-tutorlms[bb-tutorlms-user-started-quiz]" id="bb-tutorlms-user-started-quiz" type="checkbox" value="1" <?php checked( bb_tutorlms_user_started_quiz() ); ?>/>
                <label for="bp-zoom-enable-groups">
		            <?php esc_html_e( 'User started a quiz', 'buddyboss' ); ?>
                </label>
            </td>
        </tr>
        <tr class="child-no-padding">
            <th scope="row"></th>
            <td>
                <input name="bb-tutorlms[bb-tutorlms-user-finished-quiz]" id="bb-tutorlms-user-finished-quiz" type="checkbox" value="1" <?php checked( bb_tutorlms_user_finished_quiz() ); ?>/>
                <label for="bp-zoom-enable-groups">
		            <?php esc_html_e( 'User finished a quiz', 'buddyboss' ); ?>
                </label>
            </td>
        </tr>
        <?php
    }

	/**
	 * Link to TutorLMS Settings tutorial.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setting_callback_tutorlms_tutorial() {
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
	 * Added icon for the TutorLMS admin settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $meta_icon Icon class.
	 * @param string $id        Section ID.
	 *
	 * @return mixed|string
	 */
	public function admin_setting_icons( $meta_icon, $id = '' ) {
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
