<?php
/**
 * BuddyBoss ReadyLaunch Onboarding
 *
 * @package BuddyBoss\Core\Administration
 * @subpackage ReadyLaunchOnboarding
 * @since   BuddyBoss 2.10.0
 * @author  BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * ReadyLaunch Onboarding Class
 *
 * Handles the onboarding modal for first-time BuddyBoss Platform activation.
 * Extends the base Setup Wizard Manager to provide ReadyLaunch-specific functionality.
 *
 * @since BuddyBoss 2.10.0
 */
class BB_ReadyLaunch_Onboarding extends BB_Setup_Wizard_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss 2.10.0
	 * @var   BB_ReadyLaunch_Onboarding|null
	 */
	private static $instance = null;

	/**
	 * Ensures only one instance of BB_ReadyLaunch_Onboarding is loaded or can be loaded.
	 *
	 * @since  BuddyBoss 2.10.0
	 * @static
	 * @return BB_ReadyLaunch_Onboarding Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since BuddyBoss 2.10.0
	 */
	private function __construct() {
		// Build configuration array with ReadyLaunch-specific settings.
		$config = array(
			'admin_page'            => 'bp-components',
			'option_prefix'         => 'bb_rl',
			'completion_option'     => 'bb_rl_onboarding_completed',
			'wizard_title'          => __( 'BuddyBoss ReadyLaunch Setup', 'buddyboss' ),
			'wizard_description'    => __( 'Get started with BuddyBoss in minutes', 'buddyboss' ),
			'skip_on_multisite'     => false,
			'enable_react_frontend' => true,
			'react_directory'       => __DIR__,
			'react_script_handle'   => 'bb-rl-onboarding-script',
			'react_style_handle'    => 'bb-rl-onboarding-style',
			'react_script_name'     => 'rl-onboarding',
			'react_style_name'      => 'onboarding',
			'react_localize_object' => 'bbRlOnboarding',
			'steps'                 => array(
				0 => array(
					'key'           => 'splash',
					'title'         => __( 'Welcome to BuddyBoss', 'buddyboss' ),
					'description'   => __( 'Let\'s bring your community to life by choose the look and feel that matches your vision.', 'buddyboss' ),
					'component'     => 'SplashScreen',
					'image'         => 'onboardingModal-splash.png',
					'skip_progress' => true,
				),
				1 => array(
					'key'         => 'community_setup',
					'title'       => __( 'Site Name', 'buddyboss' ),
					'description' => __( 'This matches the WordPress Site Title. Updating it here will update it site-wide.', 'buddyboss' ),
					'component'   => 'CommunitySetupStep',
					'image'       => 'onboardingModal-step-1.png',
				),
				2 => array(
					'key'         => 'site_appearance',
					'title'       => __( 'Site Appearance', 'buddyboss' ),
					'description' => __( 'Set your community appearance to light, dark, or both.', 'buddyboss' ),
					'component'   => 'SiteAppearanceStep',
					'image'       => 'onboardingModal-step-2.png',
				),
				3 => array(
					'key'         => 'brandings',
					'title'       => __( 'Branding', 'buddyboss' ),
					'description' => __( 'Personalize your community with logos and theme colors.', 'buddyboss' ),
					'component'   => 'BrandingsStep',
					'image'       => 'onboardingModal-step-3.png',
				),
				4 => array(
					'key'         => 'pages',
					'title'       => __( 'Pages', 'buddyboss' ),
					'description' => __( 'Select pages that should have styles from ReadyLaunch.', 'buddyboss' ),
					'component'   => 'PagesStep',
					'image'       => 'onboardingModal-step-4.png',
				),
				5 => array(
					'key'         => 'side_menus',
					'title'       => __( 'Side Menus', 'buddyboss' ),
					'description' => __( 'Enable the options to appear in the left-side menu.', 'buddyboss' ),
					'component'   => 'SideMenusStep',
					'image'       => 'onboardingModal-step-5.png',
				),
				6 => array(
					'key'         => 'widgets',
					'title'       => __( 'Sidebar Widgets', 'buddyboss' ),
					'description' => __( 'Enable or disable sidebar widgets on different community pages.', 'buddyboss' ),
					'component'   => 'WidgetsStep',
					'image'       => 'onboardingModal-step-6.png',
				),
				7 => array(
					'key'         => 'finish',
					'title'       => __( 'You\'re All Set!', 'buddyboss' ),
					'description' => __( 'Your community is ready to connect, share, and grow together.', 'buddyboss' ),
					'component'   => 'FinishScreen',
					'image'       => 'onboardingModal-finish.png',
				),
			),
			'step_options'          => array(
				'community_setup' => array(
					'blogname' => array(
						'type'        => 'text',
						'description' => __( 'This matches the WordPress Site Title. Updating it here will update it site-wide.', 'buddyboss' ),
						'required'    => true,
						'value'       => get_bloginfo( 'name' ),
					),
				),
				'site_appearance' => array(
					'bb_rl_theme_mode' => array(
						'type'    => 'visual_radio_options',
						'options' => array(
							'light'  => array(
								'label'       => __( 'Light Mode', 'buddyboss' ),
								'description' => __( 'The site will be shown in light mode.', 'buddyboss' ),
								'icon_class'  => 'bb-icons-rl-sun',
							),
							'dark'   => array(
								'label'       => __( 'Dark Mode', 'buddyboss' ),
								'description' => __( 'The site will be shown in dark mode.', 'buddyboss' ),
								'icon_class'  => 'bb-icons-rl-moon',
							),
							'choice' => array(
								'label'       => __( 'Both', 'buddyboss' ),
								'description' => __( 'Users can switch between modes.', 'buddyboss' ),
								'icon_class'  => 'bb-icons-rl-circle-half',
							),
						),
						'default' => 'light',
					),
				),
				'brandings'       => array(
					'bb_rl_light_logo'        => array(
						'type'        => 'media',
						'label'       => __( 'Logo (Light mode)', 'buddyboss' ),
						'customClass' => 'bb-rl-light-logo',
						'conditional' => array(
							'dependsOn' => 'bb_rl_theme_mode',
							'value'     => 'dark',
							'operator'  => '!==', // Show when NOT dark mode.
						),
					),
					'bb_rl_dark_logo'         => array(
						'type'        => 'media',
						'label'       => __( 'Logo (Dark mode)', 'buddyboss' ),
						'customClass' => 'bb-rl-dark-logo',
						'conditional' => array(
							'dependsOn' => 'bb_rl_theme_mode',
							'value'     => 'light',
							'operator'  => '!==', // Show when NOT light mode.
						),
					),
					'logo_description_light'  => array(
						'type'        => 'description',
						'description' => __( 'Recommended to upload a dark-colored logo for light mode, 280x80 px, in JPG or PNG format.', 'buddyboss' ),
						'conditional' => array(
							'dependsOn' => 'bb_rl_theme_mode',
							'value'     => 'light',
							'operator'  => '===',
						),
					),
					'logo_description_dark'   => array(
						'type'        => 'description',
						'description' => __( 'Recommended to upload a light-colored logo for dark mode, 280x80 px, in JPG or PNG format.', 'buddyboss' ),
						'conditional' => array(
							'dependsOn' => 'bb_rl_theme_mode',
							'value'     => 'dark',
							'operator'  => '===',
						),
					),
					'logo_description_choice' => array(
						'type'        => 'description',
						'description' => __( 'Recommended to upload a light-colored logo for dark mode and a dark-colored logo for light mode, 280x80 px, in JPG or PNG format.', 'buddyboss' ),
						'conditional' => array(
							'dependsOn' => 'bb_rl_theme_mode',
							'value'     => 'choice',
							'operator'  => '===',
						),
					),
					'logo_color_separator'    => array(
						'type' => 'hr',
					),
					'bb_rl_color_light'       => array(
						'type'        => 'color',
						'label'       => __( 'Primary Color (Light mode)', 'buddyboss' ),
						'default'     => '#3E34FF',
						'conditional' => array(
							'dependsOn' => 'bb_rl_theme_mode',
							'value'     => 'dark',
							'operator'  => '!==', // Show when NOT dark mode.
						),
					),
					'bb_rl_color_dark'        => array(
						'type'        => 'color',
						'label'       => __( 'Primary Color (Dark mode)', 'buddyboss' ),
						'default'     => '#A347FF',
						'conditional' => array(
							'dependsOn' => 'bb_rl_theme_mode',
							'value'     => 'light',
							'operator'  => '!==', // Show when NOT light mode.
						),
					),
					'color_description'       => array(
						'type'        => 'description',
						'description' => __( 'Primary color used for buttons, links, and interactive elements.', 'buddyboss' ),
					),
				),
				'pages'           => array(
					'bb_rl_enabled_pages' => array(
						'type'    => 'checkbox_group',
						'options' => $this->get_enabled_pages_options(),
					),
				),
				'side_menus'      => array(
					'bb_rl_side_menu'    => array(
						'type'    => 'draggable',
						'label'   => __( 'Navigation', 'buddyboss' ),
						'options' => $this->getComponentMenuItems(),
					),
					'bb_rl_custom_links' => array(
						'type'    => 'draggable_links',
						'label'   => __( 'Link', 'buddyboss' ),
						'options' => array(),
					),
				),
				'widgets'         => array(
					'bb_rl_activity_sidebars'       => array(
						'type'    => 'checkbox_group',
						'label'   => __( 'Activity Feed', 'buddyboss' ),
						'options' => array(
							'complete_profile'  => array(
								'label'   => __( 'Complete Profile', 'buddyboss' ),
								'default' => true,
							),
							'latest_updates'    => array(
								'label'   => __( 'Latest Updates', 'buddyboss' ),
								'default' => true,
							),
							'recent_blog_posts' => array(
								'label'   => __( 'Recent Blog Posts', 'buddyboss' ),
								'default' => true,
							),
							'active_members'    => array(
								'label'   => __( 'Active Members', 'buddyboss' ),
								'default' => true,
							),

						),
					),
					'bb_rl_member_profile_sidebars' => array(
						'type'    => 'checkbox_group',
						'label'   => __( 'Member Profile', 'buddyboss' ),
						'options' => array(
							'complete_profile' => array(
								'label'   => __( 'Complete Profile', 'buddyboss' ),
								'default' => true,
							),
							'connections'      => array(
								'label'   => __( 'Connections', 'buddyboss' ),
								'default' => true,
							),
							'my_network'       => array(
								'label'   => __( 'Network (Follow, Followers)', 'buddyboss' ),
								'default' => true,
							),

						),
					),
					'bb_rl_groups_sidebars'         => array(
						'type'    => 'checkbox_group',
						'label'   => __( 'Group', 'buddyboss' ),
						'options' => array(
							'about_group'   => array(
								'label'   => __( 'About Group', 'buddyboss' ),
								'default' => true,
							),
							'group_members' => array(
								'label'   => __( 'Group Members', 'buddyboss' ),
								'default' => true,
							),
						),
					),
				),
			),
			'react_assets'          => array(
				'logo'                  => buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/assets/bb-logo.png',
				'assetsBaseUrl'         => buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/assets/',
				'buddybossThemePreview' => buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/assets/buddyboss-theme-preview.svg',
				'currentThemePreview'   => buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/assets/current-theme-preview.svg',
			),
			'react_dependencies'    => array( 'react', 'wp-components', 'wp-element', 'wp-i18n' ),
			'custom_hooks'          => array(
				'completion'      => array(),
				'step_completion' => array(
					'bb_rl_step_completed',
				),
				'analytics'       => array(
					'bb_rl_analytics_event',
				),
			),
		);

		// Initialise parent class with configuration.
		parent::__construct( $config );
	}

	/**
	 * Initialise the ReadyLaunch onboarding wizard.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	protected function init() {
		$this->wizard_id      = 'rl_onboarding';
		$this->wizard_name    = __( 'ReadyLaunch Onboarding', 'buddyboss' );
		$this->wizard_version = '1.0.0';
		$this->assets_dir     = __DIR__ . '/assets/';
		$this->assets_url     = buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/assets/';

		// Initialise steps from configuration.
		$this->steps = $this->get_config( 'steps', array() );

		// Set current step.
		$this->current_step = 0;

		// Add ReadyLaunch specific hooks.
		$this->init_readylaunch_hooks();
	}

	/**
	 * Initialise ReadyLaunch specific hooks and filters
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	private function init_readylaunch_hooks() {
		// ReadyLaunch specific hooks can be added here if needed in the future.
	}

	/**
	 * Check if onboarding should be shown.
	 *
	 * Uses the existing BP activation mechanism with the bb_wizard_activation URL parameter.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return bool True if onboarding should be shown, false otherwise.
	 */
	public function should_show() {
		// Check if ReadyLaunch onboarding transient is set (primary method).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$activation_param = ! empty( $_GET['bb_wizard_activation'] ) ? sanitize_text_field( wp_unslash( $_GET['bb_wizard_activation'] ) ) : '';
		$is_new_install   = isset( $_GET['is_new_install'] ) && '1' === $_GET['is_new_install']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$new_activate     = $this->get_config( 'enable_new_activation' );

		$show_onboarding = (
				! empty( $new_activate ) &&
				$activation_param === $this->wizard_id &&
				$is_new_install
			) || (
				empty( $new_activate ) &&
				$activation_param === $this->wizard_id
			);

		// Check if onboarding was already completed.
		$onboarding_completed = $this->is_completed();

		// Show onboarding if transient is set and hasn't been completed yet.
		return $show_onboarding && ! $onboarding_completed;
	}

	/**
	 * Enqueue wizard-specific assets.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	protected function enqueue_wizard_assets() {
		// Enqueue WordPress media library for image uploads.
		wp_enqueue_media();

		$asset_file = __DIR__ . '/build/rl-onboarding.asset.php';
		$asset_data = file_exists( $asset_file ) ? include $asset_file : array(
			'dependencies' => $this->get_config( 'react_dependencies', array( 'react', 'wp-components', 'wp-element', 'wp-i18n' ) ),
			'version'      => $this->wizard_version,
		);

		$min = bp_core_get_minified_asset_suffix();
		$rtl = is_rtl() ? '-rtl' : '';

		// Enqueue the React script.
		wp_enqueue_script(
			$this->get_config( 'react_script_handle' ),
			buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/build/rl-onboarding.js',
			$asset_data['dependencies'],
			$asset_data['version'],
			true
		);

		// Enqueue the CSS.
		wp_enqueue_style(
			$this->get_config( 'react_style_handle' ),
			buddypress()->plugin_url . "bp-core/admin/bb-settings/rl-onboarding/build/onboarding{$rtl}.css",
			array(),
			$asset_data['version']
		);

		// Enqueue the BB Icons CSS.
		wp_enqueue_style(
			'bb-icons-rl-css',
			buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css",
			array(),
			$asset_data['version']
		);
	}

	/**
	 * Localise wizard data for JavaScript.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return array Localised data array.
	 */
	protected function localize_wizard_data() {
		$base_data = array(
			'shouldShow'   => $this->should_show(),
			'completed'    => $this->is_completed(),
			'assets'       => $this->get_wizard_assets(),
			'steps'        => $this->steps,
			'stepOptions'  => $this->get_config( 'step_options', array() ),
			'progress'     => $this->get_progress(),
			'preferences'  => $this->get_preferences(),
			'wizardId'     => $this->wizard_id,
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'dashboardUrl' => admin_url(),
			'nonce'        => wp_create_nonce( $this->wizard_id . '_wizard_nonce' ),
			'translations' => array(),
			'actions'      => array(
				'shouldShow'      => $this->wizard_id . '_should_show',
				'saveProgress'    => $this->wizard_id . '_save_step_progress',
				'complete'        => $this->wizard_id . '_complete',
				'savePreferences' => $this->wizard_id . '_save_preferences',
				'getWizardData'   => $this->wizard_id . '_get_wizard_data',
			),
			'readylaunch'  => array(
				'current_theme'             => wp_get_theme()->get( 'Name' ),
				'theme_settings'            => esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'buddyboss_theme_options' ), 'admin.php' ) ) ),
				'themes'                    => esc_url( bp_get_admin_url( 'themes.php' ) ),
				'is_buddyboss_theme_active' => get_template() === 'buddyboss-theme',
				'buddyboss_theme_installed' => wp_get_theme( 'buddyboss-theme' )->exists(),
				'site_url'                  => home_url(),
				'admin_url'                 => admin_url(),
			),
		);

		/**
		 * Filter the localised data for ReadyLaunch onboarding
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param array $base_data The base localised data.
		 */
		return apply_filters( 'bb_rl_onboarding_localize_data', $base_data );
	}

	/**
	 * Get wizard assets.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return array
	 */
	protected function get_wizard_assets() {
		return array_merge(
			array(
				'logo'      => buddypress()->plugin_url . 'bp-core/images/bb-icon.svg',
				'assetsUrl' => $this->assets_url,
			),
			$this->get_config( 'react_assets', array() )
		);
	}

	/**
	 * AJAX handler to complete the wizard.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	public function ajax_complete() {
		// Verify nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->wizard_id . '_wizard_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'buddyboss' ),
				)
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( $this->get_config( 'capability_required' ) ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have sufficient permissions to perform this action.', 'buddyboss' ),
				)
			);
		}

		// Get all onboarding configuration data.
		$completion_data = array(
			'completed_at' => current_time( 'mysql' ),
			'total_steps'  => count( $this->get_config( 'steps', array() ) ),
		);

		// Mark wizard as completed.
		$result = $this->mark_completed( $completion_data );

		if ( $result ) {
			// Save ReadyLaunch enabled option.
			$this->save_readylaunch_option( 'bb_rl_enabled', true );

			// Mark step 7 (finish) as completed in step tracking.
			$this->save_step_tracking_for_completion( 7 );

			// Mark progress as completed.
			$this->mark_progress_as_completed();

			// Send analytics events for completion.
			$this->send_completion_analytics( $completion_data );

			// Clean up ReadyLaunch specific transients.
			delete_transient( '_bb_rl_show_onboarding' );

			/**
			 * Fires after ReadyLaunch onboarding is completed.
			 *
			 * @since BuddyBoss 2.10.0
			 */
			do_action( 'bb_rl_onboarding_completed' );

			wp_send_json_success(
				array(
					'message' => __( 'ReadyLaunch setup completed successfully!', 'buddyboss' ),
					'data'    => $completion_data,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to complete setup. Please try again.', 'buddyboss' ),
				)
			);
		}
	}

	/**
	 * Sanitise final settings from the onboarding form.
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param array $settings Raw settings data.
	 * @return array Sanitised settings.
	 */
	private function sanitize_final_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		$sanitized = array();

		// Get step options configuration to validate field types.
		$step_options = $this->get_config( 'step_options', array() );

		// Process all step data and extract field values.
		foreach ( $settings as $step_key => $step_data ) {
			if ( ! is_array( $step_data ) || ! isset( $step_options[ $step_key ] ) ) {
				continue;
			}

			foreach ( $step_data as $field_key => $field_value ) {
				if ( ! isset( $step_options[ $step_key ][ $field_key ] ) ) {
					continue;
				}

				$field_config = $step_options[ $step_key ][ $field_key ];
				$field_type   = $field_config['type'] ?? 'text';

				// Sanitize based on field type and save directly using field key.
				switch ( $field_type ) {
					case 'select':
					case 'radio':
					case 'visual_options':
					case 'visual_radio_options':
						$allowed_values          = isset( $field_config['options'] ) ? array_keys( $field_config['options'] ) : array();
						$sanitized[ $field_key ] = in_array( $field_value, $allowed_values, true ) ? $field_value : ( $field_config['default'] ?? '' );
						break;

					case 'checkbox_group':
						if ( is_array( $field_value ) ) {
							// Ensure all values are sanitised and valid.
							$allowed_values          = isset( $field_config['options'] ) ? array_keys( $field_config['options'] ) : array();
							$sanitized[ $field_key ] = array_intersect( $field_value, $allowed_values );
						} else {
							// If not an array, default to an empty array.
							$sanitized[ $field_key ] = array();
						}
						break;

					case 'checkbox':
						$sanitized[ $field_key ] = ! empty( $field_value );
						break;

					case 'color':
						$sanitized[ $field_key ] = sanitize_hex_color( $field_value ) ? sanitize_hex_color( $field_value ) : ( $field_config['default'] ?? '#e57e3a' );
						break;

					case 'media':
						if ( is_array( $field_value ) && isset( $field_value['id'] ) ) {
							// New format: complete image object with id, url, alt, etc.
							$sanitized[ $field_key ] = array(
								'id'    => intval( $field_value['id'] ),
								'url'   => isset( $field_value['url'] ) ? esc_url_raw( $field_value['url'] ) : '',
								'alt'   => isset( $field_value['alt'] ) ? sanitize_text_field( $field_value['alt'] ) : '',
								'title' => isset( $field_value['title'] ) ? sanitize_text_field( $field_value['title'] ) : '',
							);
						} elseif ( is_numeric( $field_value ) ) {
							// Legacy format: just the ID.
							$sanitized[ $field_key ] = intval( $field_value );
							// Also, save the URL if provided.
							if ( isset( $step_data[ $field_key . '_url' ] ) ) {
								$sanitized[ $field_key . '_url' ] = esc_url_raw( $step_data[ $field_key . '_url' ] );
							}
						} else {
							// Invalid or empty value.
							$sanitized[ $field_key ] = null;
						}
						break;

					case 'draggable':
					case 'draggable_links':
						if ( is_array( $field_value ) ) {
							$sanitized_items = array();
							foreach ( $field_value as $item ) {
								if ( is_array( $item ) ) {
									if ( 'draggable_links' === $field_type ) {
										// Sanitize link items.
										$sanitized_items[] = array(
											'id'        => isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '',
											'title'     => isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '',
											'url'       => isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '',
											'isEditing' => false, // Always set to false for safety.
										);
									} else {
										// Sanitize draggable menu items.
										$sanitized_items[] = array(
											'id'      => isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '',
											'label'   => isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '',
											'icon'    => isset( $item['icon'] ) ? sanitize_text_field( $item['icon'] ) : '',
											'enabled' => isset( $item['enabled'] ) ? (bool) $item['enabled'] : true,
											'order'   => isset( $item['order'] ) ? intval( $item['order'] ) : 0,
										);
									}
								}
							}
							$sanitized[ $field_key ] = $sanitized_items;

							// For some draggable fields, ReadyLaunch expects a specific structure.
							switch ( $field_key ) {
								case 'bb_rl_side_menu':
									// Convert sequential list into associative map id => {enabled, order, icon}.
									$menu_map = array();
									foreach ( $sanitized_items as $menu_item ) {
										if ( empty( $menu_item['id'] ) ) {
											continue;
										}
										$menu_map[ $menu_item['id'] ] = array(
											'enabled' => isset( $menu_item['enabled'] ) ? (bool) $menu_item['enabled'] : true,
											'order'   => isset( $menu_item['order'] ) ? (int) $menu_item['order'] : 0,
											'icon'    => isset( $menu_item['icon'] ) ? $menu_item['icon'] : '',
										);
									}
									$sanitized[ $field_key ] = $menu_map;
									break;

								case 'bb_rl_activity_sidebars':
								case 'bb_rl_member_profile_sidebars':
								case 'bb_rl_groups_sidebars':
									// Convert list into boolean map id => enabled.
									$sidebar_map = array();
									foreach ( $sanitized_items as $sidebar_item ) {
										if ( empty( $sidebar_item['id'] ) ) {
											continue;
										}
										$sidebar_map[ $sidebar_item['id'] ] = isset( $sidebar_item['enabled'] ) ? (bool) $sidebar_item['enabled'] : true;
									}
									$sanitized[ $field_key ] = $sidebar_map;
									break;

								default:
									$sanitized[ $field_key ] = $sanitized_items;
									break;
							}
						} else {
							// Fall back to default if not an array.
							$sanitized[ $field_key ] = isset( $field_config['options'] ) ? $field_config['options'] : array();
						}
						break;

					default:
						$sanitized[ $field_key ] = sanitize_text_field( wp_unslash( $field_value ) );
						break;
				}
			}
		}

		// Sanitize pages settings.
		if ( isset( $settings['pages'] ) ) {
			$pages_settings = $settings['pages'];

			// Handle the new checkbox_group: bb_rl_enabled_pages.
			if ( isset( $pages_settings['bb_rl_enabled_pages'] ) && is_array( $pages_settings['bb_rl_enabled_pages'] ) ) {
				$selected_pages                   = $pages_settings['bb_rl_enabled_pages'];
				$sanitized['bb_rl_enabled_pages'] = array(
					'registration' => in_array( 'registration', $selected_pages, true ),
					'courses'      => in_array( 'courses', $selected_pages, true ),
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Override sanitize_preferences to handle step-based field structure.
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $preferences_json JSON string of preferences.
	 * @return array Sanitised preferences.
	 */
	protected function sanitize_preferences( $preferences_json ) {
		$preferences = json_decode( $preferences_json, true );

		if ( ! is_array( $preferences ) ) {
			return array();
		}

		return $this->sanitize_final_settings( $preferences );
	}

	/**
	 * Override save_preferences to apply settings immediately.
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param array  $preferences User preferences.
	 * @param string $pref_key    Optional preference key to save under.
	 *
	 * @return void
	 */
	public function save_preferences( $preferences, $pref_key = '' ) {
		// Save to parent preferences system first.
		parent::save_preferences( $preferences, $pref_key );

		// Flatten & sanitise the data so it matches the structure expected by
		// apply_readylaunch_configuration(). This ensures that when auto-save
		// fires from an individual step (e.g. theme mode), the option is
		// persisted immediately.
		$sanitised = $this->sanitize_final_settings( $preferences );

		// Apply the configuration immediately for real-time updates.
		$this->apply_readylaunch_configuration( $sanitised );
	}

	/**
	 * Apply ReadyLaunch configuration based on final settings.
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param array $final_settings The final configuration settings.
	 * @return void
	 */
	private function apply_readylaunch_configuration( $final_settings ) {
		if ( empty( $final_settings ) ) {
			return;
		}

		// Apply community setup - blogname field.
		if ( ! empty( $final_settings['blogname'] ) ) {
			update_option( 'blogname', $final_settings['blogname'] );
		}

		// Apply theme mode setting.
		if ( ! empty( $final_settings['bb_rl_theme_mode'] ) ) {
			// Save theme mode preference.
			$this->save_readylaunch_option( 'theme_mode', $final_settings['bb_rl_theme_mode'] );
		}

		// Apply branding settings - logos.
		if ( ! empty( $final_settings['bb_rl_light_logo'] ) ) {
			$this->save_readylaunch_option( 'light_logo', $final_settings['bb_rl_light_logo'] );
		}

		if ( ! empty( $final_settings['bb_rl_dark_logo'] ) ) {
			$this->save_readylaunch_option( 'dark_logo', $final_settings['bb_rl_dark_logo'] );
		}

		// Force enabled registration when enabled from settings.
		if ( ! empty( $final_settings['bb_rl_enabled_pages'] ) ) {
			$pages = $final_settings['bb_rl_enabled_pages'];
			if (
				! empty( $pages['registration'] ) &&
				! bp_enable_site_registration( false ) &&
				! bp_allow_custom_registration()
			) {
				// Enable registration page.
				bp_update_option( 'bp-enable-site-registration', true );
				bp_update_option( 'allow-custom-registration', 0 );
			}
		}

		$component_activated = false;

		// Force enabled components based on side menu settings.
		if ( ! empty( $final_settings['bb_rl_side_menu'] ) ) {
			$side_menu = $final_settings['bb_rl_side_menu'];

			// Enable or disable BuddyBoss components based on the side menu toggle.
			$component_map = array(
				'activity_feed' => 'activity',
				'groups'        => 'groups',
				'forums'        => 'forums',
				'messages'      => 'messages',
				'notifications' => 'notifications',
			);

			$active_components = bp_get_option( 'bp-active-components', array() );

			foreach ( $component_map as $menu_id => $component_key ) {
				if ( isset( $side_menu[ $menu_id ] ) ) {
					$enabled = ! empty( $side_menu[ $menu_id ]['enabled'] );
					if ( $enabled && empty( $active_components[ $component_key ] ) ) {
						$active_components[ $component_key ] = 1;
						$component_activated                 = true;
					}
				}
			}

			if ( $component_activated ) {
				bp_update_option( 'bp-active-components', $active_components );
			}
		}

		$component_map = array();
		if ( ! empty( $final_settings['bb_rl_activity_sidebars'] ) ) {
			$component_map[] = 'activity';
		}

		if ( ! empty( $final_settings['bb_rl_groups_sidebars'] ) ) {
			$component_map[] = 'groups';
		}

		if (
			! empty( $final_settings['bb_rl_member_profile_sidebars'] )
		) {
			if ( in_array( 'my_network', $final_settings['bb_rl_member_profile_sidebars'], true ) ) {
				$component_map[] = 'activity';
			}

			if ( in_array( 'connections', $final_settings['bb_rl_member_profile_sidebars'], true ) ) {
				$component_map[] = 'friends';
			}

			bp_update_option( '_bp_enable_activity_follow', true );
		}

		if ( ! empty( array_unique( $component_map ) ) ) {
			$component_map     = array_unique( $component_map );
			$active_components = bp_get_option( 'bp-active-components', array() );

			foreach ( $component_map as $k => $component_key ) {
				if ( empty( $active_components[ $component_key ] ) ) {
					$active_components[ $component_key ] = 1;
					$component_activated                 = true;
				}
			}

			if ( $component_activated ) {
				bp_update_option( 'bp-active-components', $active_components );
			}
		}

		if ( true === $component_activated ) {
			// Save settings and upgrade schema.
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			require_once buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';

			bp_core_install();
		}

		// Apply remaining step settings dynamically.
		$this->apply_remaining_step_settings( $final_settings );

		/**
		 * Fires after ReadyLaunch configuration has been applied.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param array $final_settings The final configuration settings.
		 */
		do_action( 'bb_rl_configuration_applied', $final_settings );
	}

	/**
	 * Create essential pages (Privacy Policy, Terms of Service, About).
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	private function create_essential_pages() {
		$pages_to_create = array(
			'privacy_policy'   => array(
				'title'   => __( 'Privacy Policy', 'buddyboss' ),
				'content' => __( 'Your privacy policy content goes here.', 'buddyboss' ),
			),
			'terms_of_service' => array(
				'title'   => __( 'Terms of Service', 'buddyboss' ),
				'content' => __( 'Your terms of service content goes here.', 'buddyboss' ),
			),
			'about'            => array(
				'title'   => __( 'About Us', 'buddyboss' ),
				'content' => __( 'Information about your community goes here.', 'buddyboss' ),
			),
		);

		foreach ( $pages_to_create as $page_slug => $page_data ) {
			// Check if page already exists.
			$existing_page = get_page_by_path( $page_slug );

			if ( ! $existing_page ) {
				$page_id = wp_insert_post(
					array(
						'post_title'   => $page_data['title'],
						'post_content' => $page_data['content'],
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'post_name'    => $page_slug,
					)
				);

				// Set as privacy policy page if applicable.
				if ( 'privacy_policy' === $page_slug && $page_id ) {
					update_option( 'wp_page_for_privacy_policy', $page_id );
				}
			}
		}
	}

	/**
	 * Save ReadyLaunch specific option.
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param string $option_name The option name.
	 * @param mixed  $option_value The option value.
	 * @return void
	 */
	private function save_readylaunch_option( $option_name, $option_value ) {
		if ( empty( $option_name ) || ! is_string( $option_name ) ) {
			return; // Invalid option name.
		}

		if ( strpos( $option_name, 'bb_rl_' ) === 0 ) {
			// Ensure the option name starts with bb_rl_ prefix.
			$option_name = substr( $option_name, 6 );
		}

		// Save as BuddyPress option with bb_rl_ prefix.
		bp_update_option( 'bb_rl_' . $option_name, $option_value );
	}

	/**
	 * Apply remaining step settings that weren't handled specifically.
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param array $final_settings The final configuration settings.
	 * @return void
	 */
	private function apply_remaining_step_settings( $final_settings ) {
		$step_options = $this->get_config( 'step_options', array() );

		// Get list of fields that were already handled above.
		$handled_fields = array(
			'blogname',
		);

		// Process any remaining fields.
		foreach ( $final_settings as $field_key => $field_value ) {
			// Skip if already handled or if it's a non-interactive field.
			if ( in_array( $field_key, $handled_fields, true ) ) {
				continue;
			}

			// Find the field config to determine how to save it.
			$field_config = null;
			foreach ( $step_options as $step_fields ) {
				if ( isset( $step_fields[ $field_key ] ) ) {
					$field_config = $step_fields[ $field_key ];
					break;
				}
			}

			// Skip non-interactive fields.
			if ( ! $field_config || in_array( $field_config['type'] ?? '', array( 'description', 'hr' ), true ) ) {
				continue;
			}

			// Save as ReadyLaunch option.
			$this->save_readylaunch_option( $field_key, $field_value );
		}
	}

	/**
	 * Get the enabled pages options.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return array
	 */
	private function get_enabled_pages_options() {
		$retval = array(
			'registration' => array(
				'label'   => __( 'Login & Registration', 'buddyboss' ),
				'icon'    => 'bb-icons-rl-file-text',
				'default' => true,
			),
		);

		if ( bb_load_readylaunch()->bb_is_sidebar_enabled_for_courses() ) {
			$retval['courses'] = array(
				'label'   => __( 'Courses', 'buddyboss' ),
				'icon'    => 'bb-icons-rl-file-text',
				'default' => true,
			);
		} else {
			$retval['courses'] = array(
				'label'         => __( 'Courses', 'buddyboss' ),
				'icon'          => 'bb-icons-rl-file-text',
				'default'       => false,
				'not_available' => true,
				'notice'        => __( 'Requires LearnDash or MemberPress courses to activate.', 'buddyboss' ),
			);
		}

		return $retval;
	}

	/**
	 * Get the default component menu items.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return array
	 */
	private function getComponentMenuItems() {
		$items = array(
			array(
				'id'      => 'activity_feed',
				'label'   => __( 'Activity Feed', 'buddyboss' ),
				'icon'    => 'pulse',
				'enabled' => true,
				'order'   => 0,
			),
			array(
				'id'      => 'members',
				'label'   => __( 'Members', 'buddyboss' ),
				'icon'    => 'users',
				'enabled' => true,
				'order'   => 1,
			),
			array(
				'id'      => 'groups',
				'label'   => __( 'Groups', 'buddyboss' ),
				'icon'    => 'users-three',
				'enabled' => true,
				'order'   => 2,
			),
		);

		$current_order = 3;

		if ( bb_load_readylaunch()->bb_is_sidebar_enabled_for_courses() ) {
			$items[] = array(
				'id'      => 'courses',
				'label'   => __( 'Courses', 'buddyboss' ),
				'icon'    => 'graduation-cap',
				'enabled' => true,
				'order'   => $current_order++,
			);
		}

		$items[] = array(
			'id'      => 'forums',
			'label'   => __( 'Forums', 'buddyboss' ),
			'icon'    => 'chat-text',
			'enabled' => true,
			'order'   => $current_order++,
		);

		$items[] = array(
			'id'      => 'messages',
			'label'   => __( 'Messages', 'buddyboss' ),
			'icon'    => 'chat-teardrop-text',
			'enabled' => true,
			'order'   => $current_order++,
		);

		$items[] = array(
			'id'      => 'notifications',
			'label'   => __( 'Notifications', 'buddyboss' ),
			'icon'    => 'bell',
			'enabled' => true,
			'order'   => $current_order,
		);

		return $items;
	}

	/**
	 * Save step tracking for completion (step 7 - finish).
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param int $step Step number (7 for finish step).
	 * @return void
	 */
	private function save_step_tracking_for_completion( $step ) {
		// Use the base class method to save step progress with completion data.
		$step_data = array(
			'step_key'     => 'finish',
			'status'       => 'completed',
			'completed_at' => current_time( 'mysql' ),
		);

		$this->save_step_progress( $step, $step_data );
	}

	/**
	 * Mark progress as completed for ReadyLaunch onboarding.
	 *
	 * @since BuddyBoss 2.10.0
	 * @return void
	 */
	private function mark_progress_as_completed() {
		// Save step 7 progress which will automatically update the overall progress
		// to completed status since it's the final step.
		$step_data = array(
			'step_key'     => 'finish',
			'status'       => 'completed',
			'completed_at' => current_time( 'mysql' ),
		);

		$this->save_step_progress( 7, $step_data );
	}

	/**
	 * Send analytics events for ReadyLaunch onboarding completion.
	 *
	 * @since BuddyBoss 2.10.0
	 *
	 * @param array $completion_data Completion data.
	 * @return void
	 */
	private function send_completion_analytics( $completion_data ) {
		// Send completion analytics event if analytics is enabled.
		if ( $this->config['enable_analytics'] ) {
			// Trigger analytics by calling save_step_progress which will automatically
			// send telemetry events through the base class infrastructure.
			$step_data = array(
				'step_key'     => 'finish',
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql' ),
				'analytics'    => true, // Flag to indicate this is for analytics.
			);

			// This will trigger the base class analytics events.
			$this->save_step_progress( 7, $step_data );

			// Send ReadyLaunch specific analytics through BB_Telemetry if available.
			if ( class_exists( 'BB_Telemetry' ) ) {
				$telemetry_instance = BB_Telemetry::instance();
				if ( $telemetry_instance ) {
					// Add ReadyLaunch completion data to telemetry.
					add_filter(
						'bb_telemetry_platform_options',
						function ( $options ) {
							$options[] = 'bb_rl_onboarding_completed';
							$options[] = 'bb_rl_enabled';
							return $options;
						}
					);

					// Force immediate telemetry sending.
					$telemetry_instance->bb_send_telemetry_report_to_analytics();
				}
			}
		}

		/**
		 * Fires when ReadyLaunch onboarding analytics are sent.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $wizard_id       The wizard ID.
		 * @param array  $completion_data Completion data.
		 */
		do_action( 'bb_rl_onboarding_analytics_sent', $this->wizard_id, $completion_data );
	}
}
