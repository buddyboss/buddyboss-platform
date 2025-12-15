<?php
namespace BuddyBoss\Core\Admin\Mothership;

?>
<div class="wrap buddyboss-mothership-wrap">

	<h2><?php echo esc_html( BB_License_Page::pageTitle() ); ?></h2>

	<div class="buddyboss-mothership-block-container">
		<div class="buddyboss-mothership-block">
			<div class="inside">
				<h2><?php esc_html_e( 'Manual Connect', 'buddyboss' ); ?></h2>
				<p>
					<li>
						<?php printf( __( 'Log into %s', 'buddyboss' ), '<a href="https://my.buddyboss.com/wp-admin">BuddyBoss.com</a>' ); ?>
					</li>
					<li>
						<?php printf( __( 'Go to your %s', 'buddyboss' ), '<a href="https://my.buddyboss.com/my-account/">Account</a>' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Go to the "Subscriptions" tab', 'buddyboss' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Find your product\'s license key', 'buddyboss' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Enter your license key below', 'buddyboss' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Enter your BuddyBoss account email', 'buddyboss' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Click "Update License"', 'buddyboss' ); ?>
					</li>
				</p>
			</div>
		</div>

		<div class="buddyboss-mothership-block">
			<div class="inside">
				<h2><?php esc_html_e( 'Benefits of a License', 'buddyboss' ); ?></h2>
				<ul>
					<li>
						<strong><?php esc_html_e( 'Stay Up to Date', 'buddyboss' ); ?></strong><br/>
						<?php esc_html_e( 'Get the latest features right away', 'buddyboss' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Admin Notifications', 'buddyboss' ); ?></strong><br/>
						<?php esc_html_e( 'Get updates in WordPress', 'buddyboss' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Professional Support', 'buddyboss' ); ?></strong><br/>
						<?php esc_html_e( 'Get help with any questions', 'buddyboss' ); ?>
					</li>
				</ul>
			</div>
		</div>

	</div>

	<div class='buddyboss-mothership-settings clearfix'>
		<?php
			// Use our custom BB_License_Manager instead of the base LicenseManager.
			$licenseManager = new BB_License_Manager();
			echo '<div class="setting-wrapper">';
			echo $licenseManager->generateLicenseActivationForm(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		?>
	</div><!-- .buddyboss-mothership-settings -->

	<!-- Reset License Settings Section -->
	<div class="buddyboss-mothership-reset-section" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4;">
		<h3><?php esc_html_e( 'Troubleshooting', 'buddyboss' ); ?></h3>
		<p><?php esc_html_e( 'If you\'re experiencing activation issues, you can reset all license settings and try again.', 'buddyboss' ); ?></p>
		<p><strong><?php esc_html_e( 'Warning:', 'buddyboss' ); ?></strong> <?php esc_html_e( 'This will clear all license data including activation status. You will need to re-activate your license after resetting.', 'buddyboss' ); ?></p>
		<button type="button" id="bb-reset-license-btn" class="button button-secondary">
			<?php esc_html_e( 'Reset License Settings', 'buddyboss' ); ?>
		</button>
		<span id="bb-reset-license-spinner" class="spinner" style="float: none; margin: 0 10px;"></span>
		<div id="bb-reset-license-message" style="margin-top: 10px;"></div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#bb-reset-license-btn').on('click', function(e) {
			e.preventDefault();

			// Confirm action
			if (!confirm('<?php echo esc_js( __( 'Are you sure you want to reset all license settings? This will deactivate your license and clear all stored data.', 'buddyboss' ) ); ?>')) {
				return;
			}

			var $btn = $(this);
			var $spinner = $('#bb-reset-license-spinner');
			var $message = $('#bb-reset-license-message');

			// Show loading state
			$btn.prop('disabled', true);
			$spinner.addClass('is-active');
			$message.html('');

			// Make AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'bb_reset_license_settings',
					nonce: '<?php echo esc_js( wp_create_nonce( 'bb_reset_license_settings' ) ); ?>'
				},
				success: function(response) {
					if (response.success) {
						$message.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');

						// Reload the page after 2 seconds to show the clean state
						setTimeout(function() {
							window.location.reload();
						}, 2000);
					} else {
						$message.html('<div class="notice notice-error inline"><p><strong><?php esc_html_e( 'Error:', 'buddyboss' ); ?></strong> ' + response.data + '</p></div>');
					}
				},
				error: function() {
					$message.html('<div class="notice notice-error inline"><p><strong><?php esc_html_e( 'Error:', 'buddyboss' ); ?></strong> <?php esc_html_e( 'An error occurred while resetting license settings.', 'buddyboss' ); ?></p></div>');
				},
				complete: function() {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
				}
			});
		});
	});
	</script>

</div>
