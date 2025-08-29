<?php
/**
 * BuddyBoss License Page
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

namespace BuddyBoss\Core\Admin\Mothership;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include required files.
require_once __DIR__ . '/manager/class-bb-license-manager.php';
require_once __DIR__ . '/class-bb-plugin-connector.php';
require_once __DIR__ . '/api/class-bb-api-request.php';

use BuddyBoss\Core\Admin\Mothership\Manager\BB_License_Manager;
use BuddyBoss\Core\Admin\Mothership\API\BB_API_Request;

/**
 * License activation page class.
 */
class BB_License_Page {

	/**
	 * Page capability.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Page slug.
	 */
	const SLUG = 'buddyboss-license';

	/**
	 * Get page title.
	 *
	 * @return string Page title.
	 */
	public static function get_page_title() {
		return esc_html__( 'BuddyBoss License Activation', 'buddyboss' );
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
			esc_html__( 'License Activation', 'buddyboss' ),
			self::CAPABILITY,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render the page.
	 */
	public static function render() {
		$license_manager = new BB_License_Manager();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( self::get_page_title() ); ?></h1>
			
			<div class="buddyboss-license-wrap">
				<?php echo $license_manager->generate_license_activation_form(); ?>
				
				<div class="buddyboss-license-info">
					<h3><?php esc_html_e( 'License Information', 'buddyboss' ); ?></h3>
					<?php self::render_license_info(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render license information.
	 */
	private static function render_license_info() {
		$connector = BB_Plugin_Connector::get_instance();
		$is_active = $connector->get_license_activation_status();
		
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'buddyboss' ); ?></th>
				<td>
					<?php if ( $is_active ) : ?>
						<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
						<?php esc_html_e( 'Active', 'buddyboss' ); ?>
					<?php else : ?>
						<span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
						<?php esc_html_e( 'Inactive', 'buddyboss' ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Domain', 'buddyboss' ); ?></th>
				<td><?php echo esc_html( BB_Credentials::get_activation_domain() ); ?></td>
			</tr>
			<?php if ( $is_active && BB_Credentials::get_license_key() ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'License Key', 'buddyboss' ); ?></th>
				<td>
					<?php 
					$key = BB_Credentials::get_license_key();
					echo esc_html( substr( $key, 0, 4 ) . str_repeat( '*', strlen( $key ) - 8 ) . substr( $key, -4 ) );
					?>
				</td>
			</tr>
			<?php endif; ?>
		</table>
		<?php
	}
}