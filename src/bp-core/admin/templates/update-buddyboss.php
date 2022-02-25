<?php
/**
 * BuddyBoss Admin Screen.
 *
 * This file contains update information about BuddyBoss.
 *
 * @package BuddyBoss
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// If you have not any release note then set $show_overview as false.
$show_overview = true;

// Get release data based on plugin version from gitHub API.
$cache_key    = 'bb_changelog_' . BP_PLATFORM_VERSION;
$bb_changelog = wp_cache_get( $cache_key, 'bp' );
if ( false === $bb_changelog ) {
	$response     = wp_safe_remote_get( 'https://api.github.com/repos/buddyboss/buddyboss-platform/releases/tags/' . BP_PLATFORM_VERSION );
	$bb_changelog = wp_remote_retrieve_body( $response );
	if ( ! is_wp_error( $bb_changelog ) && ! empty( $bb_changelog ) ) {
		$bb_changelog_data = json_decode( $bb_changelog, true );
		if ( ! empty( $bb_changelog_data ) && isset( $bb_changelog_data['body'] ) ) {
			wp_cache_set( $cache_key, $bb_changelog, 'bp' );
		}
	}
}
?>
<div id="bp-hello-backdrop" style="display: none;"></div>

<div id="bp-hello-container" class="bp-hello-buddyboss bb-update-modal bb-onload-modal" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'Release Notes', 'buddyboss' ); ?></h1>
			<span class="bb-version"><?php echo esc_html__( 'Version', 'buddyboss' ) . ' ' . esc_html( BP_PLATFORM_VERSION ); ?></span>
		</div>
		<ul class="bb-hello-tabs">
			<?php
			if ( true === $show_overview ) {
				?>
				<li><a href="#bb-release-overview" class="bb-hello-tabs_anchor is_active"><?php esc_html_e( 'Overview', 'buddyboss' ); ?></a></li>
				<?php
			}
			if ( ! empty( $bb_changelog_data ) && isset( $bb_changelog_data['body'] ) ) {
				?>
				<li><a href="#bb-release-changelog" class="bb-hello-tabs_anchor"><?php esc_html_e( 'Changelog', 'buddyboss' ); ?></a></li>
				<?php
			}
			?>
		</ul>
	</div>

	<div class="bp-hello-content">
		<div id="bb-release-content" class="bb-release-content">
			<?php
			if ( true === $show_overview ) {
				?>
				<div id="bb-release-overview" class="bb-hello-tabs_content is_active">
					<p><?php esc_html_e( 'In this release, we have implemented a number of changes to the templates of profile and group headers and directories.', 'buddyboss' ); ?></p>
					<p><?php esc_html_e( 'By doing so, we are now able to offer:', 'buddyboss' ); ?></p>
					<ul class="bp-hello-list">
						<li><?php esc_html_e( 'New layout options', 'buddyboss' ); ?></li>
						<li><?php esc_html_e( 'The ability to select which elements show', 'buddyboss' ); ?></li>
						<li><?php esc_html_e( 'Numerous visual improvements suggested by customers', 'buddyboss' ); ?></li>
					</ul>
					<p><?php esc_html_e( 'To make use of these new customization options:', 'buddyboss' ); ?></p>
					<ul class="bp-hello-list">
						<li>
							<?php
							echo sprintf(
							/* translators: 1. BuddyBoss platform pro with link. 2. BuddyBoss account with link. */
								esc_html__( 'You\'ll need to install %1$s, which you can download from your %2$s', 'buddyboss' ),
								sprintf(
								/* translators: 1. BuddyBoss platform pro link. 2. BuddyBoss platform pro text. */
									'<a href="%1$s" target="_blank">%2$s</a>',
									esc_url( 'https://www.buddyboss.com/pro' ),
									esc_html__( 'BuddyBoss Platform Pro', 'buddyboss' )
								),
								sprintf(
								/* translators: 1. BuddyBoss account link. 2. BuddyBoss account text. */
									'<a href="%1$s" target="_blank">%2$s</a>',
									esc_url( 'https://my.buddyboss.com' ),
									esc_html__( 'BuddyBoss account', 'buddyboss' )
								)
							);
							?>
						</li>
						<li><?php esc_html_e( 'Update any template overrides in your child theme to use our new templates', 'buddyboss' ); ?></li>
					</ul>
					<p><?php esc_html_e( 'For more information, please watch the video below:', 'buddyboss' ); ?></p>
					<div class="video-wrapper">
						<div class="video-container">
							<iframe src="https://player.vimeo.com/video/338221385?byline=0&portrait=0&autoplay=0" width="560" height="315" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
							</iframe>
						</div>
					</div>
				</div>
				<?php
			}
			if ( ! empty( $bb_changelog_data ) && isset( $bb_changelog_data['body'] ) ) {
				?>
				<div id="bb-release-changelog" class="bb-hello-tabs_content bb-release-changelog <?php echo false === $show_overview ? 'is_active' : ''; ?>">
					<?php
					echo wp_kses_post( $bb_changelog_data['body'] );
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>
