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

$bb_platform_pro_active = false;
$bb_theme_active        = false;
if ( function_exists( 'bbp_pro_is_license_valid' ) && bbp_pro_is_license_valid() ) {
	$bb_platform_pro_active = true;
}

if ( function_exists( 'buddyboss_theme' ) ) {
	$bb_theme_active = true;
}

?>
<div class="wrap">

	<div id="bb-upgrade">

		<h1 class="bb-advance-heading"><?php _e( 'Unlock more social networking features for your websites', 'buddyboss' ); ?></h1>
		<div class="bb-upgrade-wrap">
			<div class="bb-advance-card bb-advance-card--hero">
				<div class="card-inner-wrap">
					<div class="card-figure-wrapper">
						<div class="card-figure">
							<?php echo '<img alt="" class="upgrade-figure" src="' . buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-rapyd.png' . '" />'; ?>
						</div>
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
							<a href="
							<?php
							echo bp_get_admin_url(
								add_query_arg(
									array(
										'page' => 'bb-upgrade',
										'tab'  => 'bb-performance-tester',
									),
									'admin.php'
								)
							);
							?>
							" class="advance-action-button"><?php _e( 'Test Performance', 'buddyboss' ); ?></a>
							<a href="https://rapyd.cloud/?fpr=buddyboss93" class="advance-action-link" target="_blank"><?php _e( 'View Pricing', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
						</div>
					</div>
				</div>
			</div>

			<div class="bb-advance-card bb-advance-card--pro">
				<div class="card-inner-wrap">
					<div class="card-figure">
						<?php echo '<img alt="" class="upgrade-figure" src="' . buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-pro.png' . '" />'; ?>
					</div>
					<div class="card-data">
						<h2><?php _e( 'BuddyBoss Platform Pro', 'buddyboss' ); ?></h2>
						<div class="card-subtitle"><?php _e( 'Unlock social networking features for your website', 'buddyboss' ); ?></div>
						<div class="advance-card-note">
							<ul class="advance-list">
								<li><?php _e( 'Member Profiles', 'buddyboss' ); ?></li>
								<li><?php _e( 'Polls', 'buddyboss' ); ?></li>
								<li><?php _e( 'Social groups', 'buddyboss' ); ?></li>
								<li><?php _e( 'Member connections', 'buddyboss' ); ?></li>
								<li><?php _e( 'Email notifications', 'buddyboss' ); ?></li>
								<li><?php _e( 'Reactions', 'buddyboss' ); ?></li>
								<li><?php _e( 'Forum discussions', 'buddyboss' ); ?></li>
								<li><?php _e( 'Private messaging', 'buddyboss' ); ?></li>
								<li class="advance-list__expand"><?php _e( 'Activity feeds', 'buddyboss' ); ?></li>
								<li class="advance-list__expand"><?php _e( 'Media uploading', 'buddyboss' ); ?></li>
							</ul>
						</div>
						<div class="advance-card-action <?php echo $bb_platform_pro_active ? 'advance-action-success' : ''; ?>">
							<a href="https://www.buddyboss.com/website-platform/#platform_pricing_box" class="advance-action-button <?php echo ( ! $bb_platform_pro_active ) ? "" : "advance-action-button--idle"; ?>" target="_blank"><?php ( ! $bb_platform_pro_active ) ? esc_html_e( 'Upgrade to Platform Pro', 'buddyboss' ) : esc_html_e( 'Activated', 'buddyboss' ); ?></a>
							<a href="https://www.buddyboss.com/website-platform/" class="advance-action-link" target="_blank"><?php _e( 'Learn More', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
						</div>
					</div>
				</div>
			</div>

			<div class="bb-advance-card bb-advance-card--theme">
				<div class="card-inner-wrap">
					<div class="card-figure">
						<?php echo '<img alt="" class="upgrade-figure" src="' . buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-theme.png' . '" />'; ?>
					</div>
					<div class="card-data">
						<h2><?php _e( 'BuddyBoss Theme', 'buddyboss' ); ?></h2>
						<div class="card-subtitle"><?php _e( 'Get an elegant design with powerful features for your course and community', 'buddyboss' ); ?></div>
						<div class="advance-card-note">
							<ul class="advance-list">
								<li><?php _e( 'Premium interface', 'buddyboss' ); ?></li>
								<li><?php _e( 'Plug & play sites', 'buddyboss' ); ?></li>
								<li><?php _e( 'Premium learnDash interface', 'buddyboss' ); ?></li>
								<li><?php _e( 'Plug & play sections', 'buddyboss' ); ?></li>
								<li class="advance-list__expand"><?php _e( 'Member/student dashboard', 'buddyboss' ); ?></li>
								<li class="advance-list__expand"><?php _e( 'Events calendar interface', 'buddyboss' ); ?></li>
							</ul>
						</div>
						<div class="advance-card-action <?php echo $bb_theme_active ? 'advance-action-success' : ''; ?>">
							<a href="https://www.buddyboss.com/website-platform/#platform_pricing_box" class="advance-action-button <?php echo ( ! $bb_theme_active ) ? '' : 'advance-action-button--idle'; ?>"><?php ( ! $bb_theme_active ) ? esc_html_e( 'Get BuddyBoss Theme', 'buddyboss' ) : esc_html_e( 'Activated', 'buddyboss' ); ?></a>
							<a href="https://www.buddyboss.com/website-platform/" class="advance-action-link" target="_blank"><?php _e( 'Learn More', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>

	<div id="bb-performance"></div>
</div>
