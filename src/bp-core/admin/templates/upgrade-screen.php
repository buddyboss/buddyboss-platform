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

	<h1 class="bb-advance-heading"><?php esc_html_e( 'Unlock more social networking features for your websites', 'buddyboss' ); ?></h1>
	<div class="bb-upgrade-wrap">
		<div class="bb-advance-card bb-advance-card--hero">
			<div class="card-inner-wrap">
				<div class="card-figure-wrapper">
					<div class="card-figure">
						<img alt="" class="upgrade-figure" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-rapyd.png' ); ?>" />
					</div>
				</div>
				<div class="card-data">
					<h2><?php esc_html_e( 'Rapyd Cloud', 'buddyboss' ); ?></h2>
					<div class="card-subtitle"><?php esc_html_e( 'The highest performance managed WordPress hosting on the planet', 'buddyboss' ); ?></div>
					<div class="advance-card-note">
						<p class="wp-upgrade-description">
							<?php esc_html_e( 'Many hosting providers claim to have the best performance. But when you add dynamic features and high concurrency, website performance suffers. Rapyd keeps your feature-rich websites fast and responsive, even during periods of very high traffic.', 'buddyboss' ); ?>
						</p>
					</div>
					<div class="advance-card-action">
						<a href="
						<?php
						echo esc_url(
							bp_get_admin_url(
								add_query_arg(
									array(
										'page' => 'bb-upgrade',
										'tab'  => 'bb-performance-tester',
									),
									'admin.php'
								)
							)
						);
						?>
						" class="advance-action-button"><?php esc_html_e( 'Test Performance', 'buddyboss' ); ?></a>
						<a href="https://rapyd.cloud/?fpr=buddyboss93" class="advance-action-link" target="_blank"><?php esc_html_e( 'View Pricing', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
					</div>
				</div>
			</div>
		</div>

		<div class="bb-advance-card bb-advance-card--pro">
			<div class="card-inner-wrap">
				<div class="card-figure">
					<img alt="" class="upgrade-figure" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-pro.png' ); ?>" />
				</div>
				<div class="card-data">
					<h2><?php esc_html_e( 'BuddyBoss Platform Pro', 'buddyboss' ); ?></h2>
					<div class="card-subtitle"><?php esc_html_e( 'Unlock social networking features for your website', 'buddyboss' ); ?></div>
					<div class="advance-card-note">
						<ul class="advance-list">
							<li><?php esc_html_e( 'Member Profiles', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Polls', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Social Groups', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Member Connections', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Email Notifications', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Reactions', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Forum Discussions', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Private Messaging', 'buddyboss' ); ?></li>
							<li class="advance-list__expand"><?php esc_html_e( 'Activity Feeds', 'buddyboss' ); ?></li>
							<li class="advance-list__expand"><?php esc_html_e( 'Media Uploading', 'buddyboss' ); ?></li>
						</ul>
					</div>
					<div class="advance-card-action <?php echo $bb_platform_pro_active ? 'advance-action-success' : ''; ?>">
						<a href="https://www.buddyboss.com/bbwebupgrade" class="advance-action-button <?php echo ( ! $bb_platform_pro_active ) ? '' : 'advance-action-button--idle'; ?>"><?php ( ! $bb_platform_pro_active ) ? esc_html_e( 'Upgrade to Platform Pro', 'buddyboss' ) : esc_html_e( 'Activated', 'buddyboss' ); ?></a>
						<a href="https://www.buddyboss.com/bbweblearn" class="advance-action-link" target="_blank"><?php esc_html_e( 'Learn More', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
					</div>
				</div>
			</div>
		</div>

		<div class="bb-advance-card bb-advance-card--theme">
			<div class="card-inner-wrap">
				<div class="card-figure">
					<img alt="" class="upgrade-figure" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/upgrade/bb-upgrade-card-theme.png' ); ?>" />
				</div>
				<div class="card-data">
					<h2><?php esc_html_e( 'BuddyBoss Theme', 'buddyboss' ); ?></h2>
					<div class="card-subtitle"><?php esc_html_e( 'Get an elegant design with powerful features for your course and community', 'buddyboss' ); ?></div>
					<div class="advance-card-note">
						<ul class="advance-list">
							<li><?php esc_html_e( 'Premium Interface', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Plug & Play Sites', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Premium LearnDash Interface', 'buddyboss' ); ?></li>
							<li><?php esc_html_e( 'Plug & Play Sections', 'buddyboss' ); ?></li>
							<li class="advance-list__expand"><?php esc_html_e( 'Member/Student Dashboard', 'buddyboss' ); ?></li>
							<li class="advance-list__expand"><?php esc_html_e( 'Events Calendar Interface', 'buddyboss' ); ?></li>
						</ul>
					</div>
					<div class="advance-card-action <?php echo $bb_theme_active ? 'advance-action-success' : ''; ?>">
						<a href="https://www.buddyboss.com/bbwebupgrade" class="advance-action-button <?php echo ( ! $bb_theme_active ) ? '' : 'advance-action-button--idle'; ?>"><?php ( ! $bb_theme_active ) ? esc_html_e( 'Get BuddyBoss Theme', 'buddyboss' ) : esc_html_e( 'Activated', 'buddyboss' ); ?></a>
						<a href="https://www.buddyboss.com/bbweblearn" class="advance-action-link" target="_blank"><?php esc_html_e( 'Learn More', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
