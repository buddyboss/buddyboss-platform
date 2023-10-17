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
		    'buddypress' => array( $this, 'registerBuddypressSettings' ),
		    'coursetab'  => [$this, 'registerCourseTab'],
		    // 'reports' => [$this, 'registerReportsSettings'],
	    );

	    if ( ! bp_is_active( 'groups' ) ) {
		    unset( $fields['buddypress'] );
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
		$this->current_section = 'buddypress';

		$this->add_section(
			'bb_tutor-buddypress',
			__( 'Social groups <span>&rarr; TutorLMS groups</span>', 'buddyboss' ),
			'',
			'bb_tutorial_social_group_sync'
		);

		$this->add_checkbox_field(
			'enabled',
			__( 'Social Group Sync', 'buddyboss' ),
			array(
				'input_text'   => sprintf(
				/* translators: 1. From text. 2. Group link. 3. To text link. 4. post type group link. */
					'%1$s %2$s %3$s %4$s %5$s',
					esc_html__( 'Enable group sync functionality ', 'buddyboss' ),
					sprintf(
					/* translators: 1. From text. */
						'<strong><em>%s</em></strong>',
						esc_html__( 'from', 'buddyboss' )
					),
					sprintf(
					/* translators: 1. Group link. 2. Group Text. */
						'<a href="%1$s">%2$s</a>',
						esc_url(
							add_query_arg(
								array(
									'page' => 'bb-groups',
								),
								admin_url( 'admin.php' )
							)
						),
						esc_html__( 'BuddyBoss Social Groups', 'buddyboss' )
					),
					sprintf(
					/* translators: 1. To text. */
						'<strong><em>%s</em></strong>',
						esc_html__( 'to', 'buddyboss' )
					),
					sprintf(
					/* translators: 1. Post type group link. 2. Post type group text. */
						'<a href="%1$s">%2$s</a>',
						esc_url(
							add_query_arg(
								array(
									'post_type' => 'groups',
								),
								admin_url( 'edit.php' )
							)
						),
						esc_html__( 'TutorLMS Groups', 'buddyboss' )
					)
				),
				'input_run_js' => 'buddypress_enabled',
			)
		);

		$this->add_checkbox_field(
			'show_in_bp_create',
			__( 'Create TutorLMS Group', 'buddyboss' ),
			array(
				'input_text'   => __( 'Allow social group organizers to create associated TutorLMS groups during the group creation process', 'buddyboss' ),
				'input_run_js' => 'buddypress_show_in_bp_create',
				'class'        => 'js-show-on-buddypress_enabled',
			)
		);

		$this->add_checkbox_field(
			'show_in_bp_manage',
			__( 'Manage TutorLMS Group', 'buddyboss' ),
			array(
				'input_text' => __( 'Allow social group organizers to manage associated TutorLMS groups after the group creation process', 'buddyboss' ),
				'class'      => 'js-show-on-buddypress_enabled',
			)
		);

		$this->add_select_field(
			'tab_access',
			__( 'Course Tab Visibility', 'buddyboss' ),
			array(
				'input_options'     => array(
					'anyone'   => __( 'Anyone', 'buddyboss' ),
					'loggedin' => __( 'Loggedin Users', 'buddyboss' ),
					'member'   => __( 'Group Members', 'buddyboss' ),
					'noone'    => __( 'No one', 'buddyboss' ),
				),
				'input_default'     => 'admin',
				'input_description' => __( 'Select who can see the course tab in social groups', 'buddyboss' ),
				'class'             => 'js-show-on-buddypress_enabled',
			)
		);

		$this->add_checkbox_field(
			'default_auto_sync',
			__( 'Auto Create TutorLMS Group', 'buddyboss' ),
			array(
				'input_text'        => __( 'Automatically create and associate a TutorLMS group upon creation', 'buddyboss' ),
				'input_description' => __( 'Required if you want an associated TutorLMS group, and course tab is disabled during creation', 'buddyboss' ),
				'class'             => 'js-show-on-buddypress_enabled',
			)
		);

		$this->add_checkbox_field(
			'delete_ld_on_delete',
			__( 'Auto Delete TutorLMS Group', 'buddyboss' ),
			array(
				'input_text'        => __( 'Automatically delete the associated TutorLMS group when the social group is deleted', 'buddyboss' ),
				'input_description' => __( 'Uncheck this to delete the group manually', 'buddyboss' ),
				'class'             => 'js-show-on-buddypress_enabled',
			)
		);

		$this->add_select_field(
			'default_admin_sync_to',
			__( 'Sync Organizers', 'buddyboss' ),
			array(
				'input_options'     => array(
					'admin' => __( 'Group Leader', 'buddyboss' ),
					'user'  => __( 'Group User', 'buddyboss' ),
					'none'  => __( 'None', 'buddyboss' ),
				),
				'input_default'     => 'admin',
				'input_description' => __( 'Social group "Organizers" will be assigned to the above role in TutorLMS groups', 'buddyboss' ),
				'class'             => 'js-show-on-buddypress_enabled',
			)
		);

		$this->add_select_field(
			'default_mod_sync_to',
			__( 'Sync Moderators', 'buddyboss' ),
			array(
				'input_options'     => array(
					'admin' => __( 'Group Leader', 'buddyboss' ),
					'user'  => __( 'Group User', 'buddyboss' ),
					'none'  => __( 'None', 'buddyboss' ),
				),
				'input_default'     => 'admin',
				'input_description' => __( 'Social group "Moderators" will be assigned to the above role in TutorLMS groups', 'buddyboss' ),
				'class'             => 'js-show-on-buddypress_enabled',
			)
		);

		$this->add_select_field(
			'default_user_sync_to',
			__( 'Sync Members', 'buddyboss' ),
			array(
				'input_options'     => array(
					'user' => __( 'Group User', 'buddyboss' ),
					'none' => __( 'None', 'buddyboss' ),
				),
				'input_default'     => 'user',
				'input_description' => __( 'Social group "Members" will be assigned to the above role in TutorLMS groups', 'buddyboss' ),
				'class'             => 'js-show-on-buddypress_enabled',
			)
		);
	}

	/**
	 * Register BuddyPress for TutorLMS related settings
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function registerCourseTab() {
		$this->current_section = 'course';
		$this->add_section(
			'bb_tutor_course_tab-buddypress',
			__('Profiles', 'buddyboss'),
			'',
			'bb_profiles_tutorial_my_courses'
			
		);
		$this->add_checkbox_field(
			'courses_visibility',
			__('My Courses Tab', 'buddyboss'),
			[
				'input_text' => __('Display "Courses" tab in profiles', 'buddyboss'),
				'input_description' => __( 'Adds a tab to the logged in member\'s profile displaying all courses they are enrolled in, and a matching link in the profile dropdown. If any certificates have been created, adds a sub-tab showing all certificates the member has earned.', 'buddyboss' ),
			]
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
