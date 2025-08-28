<?php

use GroundLevel\Container\Contracts\StaticContainerAwareness;
use GroundLevel\Mothership\Service as MothershipService;
use GroundLevel\Mothership\Manager\AddonsManager;

/**
 * BuddyBoss Platform Mothership Addons
 *
 * Handles the admin interface for addon management
 */
class BB_Mothership_Addons implements StaticContainerAwareness {

	/**
	 * Container instance
	 *
	 * @var \GroundLevel\Container\Container
	 */
	protected static $container;

	/**
	 * Admin page slug
	 */
	public const SLUG = 'bb-platform-mothership-addons';

	/**
	 * Get singleton instance
	 *
	 * @return BB_Mothership_Addons
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

		// Get addons using static AddonsManager.
		$addons = AddonsManager::getAddons();

		include $this->get_template_path( 'addons.php' );
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
				'bb-platform-mothership-addons',
				buddypress()->plugin_url . 'src/bp-core/admin/mothership/assets/css/addons.css',
				array(),
				buddypress()->version
			);

			wp_enqueue_script(
				'bb-platform-mothership-addons',
				buddypress()->plugin_url . 'src/bp-core/admin/mothership/assets/js/addons.js',
				array( 'jquery' ),
				buddypress()->version,
				true
			);

			wp_localize_script(
				'bb-platform-mothership-addons',
				'bbPlatformMothershipAddons',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'bb_platform_mothership_addons_nonce' ),
					'strings' => array(
						'installing'   => __( 'Installing...', 'buddyboss' ),
						'activating'   => __( 'Activating...', 'buddyboss' ),
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
