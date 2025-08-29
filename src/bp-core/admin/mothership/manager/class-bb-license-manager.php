<?php
/**
 * BuddyBoss License Manager
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

namespace BuddyBoss\Core\Admin\Mothership\Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector;
use BuddyBoss\Core\Admin\Mothership\BB_Credentials;
use BuddyBoss\Core\Admin\Mothership\API\BB_API_Request;

/**
 * The License Manager class manages site license activation.
 */
class BB_License_Manager {

	/**
	 * Schedule cron events for license checking.
	 *
	 * @param string $plugin_id The plugin ID.
	 */
	public static function schedule_events( $plugin_id ) {
		$cron_name = $plugin_id . '_check_license_status_event';
		add_action( $cron_name, array( __CLASS__, 'check_license_status' ) );
		
		// Schedule event to check license status every 12 hours.
		if ( ! wp_next_scheduled( $cron_name ) ) {
			wp_schedule_event( time(), 'twicedaily', $cron_name );
		}
	}

	/**
	 * Check the license status and trigger status changed action.
	 *
	 * @return bool False if license inactive or credentials empty, true otherwise.
	 */
	public static function check_license_status() {
		$connector = BB_Plugin_Connector::get_instance();
		
		// Only run if license is active.
		if ( ! $connector->get_license_activation_status() ) {
			return false;
		}

		$license_key = BB_Credentials::get_license_key();
		$domain      = BB_Credentials::get_activation_domain();

		if ( empty( $license_key ) || empty( $domain ) ) {
			return false;
		}

		$api      = new BB_API_Request();
		$response = $api->get( 'licenses/' . $license_key . '/activations/' . $domain );

		if ( $response->is_error() && $response->get_error_code() === 401 ) {
			do_action( 'buddyboss_license_status_changed', false, $response );
			return false;
		}

		do_action( 'buddyboss_license_status_changed', true, $response );
		return true;
	}

	/**
	 * Handle license activation/deactivation form submissions.
	 */
	public static function controller() {
		if ( ! isset( $_POST['buddyboss_license_button'] ) ) {
			return;
		}

		$action = sanitize_text_field( $_POST['buddyboss_license_button'] );

		if ( 'activate' === $action ) {
			try {
				self::activate_license(
					sanitize_text_field( $_POST['license_key'] ),
					sanitize_text_field( $_POST['activation_domain'] )
				);
				add_action( 'admin_notices', function() {
					printf(
						'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
						esc_html__( 'License activated successfully', 'buddyboss' )
					);
				});
			} catch ( \Exception $e ) {
				add_action( 'admin_notices', function() use ( $e ) {
					printf(
						'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
						esc_html( $e->getMessage() )
					);
				});
			}
		} elseif ( 'deactivate' === $action ) {
			try {
				self::deactivate_license(
					sanitize_text_field( $_POST['license_key'] ),
					sanitize_text_field( $_POST['activation_domain'] )
				);
				add_action( 'admin_notices', function() {
					printf(
						'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
						esc_html__( 'License deactivated successfully', 'buddyboss' )
					);
				});
			} catch ( \Exception $e ) {
				add_action( 'admin_notices', function() use ( $e ) {
					printf(
						'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
						esc_html( $e->getMessage() )
					);
				});
			}
		}
	}

	/**
	 * Generate the license activation form HTML.
	 *
	 * @return string The form HTML.
	 */
	public function generate_license_activation_form() {
		$connector = BB_Plugin_Connector::get_instance();
		
		if ( $connector->get_license_activation_status() ) {
			return $this->generate_disconnect_form();
		} else {
			return $this->generate_activation_form();
		}
	}

