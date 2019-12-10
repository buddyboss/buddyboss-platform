<?php
/**
 * AppBoss Admin Screen.
 *
 * This file contains information about AppBoss.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div id="bp-hello-backdrop" style="display: none;"></div>

<div id="bp-hello-container" class="bp-hello-appboss" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'Say hello to AppBoss', 'buddyboss' ); ?></h1>
		</div>
	</div>

	<div class="bp-hello-content">

		<div class="video-wrapper">
			<div class="video-container">
				<iframe width="560" height="315" src="https://www.youtube.com/embed/SZVB0_hmuoE" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
			</div>
		</div>

	</div>

	<div class="bp-hello-footer">
		<div class="bp-hello-social-cta">
			<p>
				<?php
				printf(
					__( '<span>Native mobile apps by </span><a href="%s">AppBoss</a><span>.</span>', 'buddyboss' ),
					esc_url( 'https://appboss.com/' )
				);
				?>
			</p>
		</div>

		<div class="bp-hello-social-links">
			<ul class="bp-hello-social">
				<li>
					<?php
					printf(
						'<a class="youtube bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow AppBoss on YouTube', 'buddyboss' ),
						esc_url( 'https://www.youtube.com/channel/UCcvCtasowEksYbGwcP1eJOw' ),
						esc_html( 'Follow AppBoss on YouTube', 'buddyboss' )
					);
					?>
				</li>

				<li>
					<?php
					printf(
						'<a class="twitter bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow AppBoss on Twitter', 'buddyboss' ),
						esc_url( 'https://twitter.com/AppBossWP' ),
						esc_html( 'Follow AppBoss on Twitter', 'buddyboss' )
					);
					?>
				</li>

				<li>
					<?php
					printf(
						'<a class="facebook bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow AppBoss on Facebook', 'buddyboss' ),
						esc_url( 'https://facebook.com/AppBossWP' ),
						esc_html( 'Follow AppBoss on Facebook', 'buddyboss' )
					);
					?>
				</li>
			</ul>
		</div>
	</div>
</div>
