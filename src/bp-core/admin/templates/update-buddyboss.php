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
			<span><?php echo esc_html__( 'Version', 'buddyboss' ) . ' ' . esc_html( BP_PLATFORM_VERSION ); ?></span>
		</div>
	</div>

	<div class="bp-hello-content">
		<div id="bb-release-content" class="bb-release-content">
			<ul>
				<li><a href="#bb-release-overview"><?php esc_html_e( 'Overview', 'buddyboss' ); ?></a></li>
				<li><a href="#bb-release-changelog"><?php esc_html_e( 'Changelog', 'buddyboss' ); ?></a></li>
			</ul>
			<div id="bb-release-overview">
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
						printf(
							__( 'You\'ll need to install <a href="%1$s" target="_blank">BuddyBoss Platform Pro</a>, 
						which you can download from your <a href="%2$s" target="_blank">BuddyBoss account</a>', 'buddyboss' ),
							esc_url( 'https://www.buddyboss.com/pro' ),
							esc_url( 'https://my.buddyboss.com' )
						);
						?>
					</li>
					<li><?php esc_html_e( 'Update any template overrides in your child theme to use our new templates', 'buddyboss' ); ?></li>
				</ul>
				<p><?php esc_html_e( 'For more information, please watch the video below:', 'buddyboss' ); ?></p>
				<div class="video-wrapper">
					<div class="video-container">
						<iframe src="https://player.vimeo.com/video/338221385?byline=0&portrait=0&autoplay=0" width="560" height="315"
							frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
						</iframe>
					</div>
				</div>
			</div>
			<div id="bb-release-changelog">
				<p><?php esc_html_e( 'Changes:', 'buddyboss' ); ?></p>
				<ul class="bp-hello-list">
					<li><?php esc_html_e( 'New layout options', 'buddyboss' ); ?></li>
					<li><?php esc_html_e( 'The ability to select which elements show', 'buddyboss' ); ?></li>
					<li><?php esc_html_e( 'Numerous visual improvements suggested by customers', 'buddyboss' ); ?></li>
				</ul>
			</div>
		</div>

	</div>
</div>
