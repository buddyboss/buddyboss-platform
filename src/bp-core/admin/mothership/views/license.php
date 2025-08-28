<?php
/**
 * BuddyBoss Platform Mothership License Template
 *
 * @package BuddyBoss Platform
 */

defined( 'ABSPATH' ) || exit;

$license_status = isset( $license_status ) ? $license_status : 'inactive';
$license_info   = isset( $license_info ) ? $license_info : array();
?>

<div class="bb-platform-mothership-license">
	<div class="bb-platform-mothership-license-status">
		<h3><?php esc_html_e( 'License Status', 'buddyboss' ); ?></h3>

		<?php if ( $license_status === 'active' && ! empty( $license_info ) ) : ?>
			<div class="bb-platform-license-active">
				<div class="license-info">
					<p><strong><?php esc_html_e( 'Status:', 'buddyboss' ); ?></strong>
						<span class="status-active"><?php esc_html_e( 'Active', 'buddyboss' ); ?></span>
					</p>

					<?php if ( isset( $license_info['product_name'] ) ) : ?>
						<p><strong><?php esc_html_e( 'Product:', 'buddyboss' ); ?></strong>
							<?php echo esc_html( $license_info['product_name'] ); ?>
						</p>
					<?php endif; ?>

					<?php if ( isset( $license_info['license_key'] ) ) : ?>
						<p><strong><?php esc_html_e( 'License Key:', 'buddyboss' ); ?></strong>
							<code><?php echo esc_html( substr( $license_info['license_key'], -12 ) ); ?></code>
						</p>
					<?php endif; ?>

					<?php if ( isset( $license_info['expires_at'] ) ) : ?>
						<p><strong><?php esc_html_e( 'Expires:', 'buddyboss' ); ?></strong>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license_info['expires_at'] ) ) ); ?>
						</p>
					<?php endif; ?>
				</div>

				<div class="license-actions">
					<button type="button" class="button button-secondary" id="bb-platform-deactivate-license">
						<?php esc_html_e( 'Deactivate License', 'buddyboss' ); ?>
					</button>
				</div>
			</div>
		<?php else : ?>
			<div class="bb-platform-license-inactive">
				<p><?php esc_html_e( 'No active license found. Please enter your license key to activate BuddyBoss Platform.', 'buddyboss' ); ?></p>

				<div class="license-form">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="bb-platform-license-key"><?php esc_html_e( 'License Key', 'buddyboss' ); ?></label>
							</th>
							<td>
								<input type="text"
										id="bb-platform-license-key"
										name="bb_platform_license_key"
										class="regular-text"
										placeholder="<?php esc_attr_e( 'Enter your license key', 'buddyboss' ); ?>" />
								<p class="description">
									<?php esc_html_e( 'Enter your BuddyBoss Platform license key to activate automatic updates and support.', 'buddyboss' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="button" class="button button-primary" id="bb-platform-activate-license">
							<?php esc_html_e( 'Activate License', 'buddyboss' ); ?>
						</button>
					</p>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div class="bb-platform-mothership-notices">
		<div id="bb-platform-license-message"></div>
	</div>
</div>

<script type="text/javascript">
// Ensure bbPlatformMothership is available
if (typeof bbPlatformMothership === 'undefined') {
	window.bbPlatformMothership = {
		ajaxUrl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		nonce: '<?php echo wp_create_nonce( 'bb_platform_mothership_nonce' ); ?>',
		strings: {
			activating: '<?php esc_html_e( 'Activating...', 'buddyboss' ); ?>',
			deactivating: '<?php esc_html_e( 'Deactivating...', 'buddyboss' ); ?>',
			error: '<?php esc_html_e( 'An error occurred. Please try again.', 'buddyboss' ); ?>'
		}
	};
}

jQuery(document).ready(function($) {
	// Activate license
	$('#bb-platform-activate-license').on('click', function() {
		var $button = $(this);
		var $message = $('#bb-platform-license-message');
		var licenseKey = $('#bb-platform-license-key').val();

		if (!licenseKey) {
			$message.html('<div class="notice notice-error"><p><?php esc_html_e( 'Please enter a license key.', 'buddyboss' ); ?></p></div>');
			return;
		}

		$button.prop('disabled', true).text('<?php esc_html_e( 'Activating...', 'buddyboss' ); ?>');
		$message.html('');

		$.ajax({
			url: bbPlatformMothership.ajaxUrl,
			type: 'POST',
			data: {
				action: 'bb_platform_activate_license',
				license_key: licenseKey,
				nonce: bbPlatformMothership.nonce
			},
			success: function(response) {
				if (response.success) {
					$message.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					$message.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
				}
			},
			error: function() {
				$message.html('<div class="notice notice-error"><p><?php esc_html_e( 'An error occurred. Please try again.', 'buddyboss' ); ?></p></div>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Activate License', 'buddyboss' ); ?>');
			}
		});
	});

	// Deactivate license
	$('#bb-platform-deactivate-license').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to deactivate your license?', 'buddyboss' ); ?>')) {
			return;
		}

		var $button = $(this);
		var $message = $('#bb-platform-license-message');

		$button.prop('disabled', true).text('<?php esc_html_e( 'Deactivating...', 'buddyboss' ); ?>');
		$message.html('');

		$.ajax({
			url: bbPlatformMothership.ajaxUrl,
			type: 'POST',
			data: {
				action: 'bb_platform_deactivate_license',
				nonce: bbPlatformMothership.nonce
			},
			success: function(response) {
				if (response.success) {
					$message.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					$message.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
				}
			},
			error: function() {
				$message.html('<div class="notice notice-error"><p><?php esc_html_e( 'An error occurred. Please try again.', 'buddyboss' ); ?></p></div>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php esc_html_e( 'Deactivate License', 'buddyboss' ); ?>');
			}
		});
	});
});
</script>
