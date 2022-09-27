<?php
/**
 * BuddyBoss Admin Screen.
 *
 * This file contains update information about BuddyBoss.
 *
 * @package BuddyBoss
 * @since   BuddyBoss 1.9.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// If you have not any release note then set $show_overview as false.
$show_overview = false;

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

// If you have any video then add url here.
$video_url = 'https://www.youtube.com/embed/ThTdHOYwNxU';
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
			<span class="bb-version"><?php echo esc_html__( 'BuddyBoss Platform v', 'buddyboss' ) . esc_html( BP_PLATFORM_VERSION ); ?></span>
		</div>
		<ul class="bb-hello-tabs">
			<?php
			if ( true === $show_overview ) {
				?>
				<li><a href="#bb-release-overview" class="bb-hello-tabs_anchor is_active" data-action="bb-release-overview"><?php esc_html_e( 'Overview', 'buddyboss' ); ?></a></li>
				<?php
			}
			if ( ! empty( $bb_changelog_data ) && isset( $bb_changelog_data['body'] ) ) {
				?>
				<li><a href="#bb-release-changelog" class="bb-hello-tabs_anchor" data-action="bb-release-changelog"><?php esc_html_e( 'Changelog', 'buddyboss' ); ?></a></li>
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
					<h3><?php esc_html_e( 'Welcome to BuddyBoss Theme 2.0 🥳', 'buddyboss' ); ?></h3>
					<p><?php esc_html_e( 'Check out the video below for a full walkthrough of all the new features and updates available to you in this release.', 'buddyboss' ); ?></p>
					<p>
						<?php
						echo sprintf(
							esc_html__( 'As this update contains a number of improvements to the theme’s colors, layouts and styling, we recommend you reconfigure your Theme Options and review any custom CSS you may have.  For more information on how to update, %1$s.', 'buddyboss' ),
							sprintf(
								'<a href="%1$s" target="_blank">%2$s</a>',
								esc_url( 'https://www.buddyboss.com/resources/docs/buddyboss-theme/getting-started/updating-to-buddyboss-theme-2-0' ),
								esc_html__( 'check out this tutorial', 'buddyboss' )
							)
						);
						?>
					</p>
					<?php
					if ( ! empty( $video_url ) ) {
						?>
						<p><?php esc_html_e( 'For more information, please watch the video below:', 'buddyboss' ); ?></p>
						<div class="video-wrapper">
							<div class="video-container">
								<iframe width="560" height="315" src="<?php echo esc_url( $video_url ); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			if ( ! empty( $bb_changelog_data ) && isset( $bb_changelog_data['body'] ) ) {
				?>
				<div id="bb-release-changelog" class="bb-hello-tabs_content bb-release-changelog <?php echo esc_attr( false === $show_overview ? 'is_active' : '' ); ?>">
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
