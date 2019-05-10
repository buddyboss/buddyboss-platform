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

<div id="bp-hello-container" class="bp-hello-buddyboss" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Close pop-up', 'buddyboss' ); ?></span>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'New in BuddyBoss', 'buddyboss' ); ?></h1>
		</div>
	</div>

	<div class="bp-hello-content">
		
		<!--
		<h2><?php echo esc_html( _n( 'Maintenance Release', 'Maintenance Releases', 1, 'buddyboss' ) ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: 1: BuddyPress version number, 2: plural number of bugs. */
				_n(
					'<strong>Version %1$s</strong> addressed %2$s bug.',
					'<strong>Version %1$s</strong> addressed %2$s bugs.',
					23,
					'buddyboss'
				),
				self::display_version(),
				number_format_i18n( 23 )
			);
			?>
		</p>

		<hr>
		-->

		<h2><?php esc_html_e( 'Welcome to BuddyBoss Platform', 'buddyboss' ); ?></h2>
		<p><?php _e( 'BuddyBoss Platform is a fork of <a href="https://buddypress.org/" target="_blank">BuddyPress</a>, and is designed to be backwards compatible with BuddyPress data and most BuddyPress plugins. If you have been using BuddyPress in the past, you should remove it as this is a full replacement. We have improved almost every aspect of BuddyPress and added many new features. Below are some highlights, but as you explore you will find improvements all over the place. Have fun!', 'buddyboss' ); ?></p>

		<h2><?php esc_html_e( '1. New "BuddyBoss Theme"', 'buddyboss' ); ?></h2>
		<p><?php _e( 'BuddyBoss Platform is meant to be theme independent, meaning any generic WordPress theme can use it, and then a custom theme just makes everything extra nice. We have create an all new premium BuddyBoss Theme that styles everything to look absolutely gorgeous. Because we now control the core plugin framework, we are able to do much more advanced layouts in our theme than before, and we think you will love the result. If you were previously using <a href="https://www.buddyboss.com/product/boss-theme/" target="_blank">Boss</a> theme or <a href="https://www.buddyboss.com/product/onesocial-theme/" target="_blank">OneSocial</a> theme you should switch to our new theme, as those legacy themes won\'t support all of our new layouts.', 'buddyboss' ); ?></p>

		<h2><?php esc_html_e( "2. Forum Discussions", 'buddyboss' ); ?></h2>
		<p><?php _e( 'We have added a native forum discussions component. Your members can now communicate with each other in Q&A style conversations. If you were previously using <a href="https://bbpress.org/" target="_blank">bbPress</a> plugin, you should remove it as those features have been merged into BuddyBoss Platform and greatly improved. Your data will continue to work as normal.', 'buddyboss' ); ?></p>

		<h2><?php esc_html_e( "3. Media Uploading", 'buddyboss' ); ?></h2>
		<p><?php _e( 'We have added a native media management component. Your members can now upload photos, emoji, and animated GIFs into profiles, groups, private messages and forum discussions. They can then organize those photos into albums. If you were previously using <a href="https://www.buddyboss.com/product/buddyboss-media/" target="_blank">BuddyBoss Media</a> plugin with BuddyPress, you should remove it as those features have been merged into BuddyBoss Platform and greatly improved.', 'buddyboss' ); ?></p>

		<h2><?php esc_html_e( "4. Profile and Group Types", 'buddyboss' ); ?></h2>
		<p><?php _e( 'We have added native Profile and Group type management, along with group heirarchies. If you were previously using <a href="https://www.buddyboss.com/product/buddypress-member-types/" target="_blank">BuddyPress Member Types</a> plugin with BuddyPress, you should remove it as those features have been merged into BuddyBoss Platform and greatly improved.', 'buddyboss' ); ?></p>

		<h2><?php esc_html_e( "5. Network Search", 'buddyboss' ); ?></h2>
		<p><?php _e( 'We have added a native search component. Your members can now search content across the entire network, including members, groups, forums, custom post types and more. If you were previously using <a href="https://wordpress.org/plugins/buddypress-global-search/" target="_blank">BuddyPress Global Search</a> plugin with BuddyPress, you should remove it as those features have been merged into BuddyBoss Platform and greatly improved.', 'buddyboss' ); ?></p>

		<h2><?php esc_html_e( "6. Email Invites", 'buddyboss' ); ?></h2>
		<p><?php _e( 'We have added a native email invitation component. Your members can now invite outside users to your community. The recipients will receive an email with a link to join the site.', 'buddyboss' ); ?></p>

		<h2><?php esc_html_e( "7. Private Network", 'buddyboss' ); ?></h2>
		<p><?php _e( 'We have added a native private network option for your community. With a single checkbox you can restrict access to all of your content, but still allow users to register accounts, and allow access to other URLs of your choice.', 'buddyboss' ); ?></p>

		<p><?php _e( '<strong>Thank you for using BuddyBoss!</strong>', 'buddyboss' ); ?></p>

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
						'<a class="youtube bp-tooltip" data-bp-tooltip="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow BuddyBoss on YouTube', 'buddyboss' ),
						esc_url( 'https://www.youtube.com/c/BuddybossWP' ),
						esc_html( 'Follow BuddyBoss on YouTube', 'buddyboss' )
					);
					?>
				</li>

				<li>
					<?php
					printf(
						'<a class="twitter bp-tooltip" data-bp-tooltip="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
						esc_attr( 'Follow BuddyBoss on Twitter', 'buddyboss' ),
						esc_url( 'https://twitter.com/BuddyBossWP' ),
						esc_html( 'Follow BuddyBoss on Twitter', 'buddyboss' )
					);
					?>
				</li>

				<li>
					<?php
					printf(
						'<a class="facebook bp-tooltip" data-bp-tooltip="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
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
