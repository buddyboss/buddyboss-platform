<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
use BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;

/**
 * This class registers and renders an admin page that displays a list of add-ons available for the License.
 */
class BB_Addons_Page implements StaticContainerAwareness {

	use HasStaticContainer;

	/**
	 * The capability required to view the page.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * The page slug.
	 */
	public const SLUG = 'buddyboss-addons';

	/**
	 * Retrieves the page title.
	 *
	 * @return string
	 */
	public static function pageTitle(): string {
		return esc_html__( 'BuddyBoss License Add-ons', 'buddyboss' );
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
			esc_html__( 'Add-ons', 'buddyboss' ),
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
		echo '<div class="wrap">';
			echo '<h2>' . self::pageTitle() . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<br>';
			echo BB_Addons_Manager::generateAddonsHtml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}
