<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
use BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;

/**
 * This class registers and renders an admin page that displays a form for activating/deactivating the license.
 */
class BB_License_Page implements StaticContainerAwareness {

	use HasStaticContainer;

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
		return esc_html__( 'BuddyBoss License Activation', 'buddyboss' );
	}

	/**
	 * Registers the page.
	 *
	 * @return mixed The resulting page's hook suffix or false if the user does not have the capability set in the constant self::CAPABILITY.
	 */
	public static function register() {
		return add_submenu_page(
			'buddyboss-platform',
			self::pageTitle(),
			esc_html__( 'License Activation', 'buddyboss' ),
			self::CAPABILITY,
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
		wp_enqueue_style( 'bb-mothership-admin', buddypress()->plugin_url . 'bp-core/admin/css/mothership.css', array(), buddypress()->version );


		include_once __DIR__ . '/views/admin.php';
	}
}
