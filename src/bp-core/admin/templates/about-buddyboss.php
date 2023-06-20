<?php
/**
 * BuddyBoss Admin Screen.
 *
 * This file contains information about BuddyBoss.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div id="bp-hello-backdrop" style="display: none;"></div>

<div id="bp-hello-container" class="bp-hello-buddyboss bb-onload-modal" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'BuddyBoss Platform', 'buddyboss' ); ?></h1>
		</div>
	</div>

	<div class="bp-hello-content">
		<h2><?php esc_html_e( 'Welcome to BuddyBoss Platform', 'buddyboss' ); ?></h2>
		<p><?php _e( 'BuddyBoss Platform is a fork of <a href="https://buddypress.org/" target="_blank">BuddyPress</a> and <a href="https://bbpress.org/" target="_blank">bbPress</a>, and is designed to be backwards compatible with BuddyPress data and most BuddyPress plugins. If you have been using BuddyPress or bbPress in the past, you should remove them as this is a full replacement. We have improved almost every aspect of BuddyPress and added many new features. As you explore you will find improvements all over the place. Have fun!', 'buddyboss' ); ?></p>

		<?php
		$theme = wp_get_theme(); // gets the current theme
		if ( 'BuddyBoss Theme' == $theme->name || 'BuddyBoss Theme' == $theme->parent_theme ) : ?>

			<h2><?php esc_html_e( 'Get support for BuddyBoss', 'buddyboss' ); ?></h2>
			<p><?php _e( 'We see you are running BuddyBoss Theme, congratulations! If you have an active theme license, you can access our support ticketing system to get 24/7 support for BuddyBoss Theme and Platform. Sign into <a href="https://www.buddyboss.com/" target="_blank">BuddyBoss.com</a> using the email and password created during your purchase, and from there you can access support tickets, downloads and license keys.', 'buddyboss' ); ?></p>
		
		<?php else : ?>

			<h2><?php esc_html_e( 'Consider the BuddyBoss Theme', 'buddyboss' ); ?></h2>
			<p><?php _e( 'BuddyBoss Platform is theme independent, meaning any generic WordPress theme can use it, and then a custom theme just makes everything extra nice. We have created our premium <a href="https://www.buddyboss.com/pricing/" target="_blank">BuddyBoss Theme</a> that styles everything to look absolutely gorgeous. Because we now control the core plugin framework, we are able to do much more advanced layouts in our theme than before, and we think you will love the result. If you were previously using <a href="https://www.buddyboss.com/product/boss-theme/" target="_blank">Boss</a> theme or <a href="https://www.buddyboss.com/product/onesocial-theme/" target="_blank">OneSocial</a> theme you should switch to our new theme, as those legacy themes won\'t support all of our new layouts.', 'buddyboss' ); ?></p>

		<?php endif; ?>

		<h2><?php esc_html_e( 'Below are some resources to help you get started:', 'buddyboss' ); ?></h2>
		<ul class="bp-hello-list">
			<li>
				<?php
				printf(
					__( '<a href="%s" target="_blank">Documentation</a>', 'buddyboss' ),
					esc_url( 'https://www.buddyboss.com/resources/docs/' )
				);
				?>
			</li>
			<li>
				<?php
				printf(
					__( '<a href="%s" target="_blank">Roadmap</a>', 'buddyboss' ),
					esc_url( 'https://www.buddyboss.com/roadmap/' )
				);
				?>
			</li>
			<li>
				<?php
				printf(
					__( '<a href="%s" target="_blank">Release Notes</a>', 'buddyboss' ),
					esc_url( 'https://www.buddyboss.com/resources/buddyboss-platform-releases/' )
				);
				?>
			</li>
			<li>
				<?php
				printf(
					__( '<a href="%s" target="_blank">Code Reference</a>', 'buddyboss' ),
					esc_url( 'https://www.buddyboss.com/resources/reference/' )
				);
				?>
			</li>
			<li>
				<?php
				printf(
					__( '<a href="%s" target="_blank">REST API</a>', 'buddyboss' ),
					esc_url( 'https://www.buddyboss.com/resources/api/' )
				);
				?>
			</li>
			<li>
				<?php
				printf(
					/* translators: 1: URL, 2: Text. */
					'<a href="%1$s" target="_blank">%2$s</a>',
					esc_url( 'https://github.com/buddyboss/buddyboss-platform' ),
					esc_html__( 'GitHub', 'buddyboss' )
				);
				?>
			</li>
		</ul>

		<br /><br />
	</div>

	<div class="bp-hello-footer">
		<div class="bp-hello-social-cta">
			<p>
				<?php
				printf(
					__( 'Built by <a href="%s">BuddyBoss</a>.', 'buddyboss' ),
					esc_url( 'https://www.buddyboss.com/' )
				);
				?>
			</p>
		</div>

		<div class="bp-hello-social-links">
			<ul class="bp-hello-social">
				<li>
					<?php
					printf(
						/* translators: 1: Attr Text, 2: Link, 2: Text. */
						'<a class="youtube" title="%1$s" href="%2$s"><i class="bb-icon-f bb-icon-brand-youtube"></i><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow BuddyBoss on YouTube', 'buddyboss' ),
						esc_url( 'https://www.youtube.com/c/BuddybossWP' ),
						esc_html__( 'Follow BuddyBoss on YouTube', 'buddyboss' )
					);
					?>
				</li>

				<li>
					<?php
					printf(
						/* translators: 1: Attr Text, 2: Link, 2: Text. */
						'<a class="twitter" title="%1$s" href="%2$s"><i class="bb-icon-f bb-icon-brand-twitter"></i><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow BuddyBoss on Twitter', 'buddyboss' ),
						esc_url( 'https://twitter.com/BuddyBossWP' ),
						esc_html__( 'Follow BuddyBoss on Twitter', 'buddyboss' )
					);
					?>
				</li>

				<li>
					<?php
					printf(
						/* translators: 1: Attr Text, 2: Link, 2: Text. */
						'<a class="facebook" title="%1$s" href="%2$s"><i class="bb-icon-f bb-icon-brand-facebook"></i><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow BuddyBoss on Facebook', 'buddyboss' ),
						esc_url( 'https://facebook.com/BuddyBossWP/' ),
						esc_html__( 'Follow BuddyBoss on Facebook', 'buddyboss' )
					);
					?>
				</li>
			</ul>
		</div>
	</div>
</div>
