<?php
/**
 * BuddyBoss TutorLMS integration admin tabs.
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the BuddyBoss TutorLMS admin tab.
 *
 * @since BuddyBoss 1.0.0
 */
class BB_TutorLMS_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	/**
	 * Initialize Admin Integration Tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function initialize() {
		$this->tab_order      = 30;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
	}

	/**
	 * Save tutorlms settings with setting helper class
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function settings_save() {
		
	}

	/**
	 * Register setting fields
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function register_fields() {

	    $fields = array(
		    'buddyboss' => array( $this, 'registerBuddypressSettings' ),
		    // 'reports' => [$this, 'registerReportsSettings'],
	    );

	    if ( ! bp_is_active( 'groups' ) ) {
		    unset( $fields['buddyboss'] );
		    unset( $fields['tutorlms'] );
        }

		$fields = apply_filters( 'bp_integrations_tutorlms_fields', $fields, $this );

		foreach ( $fields as $key => $callback ) {
			call_user_func( $callback );

			/**
			 * Action to add additional fields into each tutorlms setting sections
			 *
			 * @since BuddyBoss 1.0.0
			 */
			do_action( 'bp_integrations_tutorlms_field_added', $key, $this );
		}
	}

	/**
	 * Load the settings html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function form_html() {
		// Check Group component active.
		if ( ! bp_is_active( 'groups' ) && is_plugin_active( $this->required_plugin ) ) {
			if ( is_file( $this->intro_template ) ) {
				require $this->intro_template;
			}
		}

		parent::form_html();
	}

	/**
	 * Register Buddypress related settings
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerBuddypressSettings() {
		$this->current_section = 'buddyboss';

		$this->add_section(
			'bp_tutor-integration',
			__( 'TutorLMS <span>&rarr; Social groups</span>', 'buddyboss' ),
			'',
			'bb_tutorial_social_group_sync'
		);

		$this->add_checkbox_field(
			'enabled',
			__( 'TutorLMS Group Sync', 'buddyboss' ),
			array(
				'input_text'   => esc_html__( 'Enable TutorLMS course to be used within social groups', 'buddyboss' ),
				'input_run_js' => 'buddyboss_enabled',
			)
		);

		$this->add_checkbox_field(
			'show_in_tl_display_course_tab_in_groups',
			__( 'Group Course Tab', 'buddyboss' ),
			array(
				'input_text'        => __( 'Display "Courses" tab in Social Groups', 'buddyboss' ),
				'input_run_js'      => 'buddyboss_show_in_bp_create',
				'input_description' => __( 'Course organizers have the option to manage whether they want a course tab to show and which courses specifically they would like to show.', 'buddyboss' ),
				'class'             => 'js-show-on-buddyboss_enabled',
			)
		);

		$this->add_checkbox_field(
			'show_in_tl_tab_visibility',
			__( 'Tab Visibility', 'buddyboss' ),
			array(
				'input_text'        => __( 'Allow group organizers to hide the "Course" tab during course creation and from the manage course screen.', 'buddyboss' ),
				'input_run_js'      => 'buddyboss_show_in_bp_create',
				'class'             => 'js-show-on-buddyboss_enabled',
			)
		);

		$this->add_checkbox_field(
			'show_in_tl_course_visibility',
			__( 'Course Visibility', 'buddyboss' ),
			array(
				'input_text'        => __( 'Allow group organizers to choose which courses to show within the course tab.', 'buddyboss' ),
				'input_run_js'      => 'buddyboss_show_in_bp_create',
				'class'             => 'js-show-on-buddyboss_enabled',
			)
		);

		$this->add_checkbox_field(
			'show_in_tl_notification',
			__( 'Notification', 'buddyboss' ),
			array(
				'input_text' => __( 'Send TutorLMS notifications using the BuddyBoss notification systems', 'buddyboss' ),
				'input_description' => sprintf(
				/* translators: URL. */
					__( 'You can manage notification types in the BuddyBoss <a href="%s" target="_blank">notification settings.</a> When enabled, you do not need to use the TutorLMS Email Notifications or Notifications add-ons.', 'buddyboss' ),
					esc_url(
						add_query_arg(
							array(
								'page' => 'bp-settings',
								'tab'  => 'bp-notifications',
							),
							admin_url( 'admin.php' )
						)
					),
				),
				'input_run_js' => 'buddyboss_show_in_bp_create',
				'class' => 'js-show-on-buddyboss_enabled',
			)
		);

		$this->add_checkbox_field(
			'show_in_tl_use_bb_profiles',
			__( 'BuddyPress Profiles', 'buddyboss' ),
			array(
				'input_text'        => __( 'Use BuddyBoss for public profiles and user settings', 'buddyboss' ),
				'input_run_js'      => 'buddyboss_show_in_bp_create',
				'class'             => 'js-show-on-buddyboss_enabled',
			)
		);
	}

	/**
	 * Description for reports setting section
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function tutorlms_groups_report_description() {
		echo wpautop(
			__( 'Control the setting for social group\'s reports.', 'buddyboss' )
		);
	}

	/**
	 * Dropdown options for report aceess setting
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function output_report_access_setting() {
		$input_field   = 'access';
		$input_value   = $this->get_input_value( $input_field, array() );
		$input_name    = $this->get_input_name( $input_field );
		$input_options = array(
			'admin'  => __( 'Organizers', 'buddyboss' ),
			'mod'    => __( 'Moderators', 'buddyboss' ),
			'member' => __( 'Members', 'buddyboss' ),
		);

		foreach ( $input_options as $key => $value ) {
			$checked = in_array( $key, $input_value ) ? 'checked' : '';
			printf(
				'
        		<p>
	        		<label>
	        			<input type="checkbox" name="%s[]" value="%s" %s>%s</option>
	        		</label>
	        	</p>
        	',
				$input_name,
				$key,
				$checked,
				$value
			);
		}

		echo $this->render_input_description( __( 'Select which roles can view reports', 'buddyboss' ) );
	}

	/**
	 * Overwrite the input name
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function get_input_name( $name ) {
		
	}

	/**
	 * Overwrite the input value
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function get_input_value( $key, $default = '' ) {
		return bb_tutorlms( 'settings' )->get( "{$this->current_section}.{$key}", $default );
	}
}
