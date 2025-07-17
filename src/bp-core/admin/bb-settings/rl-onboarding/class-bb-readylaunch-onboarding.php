<?php
/**
 * BuddyBoss ReadyLaunch Onboarding
 *
 * @package BuddyBoss\Core\Administration
 * @subpackage ReadyLaunchOnboarding
 * @since   BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
 */
class BB_ReadyLaunch_Onboarding extends BB_Setup_Wizard_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var   BB_ReadyLaunch_Onboarding|null
	 */
	private static $instance = null;

	/**
	 * Ensures only one instance of BB_ReadyLaunch_Onboarding is loaded or can be loaded.
	 *
	 * @since  BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
					'key'         => 'splash',
					'title'       => __( 'Welcome to BuddyBoss', 'buddyboss' ),
					'description' => __( 'Let\'s bring your community to life by choose the look and feel that matches your vision.', 'buddyboss' ),
					'component'   => 'SplashScreen',
					'image'       => 'onboardingModal-splash.png',
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
					'description' => __( 'Configure your brand identity and assets', 'buddyboss' ),
					'component'   => 'BrandingsStep',
					'image'       => 'onboardingModal-step-3.png',
				),
				4 => array(
					'key'         => 'pages',
					'title'       => __( 'Pages', 'buddyboss' ),
					'description' => __( 'Set up your essential pages and content', 'buddyboss' ),
					'component'   => 'PagesStep',
					'image'       => 'onboardingModal-step-4.png',
				),
				5 => array(
					'key'         => 'side_menus',
					'title'       => __( 'Side Menus', 'buddyboss' ),
					'description' => __( 'Configure navigation and menu structure', 'buddyboss' ),
					'component'   => 'SideMenusStep',
					'image'       => 'onboardingModal-step-5.png',
				),
				6 => array(
					'key'         => 'widgets',
					'title'       => __( 'Widgets', 'buddyboss' ),
					'description' => __( 'Customize widgets and layout components', 'buddyboss' ),
					'component'   => 'WidgetsStep',
					'image'       => 'onboardingModal-step-6.png',
				),
				7 => array(
					'key'         => 'finish',
					'title'       => __( 'Setup Complete!', 'buddyboss' ),
					'description' => __( 'Your BuddyBoss community is ready to go!', 'buddyboss' ),
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
						'type'       => 'visual_options',
						'options'    => array(
							'light'  => array(
								'label'       => __( 'Light Mode', 'buddyboss' ),
								'description' => __( 'The site will be shown in light mode.', 'buddyboss' ),
								'icon_class' => 'bb-icon-rl-light',
							),
							'dark'   => array(
								'label'       => __( 'Dark Mode', 'buddyboss' ),
								'description' => __( 'The site will be shown in dark mode.', 'buddyboss' ),
								'icon_class' => 'bb-icon-rl-dark',
							),
							'choice' => array(
								'label'       => __( 'Both', 'buddyboss' ),
								'description' => __( 'Users can switch between modes.', 'buddyboss' ),
								'icon_class' => 'bb-icon-rl-both',
							),
						),
						'default'     => 'light',
					),
				),
				'brandings'       => array(
					'bb_rl_light_logo'    => array(
						'type'        => 'media',
						'label'       => __( 'Logo (Light mode)', 'buddyboss' ),
						'description' => __( 'Upload your site logo', 'buddyboss' ),
					),
					'bb_rl_dark_logo'    => array(
						'type'        => 'media',
						'label'       => __( 'Logo (Dark mode)', 'buddyboss' ),
						'description' => __( 'Upload your site logo', 'buddyboss' ),
					),
					'logo_description'    => array(
						'type'        => 'description',
						'description' => __( 'Recommended to upload a light-colored logo for dark mode and a dark-colored logo for light mode, 280x80 px, in JPG or PNG format.', 'buddyboss' ),
					),
					'logo_color_separator'    => array(
						'type'        => 'hr'
					),
					'bb_rl_color_light' => array(
						'type'        => 'color',
						'label'       => __( 'Primary Color (Light mode)', 'buddyboss' ),
						'description' => __( 'Set your primary brand colors', 'buddyboss' ),
						'default'     => '#3E34FF',
					),
					'bb_rl_color_dark' => array(
						'type'        => 'color',
						'label'       => __( 'Primary Color (Dark mode)', 'buddyboss' ),
						'description' => __( 'Set your primary brand colors', 'buddyboss' ),
						'default'     => '#A347FF',
					),
					'color_description'    => array(
						'type'        => 'description',
						'description' => __( 'Primary color used for buttons, links, and interactive elements.', 'buddyboss' ),
					),					
				),
				'pages'           => array(
					'create_essential_pages' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Create Essential Pages', 'buddyboss' ),
						'description' => __( 'Auto-create Privacy Policy, Terms of Service, and About pages', 'buddyboss' ),
						'default'     => true,
					),
					'homepage_layout'        => array(
						'type'        => 'visual_options',
						'label'       => __( 'Homepage Layout', 'buddyboss' ),
						'description' => __( 'Choose your homepage layout', 'buddyboss' ),
						'options'     => array(
							'activity' => __( 'Activity Feed', 'buddyboss' ),
							'custom'   => __( 'Custom Page', 'buddyboss' ),
							'landing'  => __( 'Landing Page', 'buddyboss' ),
						),
						'default'     => 'activity',
					),
				),
				'side_menus'      => array(
					'enable_primary_menu' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Enable Primary Navigation', 'buddyboss' ),
						'description' => __( 'Show main navigation menu', 'buddyboss' ),
						'default'     => true,
					),
					'enable_member_menu'  => array(
						'type'        => 'checkbox',
						'label'       => __( 'Enable Member Menu', 'buddyboss' ),
						'description' => __( 'Show member-specific navigation', 'buddyboss' ),
						'default'     => true,
					),
					'menu_style'          => array(
						'type'        => 'visual_options',
						'label'       => __( 'Menu Style', 'buddyboss' ),
						'description' => __( 'Choose navigation menu style', 'buddyboss' ),
						'options'     => array(
							'horizontal' => __( 'Horizontal', 'buddyboss' ),
							'vertical'   => __( 'Vertical Sidebar', 'buddyboss' ),
						),
						'default'     => 'horizontal',
					),
				),
				'widgets'         => array(
					'enable_sidebar_widgets' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Enable Sidebar Widgets', 'buddyboss' ),
						'description' => __( 'Show widgets in sidebar areas', 'buddyboss' ),
						'default'     => true,
					),
					'default_widgets'        => array(
						'type'        => 'checkbox',
						'label'       => __( 'Install Default Widgets', 'buddyboss' ),
						'description' => __( 'Add common widgets like Recent Activity, Member List', 'buddyboss' ),
						'default'     => true,
					),
					'widget_areas'           => array(
						'type'        => 'visual_options',
						'label'       => __( 'Widget Areas', 'buddyboss' ),
						'description' => __( 'Choose which widget areas to enable', 'buddyboss' ),
						'options'     => array(
							'all'     => __( 'All Areas', 'buddyboss' ),
							'sidebar' => __( 'Sidebar Only', 'buddyboss' ),
							'footer'  => __( 'Footer Only', 'buddyboss' ),
						),
						'default'     => 'all',
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
				'completion'      => array(
					'bb_readylaunch_onboarding_completed',
					'bb_rl_setup_complete',
				),
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	private function init_readylaunch_hooks() {
		// Add ReadyLaunch specific AJAX actions.
		add_action( 'wp_ajax_bb_rl_get_theme_info', array( $this, 'ajax_get_theme_info' ) );
		add_action( 'wp_ajax_bb_rl_install_theme', array( $this, 'ajax_install_theme' ) );

		// Handle completion specific to ReadyLaunch.
		add_action( 'bb_readylaunch_onboarding_completed', array( $this, 'on_readylaunch_completed' ) );
	}

	/**
	 * Check if onboarding should be shown.
	 *
	 * Uses the existing BP activation mechanism with the bb_wizard_activation URL parameter.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	protected function enqueue_wizard_assets() {
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
			buddypress()->plugin_url . 'bp-core/admin/bb-settings/rl-onboarding/build/onboarding.css',
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
	 * Localize wizard data for JavaScript.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return array Localized data array.
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
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $base_data The base localised data.
		 */
		return apply_filters( 'bb_rl_onboarding_localize_data', $base_data );
	}

	/**
	 * Get wizard assets.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
		$final_settings  = isset( $_POST['finalSettings'] ) ? $this->sanitize_final_settings( $_POST['finalSettings'] ) : array();
		$completion_data = array(
			'final_settings' => $final_settings,
			'completed_at'   => current_time( 'mysql' ),
			'total_steps'    => count( $this->get_config( 'steps', array() ) ),
		);

		// Save all settings as preferences.
		if ( ! empty( $final_settings ) ) {
			$preferences                   = $this->get_preferences();
			$preferences['final_settings'] = $final_settings;
			$this->save_preferences( $preferences );
		}

		// Mark wizard as completed.
		$result = $this->mark_completed( $completion_data );

		if ( $result ) {
			// Apply all ReadyLaunch configurations.
			$this->apply_readylaunch_configuration( $final_settings );

			// Send completion notification.
			$this->send_completion_notification();

			// Clean up ReadyLaunch specific transients.
			delete_transient( '_bb_rl_show_onboarding' );

			/**
			 * Fires after ReadyLaunch onboarding is completed.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $final_settings The final configuration settings.
			 */
			do_action( 'bb_rl_onboarding_completed', $final_settings );

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
	 * Configure ReadyLaunch settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $selected_option The selected configuration option.
	 * @return void
	 */
	private function configure_readylaunch( $selected_option ) {
		switch ( $selected_option ) {
			case 'buddyboss_theme':
				// Activate ReadyLaunch and BuddyBoss theme if available.
				if ( wp_get_theme( 'buddyboss-theme' )->exists() ) {
					switch_theme( 'buddyboss-theme' );
				}
				break;

			case 'current_theme':
				// Keep current theme, just activate ReadyLaunch.
				break;

			default:
				// Default action.
				break;
		}

		// Set ReadyLaunch as configured.
		bp_update_option( 'bb_readylaunch_setup_completed', true );
	}

	/**
	 * Handle ReadyLaunch completion
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $wizard_id The wizard ID.
	 * @return void
	 */
	public function on_readylaunch_completed( $wizard_id ) {
		if ( $wizard_id !== $this->wizard_id ) {
			return;
		}

		$preferences = $this->get_preferences();

		// Perform additional setup based on preferences.
		if ( isset( $preferences['selected_option'] ) ) {
			switch ( $preferences['selected_option'] ) {
				case 'buddyboss_theme':
					$this->setup_buddyboss_theme();
					break;

				case 'current_theme':
					$this->setup_current_theme();
					break;
			}
		}

		// Send completion email or perform other actions.
		$this->send_completion_notification();
	}

	/**
	 * Setup BuddyBoss theme configuration
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	private function setup_buddyboss_theme() {
		// Additional BuddyBoss theme configuration.
		bp_update_option( 'bb_theme_configured_via_rl', true );

		// Track theme selection analytics.
		if ( $this->get_config( 'enable_analytics' ) ) {
			$this->save_step_progress(
				999,
				array(
					'event'          => 'theme_selected',
					'selected_theme' => 'buddyboss_theme',
				)
			);
		}
	}

	/**
	 * Setup current theme configuration
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	private function setup_current_theme() {
		// Track current theme retention.
		bp_update_option( 'bb_current_theme_retained_via_rl', true );

		// Track theme selection analytics.
		if ( $this->get_config( 'enable_analytics' ) ) {
			$this->save_step_progress(
				999,
				array(
					'event'          => 'theme_selected',
					'selected_theme' => 'current_theme',
					'theme_name'     => wp_get_theme()->get( 'Name' ),
				)
			);
		}
	}

	/**
	 * Send completion notification
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	private function send_completion_notification() {
		// Send welcome email to admin (optional).
		$admin_email = get_option( 'admin_email' );
		if ( $admin_email ) {
			$subject = __( 'BuddyBoss ReadyLaunch Setup Complete', 'buddyboss' );
			$message = __( 'Congratulations! Your BuddyBoss community setup is now complete and ready to use.', 'buddyboss' );

			/**
			 * Filter the completion notification email
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $email_data Email data array.
			 */
			$email_data = apply_filters(
				'bb_rl_completion_notification_email',
				array(
					'to'      => $admin_email,
					'subject' => $subject,
					'message' => $message,
				)
			);

			if ( ! empty( $email_data['to'] ) ) {
				wp_mail( $email_data['to'], $email_data['subject'], $email_data['message'] );
			}
		}
	}

	/**
	 * AJAX handler to get theme information
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	public function ajax_get_theme_info() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->wizard_id . '_wizard_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'buddyboss' ) ) );
		}

		$theme_slug = isset( $_POST['theme_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['theme_slug'] ) ) : '';

		$theme_info = array();

		if ( 'buddyboss-theme' === $theme_slug ) {
			$theme_info = array(
				'name'         => __( 'BuddyBoss Theme', 'buddyboss' ),
				'description'  => __( 'Premium theme designed for BuddyBoss Platform', 'buddyboss' ),
				'version'      => '1.0.0',
				'author'       => 'BuddyBoss',
				'is_installed' => wp_get_theme( 'buddyboss-theme' )->exists(),
				'is_active'    => get_template() === 'buddyboss-theme',
				'download_url' => 'https://buddyboss.com/theme-download',
			);
		} else {
			$current_theme = wp_get_theme();
			$theme_info    = array(
				'name'         => $current_theme->get( 'Name' ),
				'description'  => $current_theme->get( 'Description' ),
				'version'      => $current_theme->get( 'Version' ),
				'author'       => $current_theme->get( 'Author' ),
				'is_installed' => true,
				'is_active'    => true,
			);
		}

		wp_send_json_success( array( 'theme_info' => $theme_info ) );
	}

	/**
	 * AJAX handler to install/activate theme
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	public function ajax_install_theme() {
		// Verify nonce and capabilities.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->wizard_id . '_wizard_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'buddyboss' ) ) );
		}

		if ( ! current_user_can( 'install_themes' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to install themes.', 'buddyboss' ) ) );
		}

		$theme_slug  = isset( $_POST['theme_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['theme_slug'] ) ) : '';
		$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : 'activate';

		if ( 'buddyboss-theme' === $theme_slug ) {
			// Handle BuddyBoss theme installation/activation.
			$result = $this->handle_buddyboss_theme_activation();
		} else {
			// Keep current theme - no action needed.
			$result = array(
				'success' => true,
				'message' => __( 'Current theme maintained.', 'buddyboss' ),
			);
		}

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Handle BuddyBoss theme activation
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return array Result of theme activation.
	 */
	private function handle_buddyboss_theme_activation() {
		// Check if theme is already installed.
		if ( ! wp_get_theme( 'buddyboss-theme' )->exists() ) {
			// Theme installation logic would go here
			// For now, return instruction to download.
			return array(
				'success'      => false,
				'message'      => __( 'Please download and install the BuddyBoss Theme first.', 'buddyboss' ),
				'download_url' => 'https://buddyboss.com/theme-download',
			);
		}

		// Activate the theme.
		switch_theme( 'buddyboss-theme' );

		return array(
			'success' => true,
			'message' => __( 'BuddyBoss Theme activated successfully!', 'buddyboss' ),
		);
	}

	/**
	 * Sanitise final settings from the onboarding form.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $settings Raw settings data.
	 * @return array Sanitised settings.
	 */
	private function sanitize_final_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		$sanitized = array();

		// Sanitize community setup settings.
		if ( isset( $settings['community_setup'] ) ) {
			$sanitized['community_setup'] = array(
				'site_title'   => isset( $settings['community_setup']['site_title'] ) ? sanitize_text_field( wp_unslash( $settings['community_setup']['site_title'] ) ) : '',
				'privacy_mode' => isset( $settings['community_setup']['privacy_mode'] ) ? sanitize_text_field( wp_unslash( $settings['community_setup']['privacy_mode'] ) ) : 'public',
			);
		}

		// Sanitize site appearance settings.
		if ( isset( $settings['site_appearance'] ) ) {
			$sanitized['site_appearance'] = array(
				'color_scheme' => isset( $settings['site_appearance']['color_scheme'] ) ? sanitize_text_field( wp_unslash( $settings['site_appearance']['color_scheme'] ) ) : 'default',
				'site_layout'  => isset( $settings['site_appearance']['site_layout'] ) ? sanitize_text_field( wp_unslash( $settings['site_appearance']['site_layout'] ) ) : 'fullwidth',
			);
		}

		// Sanitize branding settings.
		if ( isset( $settings['brandings'] ) ) {
			$sanitized['brandings'] = array(
				'site_logo'    => isset( $settings['brandings']['site_logo'] ) ? intval( $settings['brandings']['site_logo'] ) : 0,
				'favicon'      => isset( $settings['brandings']['favicon'] ) ? intval( $settings['brandings']['favicon'] ) : 0,
				'brand_colors' => isset( $settings['brandings']['brand_colors'] ) ? sanitize_hex_color( $settings['brandings']['brand_colors'] ) : '',
			);
		}

		// Sanitize pages settings.
		if ( isset( $settings['pages'] ) ) {
			$sanitized['pages'] = array(
				'create_essential_pages' => ! empty( $settings['pages']['create_essential_pages'] ),
				'homepage_layout'        => isset( $settings['pages']['homepage_layout'] ) ? sanitize_text_field( wp_unslash( $settings['pages']['homepage_layout'] ) ) : 'activity',
			);
		}

		// Sanitize side menus settings.
		if ( isset( $settings['side_menus'] ) ) {
			$sanitized['side_menus'] = array(
				'enable_primary_menu' => ! empty( $settings['side_menus']['enable_primary_menu'] ),
				'enable_member_menu'  => ! empty( $settings['side_menus']['enable_member_menu'] ),
				'menu_style'          => isset( $settings['side_menus']['menu_style'] ) ? sanitize_text_field( wp_unslash( $settings['side_menus']['menu_style'] ) ) : 'horizontal',
			);
		}

		// Sanitize widgets settings.
		if ( isset( $settings['widgets'] ) ) {
			$sanitized['widgets'] = array(
				'enable_sidebar_widgets' => ! empty( $settings['widgets']['enable_sidebar_widgets'] ),
				'default_widgets'        => ! empty( $settings['widgets']['default_widgets'] ),
				'widget_areas'           => isset( $settings['widgets']['widget_areas'] ) ? sanitize_text_field( wp_unslash( $settings['widgets']['widget_areas'] ) ) : 'all',
			);
		}

		return $sanitized;
	}

	/**
	 * Apply ReadyLaunch configuration based on final settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $final_settings The final configuration settings.
	 * @return void
	 */
	private function apply_readylaunch_configuration( $final_settings ) {
		if ( empty( $final_settings ) ) {
			return;
		}

		// Apply community setup.
		if ( isset( $final_settings['community_setup'] ) ) {
			$community_setup = $final_settings['community_setup'];

			// Update site title if provided.
			if ( ! empty( $community_setup['site_title'] ) ) {
				update_option( 'blogname', $community_setup['site_title'] );
			}

			// Configure privacy mode.
			if ( isset( $community_setup['privacy_mode'] ) ) {
				// This would typically involve setting registration options.
				if ( 'private' === $community_setup['privacy_mode'] ) {
					update_option( 'users_can_register', 0 );
				} else {
					update_option( 'users_can_register', 1 );
				}
			}
		}

		// Apply site appearance settings.
		if ( isset( $final_settings['site_appearance'] ) ) {
			$site_appearance = $final_settings['site_appearance'];

			if ( isset( $site_appearance['color_scheme'] ) ) {
				bp_update_option( 'bb_rl_color_scheme', $site_appearance['color_scheme'] );
			}

			if ( isset( $site_appearance['site_layout'] ) ) {
				bp_update_option( 'bb_rl_site_layout', $site_appearance['site_layout'] );
			}
		}

		// Apply branding settings.
		if ( isset( $final_settings['brandings'] ) ) {
			$brandings = $final_settings['brandings'];

			if ( isset( $brandings['site_logo'] ) && $brandings['site_logo'] ) {
				update_option( 'custom_logo', $brandings['site_logo'] );
			}

			if ( isset( $brandings['favicon'] ) && $brandings['favicon'] ) {
				update_option( 'site_icon', $brandings['favicon'] );
			}

			if ( isset( $brandings['brand_colors'] ) && $brandings['brand_colors'] ) {
				bp_update_option( 'bb_rl_brand_colors', $brandings['brand_colors'] );
			}
		}

		// Apply pages settings.
		if ( isset( $final_settings['pages'] ) ) {
			$pages = $final_settings['pages'];

			if ( isset( $pages['create_essential_pages'] ) && $pages['create_essential_pages'] ) {
				$this->create_essential_pages();
			}

			if ( isset( $pages['homepage_layout'] ) ) {
				bp_update_option( 'bb_rl_homepage_layout', $pages['homepage_layout'] );

				// Set homepage based on layout choice.
				if ( 'activity' === $pages['homepage_layout'] ) {
					update_option( 'show_on_front', 'page' );
					// Set to activity page if exists.
				}
			}
		}

		// Apply side menus settings.
		if ( isset( $final_settings['side_menus'] ) ) {
			$side_menus = $final_settings['side_menus'];

			if ( isset( $side_menus['enable_primary_menu'] ) ) {
				bp_update_option( 'bb_rl_enable_primary_menu', $side_menus['enable_primary_menu'] ? 1 : 0 );
			}

			if ( isset( $side_menus['enable_member_menu'] ) ) {
				bp_update_option( 'bb_rl_enable_member_menu', $side_menus['enable_member_menu'] ? 1 : 0 );
			}

			if ( isset( $side_menus['menu_style'] ) ) {
				bp_update_option( 'bb_rl_menu_style', $side_menus['menu_style'] );
			}
		}

		// Apply widgets settings.
		if ( isset( $final_settings['widgets'] ) ) {
			$widgets = $final_settings['widgets'];

			if ( isset( $widgets['enable_sidebar_widgets'] ) ) {
				bp_update_option( 'bb_rl_enable_sidebar_widgets', $widgets['enable_sidebar_widgets'] ? 1 : 0 );
			}

			if ( isset( $widgets['default_widgets'] ) && $widgets['default_widgets'] ) {
				$this->setup_default_widgets();
			}

			if ( isset( $widgets['widget_areas'] ) ) {
				bp_update_option( 'bb_rl_widget_areas', $widgets['widget_areas'] );
			}
		}

		/**
		 * Fires after ReadyLaunch configuration has been applied.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $final_settings The final configuration settings.
		 */
		do_action( 'bb_rl_configuration_applied', $final_settings );
	}

	/**
	 * Create essential pages (Privacy Policy, Terms of Service, About).
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * Setup default widgets for the community.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	private function setup_default_widgets() {
		// Get current widget options.
		$sidebar_widgets = get_option( 'sidebars_widgets', array() );

		// Define default widgets for primary sidebar.
		$default_widgets = array(
			'bb_recent_activity' => array(
				'title' => __( 'Recent Activity', 'buddyboss' ),
				'count' => 5,
			),
			'bb_member_list'     => array(
				'title' => __( 'Community Members', 'buddyboss' ),
				'count' => 8,
			),
			'bb_groups_widget'   => array(
				'title' => __( 'Active Groups', 'buddyboss' ),
				'count' => 5,
			),
		);

		// Add widgets to primary sidebar if it exists.
		if ( isset( $sidebar_widgets['sidebar-1'] ) ) {
			foreach ( $default_widgets as $widget_id => $widget_config ) {
				// Add widget instance.
				$widget_instances = get_option( 'widget_' . $widget_id, array() );
				$instance_id      = count( $widget_instances ) + 1;

				$widget_instances[ $instance_id ] = $widget_config;
				update_option( 'widget_' . $widget_id, $widget_instances );

				// Add to sidebar.
				$sidebar_widgets['sidebar-1'][] = $widget_id . '-' . $instance_id;
			}

			update_option( 'sidebars_widgets', $sidebar_widgets );
		}
	}
}
