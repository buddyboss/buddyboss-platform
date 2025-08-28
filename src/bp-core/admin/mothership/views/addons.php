<?php
/**
 * BuddyBoss Platform Mothership Addons Template
 *
 * @package BuddyBoss Platform
 */

defined( 'ABSPATH' ) || exit;

$addons = isset( $addons ) ? $addons : array();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'BuddyBoss Platform Add-ons', 'buddyboss' ); ?></h1>

	<p><?php esc_html_e( 'Enhance your BuddyBoss Platform with premium add-ons and integrations.', 'buddyboss' ); ?></p>

	<div class="bb-platform-mothership-addons">
		<?php if ( ! empty( $addons ) ) : ?>
			<div class="bb-platform-addons-grid">
				<?php foreach ( $addons as $addon ) : ?>
					<div class="bb-platform-addon-card" data-addon-slug="<?php echo esc_attr( $addon['slug'] ); ?>">
						<div class="addon-header">
							<?php if ( isset( $addon['icon'] ) ) : ?>
								<img src="<?php echo esc_url( $addon['icon'] ); ?>" alt="<?php echo esc_attr( $addon['name'] ); ?>" class="addon-icon" />
							<?php endif; ?>

							<h3 class="addon-name"><?php echo esc_html( $addon['name'] ); ?></h3>

							<div class="addon-status">
								<?php
								$status      = isset( $addon['status'] ) ? $addon['status'] : 'not-installed';
								$status_text = '';

								switch ( $status ) {
									case 'active':
										$status_text  = __( 'Active', 'buddyboss' );
										$status_class = 'status-active';
										break;
									case 'inactive':
										$status_text  = __( 'Inactive', 'buddyboss' );
										$status_class = 'status-inactive';
										break;
									case 'installed':
										$status_text  = __( 'Installed', 'buddyboss' );
										$status_class = 'status-installed';
										break;
									default:
										$status_text  = __( 'Not Installed', 'buddyboss' );
										$status_class = 'status-not-installed';
										break;
								}
								?>
								<span class="addon-status-badge <?php echo esc_attr( $status_class ); ?>">
									<?php echo esc_html( $status_text ); ?>
								</span>
							</div>
						</div>

						<div class="addon-content">
							<?php if ( isset( $addon['description'] ) ) : ?>
								<p class="addon-description"><?php echo esc_html( $addon['description'] ); ?></p>
							<?php endif; ?>

							<?php if ( isset( $addon['version'] ) ) : ?>
								<p class="addon-version">
									<strong><?php esc_html_e( 'Version:', 'buddyboss' ); ?></strong>
									<?php echo esc_html( $addon['version'] ); ?>
								</p>
							<?php endif; ?>
						</div>

						<div class="addon-actions">
							<?php if ( 'active' === $status ) : ?>
								<button type="button" class="button button-secondary addon-action" data-action="deactivate" data-plugin="<?php echo esc_attr( $addon['plugin_file'] ); ?>">
									<?php esc_html_e( 'Deactivate', 'buddyboss' ); ?>
								</button>
							<?php elseif ( 'inactive' === $status ) : ?>
								<button type="button" class="button button-primary addon-action" data-action="activate" data-plugin="<?php echo esc_attr( $addon['plugin_file'] ); ?>">
									<?php esc_html_e( 'Activate', 'buddyboss' ); ?>
								</button>
							<?php elseif ( 'installed' === $status ) : ?>
								<button type="button" class="button button-primary addon-action" data-action="activate" data-plugin="<?php echo esc_attr( $addon['plugin_file'] ); ?>">
									<?php esc_html_e( 'Activate', 'buddyboss' ); ?>
								</button>
							<?php else : ?>
								<button type="button" class="button button-primary addon-action" data-action="install" data-slug="<?php echo esc_attr( $addon['slug'] ); ?>">
									<?php esc_html_e( 'Install', 'buddyboss' ); ?>
								</button>
							<?php endif; ?>

							<?php if ( isset( $addon['documentation_url'] ) ) : ?>
								<a href="<?php echo esc_url( $addon['documentation_url'] ); ?>" target="_blank" class="button button-link">
									<?php esc_html_e( 'Documentation', 'buddyboss' ); ?>
								</a>
							<?php endif; ?>
						</div>

						<div class="addon-message"></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="bb-platform-no-addons">
				<p><?php esc_html_e( 'No add-ons available at this time.', 'buddyboss' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

<script type="text/javascript">
// Ensure bbPlatformMothershipAddons is available
if (typeof bbPlatformMothershipAddons === 'undefined') {
	window.bbPlatformMothershipAddons = {
		ajaxUrl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		nonce: '<?php echo wp_create_nonce( 'bb_platform_mothership_addons_nonce' ); ?>',
		strings: {
			installing: '<?php esc_html_e( 'Installing...', 'buddyboss' ); ?>',
			activating: '<?php esc_html_e( 'Activating...', 'buddyboss' ); ?>',
			deactivating: '<?php esc_html_e( 'Deactivating...', 'buddyboss' ); ?>',
			error: '<?php esc_html_e( 'An error occurred. Please try again.', 'buddyboss' ); ?>'
		}
	};
}

jQuery(document).ready(function($) {
	$('.addon-action').on('click', function() {
		var $button = $(this);
		var $card = $button.closest('.bb-platform-addon-card');
		var $message = $card.find('.addon-message');
		var action = $button.data('action');
		var data = {
			action: 'bb_platform_' + action + '_addon',
			nonce: bbPlatformMothershipAddons.nonce
		};

		// Add action-specific data
		if (action === 'install') {
			data.addon_slug = $button.data('slug');
		} else {
			data.plugin_file = $button.data('plugin');
		}

		$button.prop('disabled', true);
		$message.html('');

		$.ajax({
			url: bbPlatformMothershipAddons.ajaxUrl,
			type: 'POST',
			data: data,
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
				$button.prop('disabled', false);
			}
		});
	});
});
</script>
