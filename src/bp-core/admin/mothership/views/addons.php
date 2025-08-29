<?php
/**
 * Addons display template
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Variables passed to this template.
$products = isset( $products ) ? $products : array();
?>

<div class="bb-addons-container">
	
	<div class="bb-addons-header">
		<form method="post" class="bb-addons-refresh">
			<input type="submit" name="bb_refresh_addons" value="<?php esc_attr_e( 'Refresh Add-ons', 'buddyboss' ); ?>" class="button button-secondary" />
		</form>
	</div>

	<?php if ( empty( $products ) ) : ?>
		<div class="notice notice-info">
			<p><?php esc_html_e( 'No add-ons available at this time.', 'buddyboss' ); ?></p>
		</div>
	<?php else : ?>
		<div class="bb-addons-grid">
			<?php foreach ( $products as $product ) : ?>
				<?php
				$plugin_file = isset( $product->main_file ) ? $product->main_file : '';
				$is_installed = ! empty( $plugin_file ) && file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
				$is_active = $is_installed && is_plugin_active( $plugin_file );
				
				// Get version info.
				$latest_version = isset( $product->_embedded->{'version-latest'}->number ) 
					? $product->_embedded->{'version-latest'}->number 
					: '';
				$download_url = isset( $product->_embedded->{'version-latest'}->url ) 
					? $product->_embedded->{'version-latest'}->url 
					: '';
				
				// Get current version if installed.
				$current_version = '';
				if ( $is_installed ) {
					$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false );
					$current_version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';
				}
				
				$has_update = $is_installed && version_compare( $current_version, $latest_version, '<' );
				?>
				
				<div class="bb-addon-card" data-plugin="<?php echo esc_attr( $plugin_file ); ?>">
					<div class="bb-addon-card-header">
						<?php if ( ! empty( $product->image ) ) : ?>
							<img src="<?php echo esc_url( $product->image ); ?>" alt="<?php echo esc_attr( $product->name ); ?>" class="bb-addon-icon" />
						<?php else : ?>
							<div class="bb-addon-icon-placeholder">
								<span class="dashicons dashicons-admin-plugins"></span>
							</div>
						<?php endif; ?>
					</div>
					
					<div class="bb-addon-card-body">
						<h3 class="bb-addon-title"><?php echo esc_html( $product->name ); ?></h3>
						
						<?php if ( ! empty( $product->description ) ) : ?>
							<p class="bb-addon-description"><?php echo esc_html( $product->description ); ?></p>
						<?php endif; ?>
						
						<div class="bb-addon-meta">
							<?php if ( $latest_version ) : ?>
								<span class="bb-addon-version">
									<?php esc_html_e( 'Version:', 'buddyboss' ); ?> 
									<?php echo esc_html( $latest_version ); ?>
								</span>
							<?php endif; ?>
							
							<?php if ( $is_installed && $current_version ) : ?>
								<span class="bb-addon-current-version">
									<?php esc_html_e( 'Installed:', 'buddyboss' ); ?> 
									<?php echo esc_html( $current_version ); ?>
								</span>
							<?php endif; ?>
						</div>
						
						<?php if ( $has_update ) : ?>
							<div class="bb-addon-update-notice">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Update available', 'buddyboss' ); ?>
							</div>
						<?php endif; ?>
					</div>
					
					<div class="bb-addon-card-footer">
						<div class="bb-addon-status">
							<?php if ( $is_active ) : ?>
								<span class="bb-addon-status-badge bb-addon-status-active">
									<?php esc_html_e( 'Active', 'buddyboss' ); ?>
								</span>
							<?php elseif ( $is_installed ) : ?>
								<span class="bb-addon-status-badge bb-addon-status-inactive">
									<?php esc_html_e( 'Inactive', 'buddyboss' ); ?>
								</span>
							<?php else : ?>
								<span class="bb-addon-status-badge bb-addon-status-not-installed">
									<?php esc_html_e( 'Not Installed', 'buddyboss' ); ?>
								</span>
							<?php endif; ?>
						</div>
						
						<div class="bb-addon-actions">
							<?php if ( $is_active ) : ?>
								<button class="button bb-addon-deactivate" 
									data-plugin="<?php echo esc_attr( $plugin_file ); ?>"
									data-type="<?php echo isset( $product->type ) ? esc_attr( $product->type ) : 'add-on'; ?>">
									<?php esc_html_e( 'Deactivate', 'buddyboss' ); ?>
								</button>
							<?php elseif ( $is_installed ) : ?>
								<button class="button button-primary bb-addon-activate" 
									data-plugin="<?php echo esc_attr( $plugin_file ); ?>"
									data-type="<?php echo isset( $product->type ) ? esc_attr( $product->type ) : 'add-on'; ?>">
									<?php esc_html_e( 'Activate', 'buddyboss' ); ?>
								</button>
							<?php elseif ( $download_url ) : ?>
								<button class="button button-primary bb-addon-install" 
									data-plugin="<?php echo esc_attr( $download_url ); ?>"
									data-type="<?php echo isset( $product->type ) ? esc_attr( $product->type ) : 'add-on'; ?>">
									<?php esc_html_e( 'Install', 'buddyboss' ); ?>
								</button>
							<?php endif; ?>
							
							<?php if ( $has_update ) : ?>
								<a href="<?php echo esc_url( admin_url( 'plugins.php?plugin_status=upgrade' ) ); ?>" 
									class="button">
									<?php esc_html_e( 'Update', 'buddyboss' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>