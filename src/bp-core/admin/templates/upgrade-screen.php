<?php
/**
 * BuddyBoss Upgrade Admin Screen.
 *
 * This file contains information about BuddyBoss Upgrade.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="wrap bp-upgrade-wrap">

	<div class="bb-advance-card bb-advance-card--hero">
		<div class="card-inner-wrap">
			<div class="card-figure">
				<?php echo '<img alt="" class="upgrade-figure" src="' . buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-rapyd.png' . '" />'; ?>
			</div>
			<div class="card-data">
				<h2><?php _e( 'Rapyd Cloud', 'buddyboss' ); ?></h2>
				<div class="card-subtitle"><?php _e( 'The highest performance managed WordPress hosting on the planet', 'buddyboss' ); ?></div>
				<div class="advance-card-note">
					<p class="wp-upgrade-description">
						<?php _e( 'Many hosting providers claim to have the best performance. But when you add dynamicfeatures and high concurrency, website performance suffers. Rapyd keeps your feature-richwebsites fast and responsive, even during periods of very high traffic.', 'buddyboss' ); ?>
					</p>
				</div>
				<div class="advance-card-action">
					<button class="advance-action"><?php _e( 'Test Performance', 'buddyboss' ); ?></button>
					<a href="https://rapyd.cloud/pricing/" class="advance-action-link" target="_blank"><?php _e( 'View Pricing', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
				</div>
			</div>
		</div>
	</div>

	<div class="bb-advance-card">
		<div class="card-inner-wrap">
			<div class="card-figure">
				<?php echo '<img alt="" class="upgrade-figure" src="' . buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-pro.png' . '" />'; ?>
			</div>
			<div class="card-data">
				<h2><?php _e( 'BuddyBoss Platform Pro', 'buddyboss' ); ?></h2>
				<div class="card-subtitle"><?php _e( 'Unlock social networking features for your website', 'buddyboss' ); ?></div>
				<div class="advance-card-note">
					<p class="wp-upgrade-description">
						<?php _e( 'Many hosting providers claim to have the best performance. But when you add dynamicfeatures and high concurrency, website performance suffers. Rapyd keeps your feature-richwebsites fast and responsive, even during periods of very high traffic.', 'buddyboss' ); ?>
					</p>
				</div>
				<div class="advance-card-action">
					<button class="advance-action"><?php _e( 'Upgrade to Platform Pro', 'buddyboss' ); ?></button>
					<a href="#" class="advance-action-link" target="_blank"><?php _e( 'Learn More', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
				</div>
			</div>
		</div>
	</div>

	<div class="bb-advance-card">
		<div class="card-inner-wrap">
			<div class="card-figure">
				<?php echo '<img alt="" class="upgrade-figure" src="' . buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-theme.png' . '" />'; ?>
			</div>
			<div class="card-data">
				<h2><?php _e( 'BuddyBoss Theme', 'buddyboss' ); ?></h2>
				<div class="card-subtitle"><?php _e( 'Get an elegant design with powerful features for your course and community', 'buddyboss' ); ?></div>
				<div class="advance-card-note">
					<p class="wp-upgrade-description">
						<?php _e( 'Many hosting providers claim to have the best performance. But when you add dynamicfeatures and high concurrency, website performance suffers. Rapyd keeps your feature-richwebsites fast and responsive, even during periods of very high traffic.', 'buddyboss' ); ?>
					</p>
				</div>
				<div class="advance-card-action">
					<button class="advance-action"><?php _e( 'Get BuddyBoss Theme', 'buddyboss' ); ?></button>
					<a href="#" class="advance-action-link" target="_blank"><?php _e( 'Learn More', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
				</div>
			</div>
		</div>
	</div>

</div>
