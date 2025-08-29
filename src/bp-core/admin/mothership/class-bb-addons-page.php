<?php
/**
 * BuddyBoss Addons Page
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

namespace BuddyBoss\Core\Admin\Mothership;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include required files.
require_once __DIR__ . '/manager/class-bb-addons-manager.php';
require_once __DIR__ . '/class-bb-plugin-connector.php';

use BuddyBoss\Core\Admin\Mothership\Manager\BB_Addons_Manager;

/**
 * Addons page class.
 */
class BB_Addons_Page {

	/**
	 * Page capability.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Page slug.
	 */
	const SLUG = 'buddyboss-addons';

	/**
	 * Get page title.
	 *
	 * @return string Page title.
	 */
	public static function get_page_title() {
		return esc_html__( 'BuddyBoss Add-ons', 'buddyboss' );
	}

	/**
	 * Register the admin page.
	 *
	 * @return string|false The resulting page's hook_suffix, or false if user lacks capability.
	 */
	public static function register() {
		return add_submenu_page(
			'buddyboss-platform',
			self::get_page_title(),
			esc_html__( 'Add-ons', 'buddyboss' ),
			self::CAPABILITY,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render the page.
	 */
	public static function render() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( self::get_page_title() ); ?></h1>
			
			<div class="buddyboss-addons-wrap">
				<?php echo BB_Addons_Manager::generate_addons_html(); ?>
			</div>
		</div>
		<?php
	}
}