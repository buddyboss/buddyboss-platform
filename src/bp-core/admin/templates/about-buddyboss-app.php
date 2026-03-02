<?php
/**
 * BuddyBoss App Admin Screen.
 *
 * This file contains information about the BuddyBoss App.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div id="bp-hello-backdrop" style="display: none;"></div>

<div id="bp-hello-container" class="bp-hello-buddyboss-app bb-onload-modal" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'Video Demo of BuddyBoss App', 'buddyboss' ); ?></h1>
		</div>
	</div>

	<div class="bp-hello-content">

		<div class="video-wrapper">
			<div class="video-container">
				<iframe width="560" height="315" src="https://www.youtube.com/embed/8Kkx6ys2D_Y" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
			</div>
		</div>

	</div>

	<div class="bp-hello-footer">
		<div class="bp-hello-social-cta">
			<p>
				<?php
				printf(
					__( '<span>Native mobile apps by </span><a href="%s">BuddyBoss</a><span>.</span>', 'buddyboss' ),
					esc_url( 'https://buddyboss.com/app' )
				);
				?>
			</p>
		</div>

		<div class="bp-hello-social-links">
			<ul class="bp-hello-social">
				<li>
					<?php
					printf(
						'<a class="youtube" title="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow BuddyBoss on YouTube', 'buddyboss' ),
						esc_url( 'https://www.youtube.com/c/BuddybossWP' ),
						esc_html( 'Follow BuddyBoss on YouTube', 'buddyboss' )
					);
					?>
				</li>

				<li>
					<?php
					printf(
						'<a class="twitter" title="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow BuddyBoss on Twitter', 'buddyboss' ),
						esc_url( 'https://twitter.com/BuddyBossWP' ),
						esc_html( 'Follow BuddyBoss on Twitter', 'buddyboss' )
					);
					?>
				</li>

				<li>
					<?php
					printf(
						'<a class="facebook" title="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow BuddyBoss on Facebook', 'buddyboss' ),
						esc_url( 'https://facebook.com/BuddyBossWP/' ),
						esc_html( 'Follow BuddyBoss on Facebook', 'buddyboss' )
					);
					?>
				</li>
			</ul>
		</div>
	</div>
</div>
