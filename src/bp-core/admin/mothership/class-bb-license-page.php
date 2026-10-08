<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

/**
 * This class registers and renders an admin page that displays a form for activating/deactivating the license.
 */
class BB_License_Page {

	/**
	 * The capability required to view the page.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * The page slug.
	 */
	public const SLUG = 'buddyboss-license';

	/**
	 * Retrieves the page title.
	 *
	 * @return string
	 */
	public static function pageTitle(): string {
		return esc_html__( 'BuddyBoss License Activation', 'buddyboss-platform' );
	}

	/**
	 * Registers the page.
	 *
	 * @since BuddyBoss 3.5.1 Added the `$parent_slug` and `$capability` parameters.
	 *
	 * @param string $parent_slug Parent menu slug.
	 * @param string $capability  Capability required to view the page.
	 * @return mixed The resulting page's hook suffix or false if the user does not have the capability.
	 */
	public static function register( string $parent_slug = 'buddyboss-platform', string $capability = self::CAPABILITY ) {
		return add_submenu_page(
			$parent_slug,
			self::pageTitle(),
			esc_html__( 'License Activation', 'buddyboss-platform' ),
			$capability,
			self::SLUG,
			array(
				self::class,
				'render',
			),
		);
	}

	/**
	 * Renders the page.
	 */
	public static function render(): void {
		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_style( 'bb-mothership-admin', buddypress()->plugin_url . "bp-core/admin/css/mothership{$min}.css", array(), buddypress()->version );

		include_once __DIR__ . '/views/admin.php';
	}
}
