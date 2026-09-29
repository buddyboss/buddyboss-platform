<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

/**
 * This class registers and renders an admin page that displays a list of add-ons available for the License.
 */
class BB_Addons_Page {

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
	 * @since BuddyBoss [BBVERSION] Added the `$parent_slug` and `$capability` parameters.
	 *
	 * @param string $parent_slug Parent menu slug.
	 * @param string $capability  Capability required to view the page.
	 * @return mixed The resulting page's hook suffix or false if the user does not have the capability.
	 */
	public static function register( string $parent_slug = 'buddyboss-platform', string $capability = self::CAPABILITY ) {
		return add_submenu_page(
			$parent_slug,
			self::pageTitle(),
			esc_html__( 'Add-ons', 'buddyboss' ),
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
		echo '<div class="wrap">';
			echo '<h2>' . self::pageTitle() . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<br>';
			echo BB_Addons_Manager::render_addons_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}
