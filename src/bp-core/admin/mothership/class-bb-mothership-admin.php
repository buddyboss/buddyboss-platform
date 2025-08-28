<?php

use GroundLevel\Container\Contracts\StaticContainerAwareness;
use GroundLevel\Mothership\Service as MothershipService;
use GroundLevel\Mothership\Manager\LicenseManager;

/**
 * BuddyBoss Platform Mothership Admin
 *
 * Handles the admin interface for license management
 */
class BB_Mothership_Admin implements StaticContainerAwareness {

	/**
	 * Container instance
	 *
	 * @var \GroundLevel\Container\Container
	 */
	protected static $container;

	/**
	 * Admin page slug
	 */
	public const SLUG = 'bb-platform-mothership-license';

	/**
	 * Get singleton instance
	 *
	 * @return BB_Mothership_Admin
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Render the admin page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'buddyboss' ) );
		}

		$mothershipService = self::getContainer()->get( MothershipService::class );

		// Get license status using static LicenseManager.
		$license_status = LicenseManager::checkLicenseStatus();
		$license_info   = array(
			'status'  => $license_status ? 'active' : 'inactive',
			'checked' => true,
		);

		include $this->get_template_path( 'admin.php' );
	}

	/**
	 * Print settings tabs
	 */
	public function print_settings_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'license';
		?>
		<h2 class="nav-tab-wrapper">
			<a href="?page=<?php echo esc_attr( self::SLUG ); ?>&tab=license"
				class="nav-tab <?php echo 'license' === $current_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'License', 'buddyboss' ); ?>
			</a>
		</h2>
		<?php
	}

	/**
	 * Print settings content
	 */
	public function print_settings_content() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'license';

		switch ( $current_tab ) {
			case 'license':
				$this->render_license_tab();
				break;
			default:
				$this->render_license_tab();
				break;
		}
	}

	/**
	 * Render license tab
	 */
	private function render_license_tab() {
		// Get license status using static LicenseManager.
		$license_status = LicenseManager::checkLicenseStatus();
		$license_info   = array(
			'status'  => $license_status ? 'active' : 'inactive',
			'checked' => true,
		);

		include $this->get_template_path( 'license.php' );
	}

	/**
	 * Get template path
	 *
	 * @param string $template_name Template file name.
	 * @return string
	 */
	private function get_template_path( $template_name ) {
		return __DIR__ . '/views/' . $template_name;
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		// Enqueue on any page that contains our slug.
		if ( $screen && strpos( $screen->id, self::SLUG ) !== false ) {
			wp_enqueue_style(
				'bb-platform-mothership-admin',
				buddypress()->plugin_url . 'src/bp-core/admin/mothership/assets/css/admin.css',
				array(),
				buddypress()->version
			);

			wp_enqueue_script(
				'bb-platform-mothership-admin',
				buddypress()->plugin_url . 'src/bp-core/admin/mothership/assets/js/admin.js',
				array( 'jquery' ),
				buddypress()->version,
				true
			);

			wp_localize_script(
				'bb-platform-mothership-admin',
				'bbPlatformMothership',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'bb_platform_mothership_nonce' ),
					'strings' => array(
						'activating'   => __( 'Activating...', 'buddybos' ),
						'deactivating' => __( 'Deactivating...', 'buddyboss' ),
						'error'        => __( 'An error occurred. Please try again.', 'buddyboss' ),
					),
				)
			);
		}
	}

	/**
	 * Get the container instance
	 *
	 * @return \GroundLevel\Container\Container
	 */
	public static function getContainer(): \GroundLevel\Container\Container {
		return self::$container;
	}

	/**
	 * Set the container instance
	 *
	 * @param \GroundLevel\Container\Container $container
	 */
	public static function setContainer( \GroundLevel\Container\Container $container ): void {
		self::$container = $container;
	}
}
