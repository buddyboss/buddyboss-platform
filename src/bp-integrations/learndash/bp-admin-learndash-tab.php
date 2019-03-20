<?php
/**
 * BuddyBoss LearnDash integration admin tabs.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the BuddyBoss LearnDash admin tab.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_LearnDash_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	/**
	 * Initialize Admin Integration Tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function initialize() {
		$this->tab_order             = 20;
		$this->intro_template        = $this->root_path . '/templates/admin/integration-tab-intro.php';
	}

	/**
	 * Save learndash settings with setting helper class
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function settings_save() {
		$settings = bp_ld_sync('settings');

		if ($values = bp_ld_sync()->getRequest($settings->getName())) {
			$settings->set(null, $values)->update();
		}
	}

	/**
	 * Register setting fields
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function register_fields() {

		$fields = apply_filters('bp_integrations_learndash_fields', array(
			'buddypress' => [$this, 'registerBuddypressSettings'],
			'learndash' => [$this, 'registerLearnDashSettings'],
			'reports' => [$this, 'registerReportsSettings']
		), $this);

		foreach ($fields as $key => $callback) {
			call_user_func($callback);

			/**
			 * Action to add additional fields into each learndash setting sections
			 *
			 * @since BuddyBoss 1.0.0
			 */
			do_action('bp_integrations_learndash_field_added', $key, $this);
		}
	}

	/**
	 * Load the settings html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function form_html() {
		// Check Group component active.
		if ( ! bp_is_active( 'groups' ) ) {
			if ( is_file ( $this->intro_template ) ) {
				require $this->intro_template;
			}

			return;
		}

		parent::form_html();
	}

	/**
	 * Register Buddypress related settings
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerBuddypressSettings()
	{
		$this->current_section = 'buddypress';

		$this->add_section(
			'bp_ld_sync-buddypress',
			__( 'BuddyBoss to LearnDash Sync', 'buddyboss' ),
			[ $this, 'buddypress_groups_sync_description' ]
		);

		$this->add_checkbox_field(
			'enabled',
			__('Social Group Sync', 'buddyboss'),
			[
				'input_text' => sprintf(
					__( 'Enable group sync functionality <b>FROM</b> <a href="%s">BuddyBoss Social Groups</a> <b>TO</b> <a href="%s">LearnDash Groups</a>', 'buddyboss' ),
					add_query_arg([
						'page' => 'bp-groups',
					], admin_url( 'admin.php' ) ),
					add_query_arg([
						'post_type' => 'groups',
					], admin_url( 'edit.php' ) )
				),
				'input_run_js' => 'buddypress_enabled'
			]
		);

		$this->add_checkbox_field(
			'show_in_bp_create',
			__('Create LearnDash Group', 'buddyboss'),
			[
				'input_text' => __( 'Allow social group organizers to create associated LearnDash groups during the group creation process', 'buddyboss' ),
				'input_run_js' => 'buddypress_show_in_bp_create',
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_checkbox_field(
			'show_in_bp_manage',
			__('Manage LearnDash Group', 'buddyboss'),
			[
				'input_text' => __( 'Allow social group organizers to manage associated LearnDash groups after the group creation process', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_select_field(
			'tab_access',
			__('Course Tab Visibility', 'buddyboss'),
			[
				'input_options' => [
					'anyone'   => __('Anyone', 'buddyboss'),
					'loggedin' => __('Loggedin Users', 'buddyboss'),
					'member'   => __('Group Members', 'buddyboss'),
					'noone'    => __('No one', 'buddyboss'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'Select who can see the course tab in social groups', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled',
			]
		);

		$this->add_checkbox_field(
			'default_auto_sync',
			__('Auto Create LearnDash Group', 'buddyboss'),
			[
				'input_text' => __( 'Automatically create and associate a LearnDash group upon creation', 'buddyboss' ),
				'input_description' => __( '(Required if you want an associated LearnDash group and course tab is disabled during creation)', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_checkbox_field(
			'delete_ld_on_delete',
			__('Auto Delete LearnDash Group', 'buddyboss'),
			[
				'input_text' => __( 'Automatically delete the associated LearnDash group when the social group is deleted', 'buddyboss' ),
				'input_description' => __( '(Uncheck this to delete the group manually)', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_select_field(
			'default_admin_sync_to',
			__('Sync Organizers', 'buddyboss'),
			[
				'input_options' => [
					'admin' => __('Group Leader', 'buddyboss'),
					'user'  => __('Group User', 'buddyboss'),
					'none'  => __('None', 'buddyboss'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'Select the group organizer\'s assigned role in LearnDash', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled',
			]
		);

		$this->add_select_field(
			'default_mod_sync_to',
			__('Sync Moderators', 'buddyboss'),
			[
				'input_options' => [
					'admin' => __('Group Leader', 'buddyboss'),
					'user'  => __('Group User', 'buddyboss'),
					'none'  => __('None', 'buddyboss'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'Select the group moderator\'s assigned role in LearnDash', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled',
			]
		);

		$this->add_select_field(
			'default_user_sync_to',
			__('Sync Users', 'buddyboss'),
			[
				'input_options' => [
					'user'  => __('Group User', 'buddyboss'),
					'none'  => __('None', 'buddyboss'),
		        ],
		        'input_default' => 'user',
				'input_description' => __( 'Select the group member\'s assigned role in LearnDash', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled',
			]
		);
	}

	/**
	 * Register Learndash related settings
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerLearnDashSettings()
	{
		$this->current_section = 'learndash';

		$this->add_section(
			'bp_ld_sync-learndash',
			__( 'LearnDash to BuddyBoss Sync', 'buddyboss' ),
			[ $this, 'learndash_groups_sync_description' ]
		);

		$this->add_checkbox_field(
			'enabled',
			__('LearnDash Group Sync', 'buddyboss'),
			[
				'input_text' => sprintf(
					__( 'Enable group sync functionality <b>FROM</b> <a href="%s">LearnDash Groups</a> <b>TO</b> <a href="%s">BuddyBoss Social Groups</a>', 'buddyboss' ),
					add_query_arg([
						'post_type' => 'groups',
					], admin_url( 'edit.php' ) ),
					add_query_arg([
						'page' => 'bp-groups',
					], admin_url( 'admin.php' ) )
				),
				'input_run_js' => 'learndash_enabled'
			]
		);

		$this->add_checkbox_field(
			'default_auto_sync',
			__('Auto Create Social Group', 'buddyboss'),
			[
				'input_text' => __( 'Automatically create and associate a Social Group upon creation', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled'
			]
		);

		$this->add_select_field(
			'default_bp_privacy',
			__('Social Group Privacy', 'buddyboss'),
			[
				'input_options' => [
		            'public'  => __( 'Public', 'buddyboss' ),
		            'private' => __( 'Private', 'buddyboss' ),
		            'hidden'  => __( 'Hidden', 'buddyboss' )
		        ],
		        'input_default' => 'private',
				'input_description' => __( 'Select the created Social Group privacy setting', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled'
			]
		);

		$this->add_select_field(
			'default_bp_invite_status',
			__('Social Group Invite Status', 'buddyboss'),
			[
				'input_options' => [
	                'members' => __('All group members', 'buddyboss'),
	                'mods'    => __('Group organizers and moderators only', 'buddyboss'),
	                'admins'  => __('Group organizers only', 'buddyboss')
		        ],
		        'input_default' => 'mods',
				'input_description' => __( 'Select which group participants can invite others to join the group', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled'
			]
		);

		$this->add_checkbox_field(
			'delete_bp_on_delete',
			__('Auto Delete Social Group', 'buddyboss'),
			[
				'input_text' => __( 'Automatically delete the associated Social Group when the LearnDash group is deleted', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled'
			]
		);

		$this->add_select_field(
			'default_admin_sync_to',
			__('Sync Leaders', 'buddyboss'),
			[
				'input_options' => [
					'admin' => __('Organizer', 'buddyboss'),
					'mod'   => __('Moderator', 'buddyboss'),
					'user'  => __('Member', 'buddyboss'),
					'none'  => __('None', 'buddyboss'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'Select the LearnDash Leaders\'s assigned role in Social Groups', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled',
			]
		);

		$this->add_select_field(
			'default_user_sync_to',
			__('Sync Users', 'buddyboss'),
			[
				'input_options' => [
					'admin' => __('Organizer', 'buddyboss'),
					'mod'   => __('Moderator', 'buddyboss'),
					'user'  => __('Member', 'buddyboss'),
					'none'  => __('None', 'buddyboss'),
		        ],
		        'input_default' => 'user',
				'input_description' => __( 'Select the LearnDash User\'s assigned role in Social Groups', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled',
			]
		);
	}

	/**
	 * Register reports related settings
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerReportsSettings() {
		$this->current_section = 'reports';

		$this->add_section(
			'bp_ld_sync-reports',
			__( 'Group Reports Settings', 'buddyboss' ),
			[ $this, 'learndash_groups_report_description' ]
		);

		$this->add_checkbox_field(
			'enabled',
			__('Group Reports', 'buddyboss'),
			[
				'input_text' => __( 'Enable Social Group Report for LearnDash', 'buddyboss' ),
				'input_run_js' => 'reports_enabled'
			]
		);

		$this->add_field(
			'access',
			__('Report Access', 'buddyboss'),
			[ $this, 'output_report_access_setting' ],
			[],
			[
				'class' => 'js-show-on-reports_enabled'
			]
		);

		$this->add_input_field(
			'per_page',
			__('Report Results Per Page', 'buddyboss'),
			[
				'input_type'        => 'number',
				'input_description' => __( 'Number of report results displayed per page', 'buddyboss' ),
				'class' => 'js-show-on-reports_enabled'
			]
		);
	}

	/**
	 * Description for Buddypress setting section
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function buddypress_groups_sync_description() {
		echo wpautop(
			__( 'Sync BuddyBoss group members to LearnDash groups.', 'buddyboss' )
		);
	}

	/**
	 * Description for Learndash setting section
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function learndash_groups_sync_description() {
		echo wpautop(
			__( 'Sync LearnDash group users to BuddyBoss groups.', 'buddyboss' )
		);
	}

	/**
	 * Description for reports setting section
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function learndash_groups_report_description() {
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
		$input_field = 'access';
		$input_value = $this->get_input_value( $input_field, [] );
		$input_name = $this->get_input_name( $input_field );
		$input_options = [
			'admin'  => __( 'Organizers', 'buddyboss' ),
			'mod'    => __( 'Moderators', 'buddyboss' ),
			'member' => __( 'Members', 'buddyboss' )
		];

        foreach ($input_options as $key => $value) {
        	$checked = in_array( $key, $input_value )? 'checked' : '';
        	printf( '
        		<p>
	        		<label>
	        			<input type="checkbox" name="%s[]" value="%s" %s>%s</option>
	        		</label>
	        	</p>
        	', $input_name, $key, $checked, $value );
        }

		echo $this->render_input_description(__('Select which roles can view reports', 'buddyboss'));
	}

	/**
	 * Overwrite the input name
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function get_input_name( $name ) {
		return bp_ld_sync('settings')->getName("{$this->current_section}.{$name}");
	}

	/**
	 * Overwrite the input value
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function get_input_value( $key, $default = '' ) {
		return bp_ld_sync('settings')->get("{$this->current_section}.{$key}", $default);
	}
}