	/**
	 * Generate the activation form HTML.
	 *
	 * @return string The form HTML.
	 */
	private function generate_activation_form() {
		ob_start();
		$license_key = BB_Credentials::get_license_key();
		$domain      = BB_Credentials::get_activation_domain();
		$is_readonly = BB_Credentials::is_credential_set_in_environment( 'license_key' );
		?>
		<form method="post" action="" name="buddyboss_activate_license_form">
			<div class="buddyboss-license-form">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="license_key"><?php esc_html_e( 'License Key', 'buddyboss' ); ?></label>
						</th>
						<td>
							<input name="license_key"
								type="text"
								id="license_key"
								value="<?php echo esc_attr( $license_key ); ?>"
								class="regular-text"
								<?php echo $is_readonly ? 'readonly' : ''; ?>
							/>
							<input type="hidden"
								name="activation_domain"
								value="<?php echo esc_attr( $domain ); ?>"
							/>
						</td>
					</tr>
				</table>
				<?php wp_nonce_field( 'buddyboss_activate_license', '_wpnonce' ); ?>
				<input type="hidden" name="buddyboss_license_button" value="activate" />
				<p class="submit">
					<input type="submit"
						value="<?php esc_attr_e( 'Activate License', 'buddyboss' ); ?>"
						class="button button-primary"
						<?php echo $is_readonly ? 'disabled' : ''; ?>
					/>
				</p>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate the disconnect form HTML.
	 *
	 * @return string The form HTML.
	 */
	private function generate_disconnect_form() {
		ob_start();
		$license_key = BB_Credentials::get_license_key();
		$domain      = BB_Credentials::get_activation_domain();
		?>
		<form method="post" action="" name="buddyboss_deactivate_license_form">
			<div class="buddyboss-license-form">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="license_key"><?php esc_html_e( 'License Key', 'buddyboss' ); ?></label>
						</th>
						<td>
							<input name="license_key"
								type="text"
								readonly
								id="license_key"
								value="<?php echo esc_attr( $license_key ); ?>"
								class="regular-text"
							/>
							<input type="hidden"
								name="activation_domain"
								value="<?php echo esc_attr( $domain ); ?>"
							/>
						</td>
					</tr>
				</table>
				<?php wp_nonce_field( 'buddyboss_deactivate_license', '_wpnonce' ); ?>
				<input type="hidden" name="buddyboss_license_button" value="deactivate" />
				<p class="submit">
					<input type="submit"
						value="<?php esc_attr_e( 'Deactivate License', 'buddyboss' ); ?>"
						class="button button-secondary"
					/>
				</p>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Activate a license.
	 *
	 * @param string $license_key The license key.
	 * @param string $domain      The activation domain.
	 * @throws \Exception If activation fails.
	 */
	public static function activate_license( $license_key, $domain ) {
		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \Exception( esc_html__( 'Insufficient permissions', 'buddyboss' ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'buddyboss_activate_license' ) ) {
			throw new \Exception( esc_html__( 'Invalid security token', 'buddyboss' ) );
		}

		$api      = new BB_API_Request();
		$response = $api->post( 'licenses/' . $license_key . '/activate', array(
			'domain'  => $domain,
			'product' => 'buddyboss-platform',
		));

		if ( $response->is_error() ) {
			throw new \Exception( sprintf(
				esc_html__( 'License activation failed: %s', 'buddyboss' ),
				$response->get_error_message()
			));
		}

		// Store credentials.
		BB_Credentials::store_license_key( $license_key );
		
		$connector = BB_Plugin_Connector::get_instance();
		$connector->update_license_activation_status( true );
	}

	/**
	 * Deactivate a license.
	 *
	 * @param string $license_key The license key.
	 * @param string $domain      The activation domain.
	 * @throws \Exception If deactivation fails.
	 */
	public static function deactivate_license( $license_key, $domain ) {
		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \Exception( esc_html__( 'Insufficient permissions', 'buddyboss' ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'buddyboss_deactivate_license' ) ) {
			throw new \Exception( esc_html__( 'Invalid security token', 'buddyboss' ) );
		}

		$api      = new BB_API_Request();
		$response = $api->patch( 'licenses/' . $license_key . '/activations/' . $domain . '/deactivate', array(
			'domain' => $domain,
		));

		if ( $response->is_error() ) {
			throw new \Exception( sprintf(
				esc_html__( 'License deactivation failed: %s', 'buddyboss' ),
				$response->get_error_message()
			));
		}

		// Clear stored credentials.
		BB_Credentials::store_license_key( '' );
		
		// Clear addons cache.
		delete_transient( 'buddyboss_addons_cache' );
		
		$connector = BB_Plugin_Connector::get_instance();
		$connector->update_license_activation_status( false );
	}
}