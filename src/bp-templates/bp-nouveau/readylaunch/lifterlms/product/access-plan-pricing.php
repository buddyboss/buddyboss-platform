<?php
/**
 * Template for displaying access plan pricing in ReadyLaunch
 *
 * @since BuddyBoss 2.9.00
 * @package BuddyBoss\ReadyLaunch
 */

defined( 'ABSPATH' ) || exit;



if ( ! isset( $pricing ) || empty( $pricing ) ) {
	return;
}
?>

<div class="bb-rl-lifterlms-access-plan-pricing">
	<?php if ( $pricing['is_free'] ) : ?>
		<div class="bb-rl-lifterlms-price-free">
			<span class="bb-rl-lifterlms-price-amount"><?php _e( 'Free', 'buddyboss' ); ?></span>
		</div>
	<?php else : ?>
		<div class="bb-rl-lifterlms-price-paid">
			<?php if ( $pricing['is_on_sale'] ) : ?>
				<span class="bb-rl-lifterlms-price-original"><?php echo $pricing['regular_price']; ?></span>
			<?php endif; ?>
			<span class="bb-rl-lifterlms-price-current"><?php echo $pricing['current_price']; ?></span>
			<?php if ( $pricing['sale_price'] ) : ?>
				<span class="bb-rl-lifterlms-price-sale"><?php echo $pricing['sale_price']; ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div> 