<?php

class BP_Learndash_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $groups_sync_option_key = 'learndash_settings_buddypress_groups_sync';
	protected $groups_report_option_key = 'learndash_settings_buddypress_groups_report';
	protected $current_section;

	public function initialize() {
		$this->tab_order             = 10;
		$this->intro_template        = $this->root_path . '/admin/templates/tab-intro.php';
		$this->groups_sync_options   = get_option( $this->groups_sync_option_key ) ?: [];
		$this->groups_report_options = get_option( $this->groups_report_option_key ) ?: [];

		add_action( 'admin_footer', [ $this, 'add_sync_tool_scripts' ], 20 );
	}

	public function settings_save() {
		$settings = bp_ld_sync('settings');

		if ($values = bp_ld_sync()->getRequest($settings->getName())) {
			$settings->set(null, $values)->update();
		}
	}

	public function register_fields() {
		$this->registerBuddypressSettings();
		$this->registerLearndashSettings();
		$this->registerReportsSettings();
	}

	public function form_html() {
		if ( $this->required_plugin && ! is_plugin_active( $this->required_plugin ) ) {
			if ( is_file ( $this->intro_template ) ) {
				require $this->intro_template;
			}

			return;
		}

		parent::form_html();

		require $this->root_path . '/groups-sync/templates/admin/learndash-settings-tools.php';
	}

	public function registerBuddypressSettings()
	{
		$this->current_section = 'buddypress';

		$this->add_section(
			'bp_ld_sync-buddypress',
			__( 'Social Group Sync Settings', 'buddyboss' ),
			[ $this, 'buddypress_groups_sync_description' ]
		);

		$this->add_checkbox_field(
			'enabled',
			__('Enable Social Group Sync', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Enable all group sync functionality on Social Group', 'buddyboss' ),
				'input_run_js' => 'buddypress_enabled'
			]
		);

		$this->add_checkbox_field(
			'show_in_bp_create',
			__('Course Tab on Create', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Show "course" tab when creating a social group and allow creater to control group sync.', 'buddyboss' ),
				'input_run_js' => 'buddypress_show_in_bp_create',
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_checkbox_field(
			'show_in_bp_manage',
			__('Course Tab on Manage', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Show "course" tab when manage a social group settings and allow creater to control group sync.', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_select_field(
			'tab_access',
			__('Course Tab Visibility', 'buddyboss'),
			[
				'input_options' => [
					'anyone'   => __('Anyone', 'learndash'),
					'loggedin' => __('Loggedin Users', 'learndash'),
					'member'   => __('Group Members', 'learndash'),
					'noone'    => __('No one', 'learndash'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'Who can see the "course" tab in social group...', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled',
			]
		);

		$this->add_checkbox_field(
			'default_auto_sync',
			__('Generate Learndash Group', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Automatically generate and associate a Learndash group on creation. (usually for if the group is created programatically or Course tab is disable on creation)', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_checkbox_field(
			'delete_ld_on_delete',
			__('Delete Learndash Group', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Automatically delete the associated Learndash group upon social group deletion. Uncheck this to delete the group manually.', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_checkbox_field(
			'default_user_on_ban',
			__('Delete Banned Members', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Automatically remove the associated Learndash member when he is banned from the social group. Uncheck this to remove the user manually.', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled'
			]
		);

		$this->add_select_field(
			'default_admin_sync_to',
			__('Sync Organizers To:', 'buddyboss'),
			[
				'input_options' => [
					'admin' => __('Group Leader', 'learndash'),
					'user'  => __('Group User', 'learndash'),
					'none'  => __('None', 'learndash'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'When a organizer is synced, their role in Learndash group should be...', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled',
			]
		);

		$this->add_select_field(
			'default_mod_sync_to',
			__('Sync Moderators To:', 'buddyboss'),
			[
				'input_options' => [
					'admin' => __('Group Leader', 'learndash'),
					'user'  => __('Group User', 'learndash'),
					'none'  => __('None', 'learndash'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'When a moderator is synced, their role in Learndash group should be...', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled',
			]
		);

		$this->add_select_field(
			'default_user_sync_to',
			__('Sync Users To:', 'buddyboss'),
			[
				'input_options' => [
					'user'  => __('Group User', 'learndash'),
					'none'  => __('None', 'learndash'),
		        ],
		        'input_default' => 'user',
				'input_description' => __( 'When a member is synced, their role in Learndash group should be...', 'buddyboss' ),
				'class' => 'js-show-on-buddypress_enabled',
			]
		);
	}

	public function registerLearndashSettings()
	{
		$this->current_section = 'learndash';

		$this->add_section(
			'bp_ld_sync-learndash',
			__( 'Learndash Group Sync Settings', 'buddyboss' ),
			[ $this, 'learndash_groups_sync_description' ]
		);

		$this->add_checkbox_field(
			'enabled',
			__('Enable Learndash Group Sync', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Enable all group sync functionality on Learndash Group', 'buddyboss' ),
				'input_run_js' => 'learndash_enabled'
			]
		);

		$this->add_checkbox_field(
			'default_auto_sync',
			__('Generate Learndash Group', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Automatically generate and associate a social group on creation. (usually for if the group is created programatically)', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled'
			]
		);

		$this->add_select_field(
			'default_bp_privacy',
			__('Generated Social Group Privacy', 'buddyboss'),
			[
				'input_options' => [
		            'public'  => __( 'Public', 'learndash' ),
		            'private' => __( 'Private', 'learndash' ),
		            'hidden'  => __( 'Hidden', 'learndash' )
		        ],
		        'input_default' => 'private',
				'input_description' => __( 'When a social group is generated, set the group privacy to...', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled'
			]
		);

		$this->add_select_field(
			'default_bp_invite_status',
			__('Generated Social Group Invite Status', 'buddyboss'),
			[
				'input_options' => [
	                'members' => __('All group members', 'learndash'),
	                'mods'    => __('Group organizers and moderators only', 'learndash'),
	                'admins'  => __('Group organizers only', 'learndash')
		        ],
		        'input_default' => 'mods',
				'input_description' => __( 'When a social group is generated, set the group invite status to...', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled'
			]
		);

		$this->add_checkbox_field(
			'delete_bp_on_delete',
			__('Delete Social Group', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Automatically delete the associated social group upon Learndash group deletion. Uncheck this to delete the group manually.', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled'
			]
		);

		$this->add_select_field(
			'default_admin_sync_to',
			__('Sync Leaders To:', 'buddyboss'),
			[
				'input_options' => [
					'admin' => __('Organizer', 'learndash'),
					'mod'   => __('Moderator', 'learndash'),
					'user'  => __('User', 'learndash'),
					'none'  => __('None', 'buddyboss'),
		        ],
		        'input_default' => 'admin',
				'input_description' => __( 'When a LearnDash leader is synced, their role in social group should be...', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled',
			]
		);

		$this->add_select_field(
			'default_user_sync_to',
			__('Sync Users To:', 'buddyboss'),
			[
				'input_options' => [
					'admin' => __('Organizer', 'learndash'),
					'mod'   => __('Moderator', 'learndash'),
					'user'  => __('User', 'learndash'),
					'none'  => __('None', 'buddyboss'),
		        ],
		        'input_default' => 'user',
				'input_description' => __( 'When a LearnDash user is synced, their role in social group should be...', 'buddyboss' ),
				'class' => 'js-show-on-learndash_enabled',
			]
		);
	}

	public function registerReportsSettings()
	{
		$this->current_section = 'reports';

		$this->add_section(
			'bp_ld_sync-reports',
			__( 'Group Reports Settings', 'buddyboss' ),
			[ $this, 'learndash_groups_report_description' ]
		);

		$this->add_checkbox_field(
			'enabled',
			__('Enable Group Reports', 'buddyboss'),
			[
				'input_text' => __( 'Yes', 'buddyboss' ),
				'input_description' => __( 'Enable Social Group Report for LearnDash.', 'buddyboss' ),
				'input_run_js' => 'reports_enabled'
			]
		);

		$this->add_field(
			'access',
			__('Generated Social Group Privacy', 'buddyboss'),
			[ $this, 'output_report_access_setting' ],
			[],
			[
				'class' => 'js-show-on-reports_enabled'
			]
		);

		$this->add_input_field(
			'cache_time',
			__('Reports Cache Time (minute)', 'buddyboss'),
			[
				'input_type'        => 'number',
				'input_description' => __( 'Recommanded. Reports are cached to have better performance and less server load. Here you can adjust how long the cache lives. Organizers and Moderator can refresh report at anytime. Set this to 0 to disable cache.', 'buddyboss' ),
				'class' => 'js-show-on-reports_enabled'
			]
		);
	}

	public function buddypress_groups_sync_description() {
		echo wpautop(
			__( 'Control the setting for Social Group\'s syncing on create or update.', 'buddyboss' )
		);
	}

	public function learndash_groups_sync_description() {
		echo wpautop(
			__( 'Control the setting for Learndash Group\'s syncing on create or update.', 'buddyboss' )
		);
	}

	public function learndash_groups_report_description() {
		echo wpautop(
			__( 'Control the setting for social group\'s reports.', 'buddyboss' )
		);
	}

	public function output_report_access_setting() {
		$input_field = 'report_access';
		$input_value = $this->get_input_value( $input_field, [] );
		$input_name = $this->get_input_name( $input_field );
		$input_options = [
			'admin'     => __( 'Organizers', 'buddyboss' ),
			'moderator' => __( 'Moderators', 'buddyboss' ),
			'member'    => __( 'Members', 'buddyboss' )
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

		echo $this->render_input_description(__('Allowed roles to view the group reports...', 'buddyboss'));
	}

	public function add_sync_tool_scripts() {
		printf(
			'<script src="%s"></script>',
			add_query_arg(
				'ver',
				filemtime( $this->root_path . '/groups-sync/assets/js/admin/bp_learndash_groups_sync-settings.js' ),
				$this->root_url . '/groups-sync/assets/js/admin/bp_learndash_groups_sync-settings.js'
			)
		);
	}

	protected function get_input_name( $name ) {
		return bp_ld_sync('settings')->getName("{$this->current_section}.{$name}");
		// $option_key = "{$this->current_section}_option_key";
		// return "{$this->$option_key}[{$name}]";
	}

	protected function get_input_value( $key, $default = '' ) {
		return bp_ld_sync('settings')->get("{$this->current_section}.{$key}", $default);
		// $options = "{$this->current_section}_options";
		// return isset($this->$options[$key]) ? $this->$options[$key] : $default;
	}
}

