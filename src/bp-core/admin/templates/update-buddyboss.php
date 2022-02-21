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
<div id="bp-update-backdrop" class="bp-update-backdrop-buddyboss" style="display: none;"></div>

<div id="bp-update-container" class="bp-hello-buddyboss" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'Important Information', 'buddyboss' ); ?></h1>
		</div>
	</div>

	<div class="bp-hello-content">
		<p><?php _e( 'In this release, we have implemented a number of changes to the templates of profile and group headers and directories.', 'buddyboss' ); ?></p>
		<p><?php _e( 'By doing so, we are now able to offer:', 'buddyboss' ); ?></p>
		<ul class="bp-hello-list">
			<li><?php _e( 'New layout options', 'buddyboss' ); ?></li>
			<li><?php _e( 'The ability to select which elements show', 'buddyboss' ); ?></li>
			<li><?php _e( 'Numerous visual improvements suggested by customers', 'buddyboss' ); ?></li>
		</ul>
		<p><?php _e( 'To make use of these new customization options:', 'buddyboss' ); ?></p>
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
			<li><?php _e( 'Update any template overrides in your child theme to use our new templates', 'buddyboss' ); ?></li>
		</ul>
		<p><?php _e( 'For more information, please watch the video below:', 'buddyboss' ); ?></p>
		<div class="video-wrapper">
			<div class="video-container">
				<iframe src="https://player.vimeo.com/video/338221385?byline=0&portrait=0&autoplay=0" width="560" height="315"
						frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
				</iframe>
			</div>
		</div>
	</div>
</div>
