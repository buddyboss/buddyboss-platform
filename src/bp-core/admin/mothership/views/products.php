<?php
/**
 * Products view template for displaying available add-ons.
 *
 * @package BuddyBoss\Core\Admin\Mothership
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="mosh-admin-addons" class="wrap">
	<h3>
		<form method="post" action="">
			<input type="submit"
				class="button button-secondary"
				name="submit-button-mosh-refresh-addon"
				value="<?php esc_attr_e( 'Refresh Add-ons', 'buddyboss' ); ?>"
			>
			<input type="search"
				id="mosh-products-search"
				placeholder="<?php esc_attr_e( 'Search add-ons', 'buddyboss' ); ?>"
			>
		</form>
	</h3>
	<?php if ( ! empty( $products ) ) : ?>
		<div id="mosh-products-container">
			<div class="mosh-products">
				<?php foreach ( $products as $product ) : ?>
				<div class="mosh-product mosh-product-status-<?php echo esc_attr( $product->status ); ?>">
					<div class="mosh-product-inner">
						<?php if ( $product->updateAvailable ) : ?>
						<div class="update-message notice inline notice-warning notice-alt mosh-product-update-message">
							<p>
								<?php esc_html_e( 'New version available.', 'buddyboss' ); ?>
								<button class="button-link mosh-product-update-button" type="button"><?php esc_html_e( 'Update now', 'buddyboss' ); ?></button>
							</p>
						</div>
						<?php endif; ?>
						<div class="mosh-product-details">
							<div class="mosh-product-image">
								<img src="<?php echo esc_url( $product->image ); ?>"
									alt="<?php echo esc_attr( $product->list_name ); ?>"
								>
							</div>
							<div class="mosh-product-info">
								<h2 class="mosh-product-name">
									<?php echo esc_html( $product->name ); ?>
								</h2>
								<p><?php echo esc_html( $product->description ); ?></p>
							</div>
						</div>
						<div class="mosh-product-actions mosh-clearfix">
							<div class="mosh-product-status">
								<strong>
									<?php
									printf(
										// Translators: %s: add-on status label.
										esc_html__( 'Status: %s', 'buddyboss' ),
										sprintf( '<span class="mosh-product-status-label">%s</span>', esc_html( $product->statusLabel ) )
									);
									?>
								</strong>
							</div>
							<div class="mosh-product-action">
								<button type="button"
									data-slug="<?php echo esc_attr( $product->slug ); ?>"
									data-extension-type="<?php echo esc_attr( $product->extension_type ); ?>"
								>
									<i class="<?php echo esc_attr( $product->iconClass ); ?>"></i>
									<?php echo esc_html( $product->buttonLabel ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php else : ?>
		<h3><?php esc_html_e( 'There were no Add-ons found for your License Key.', 'buddyboss' ); ?></h3>
	<?php endif; ?>
</div>
