<?php
/**
 * AppBoss Admin Screen.
 *
 * This file contains information about AppBoss. The BuddyBoss application to create a native mobile app with your WordPress site.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div id="bp-hello-backdrop" style="display: none;"></div>

<div id="bp-hello-container" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
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

		<h2><?php esc_html_e( 'Feature', 'buddyboss' ); ?></h2>
		<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'buddyboss' ); ?></p>

		<h2><?php esc_html_e( "Feature", 'buddyboss' ); ?></h2>
		<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'buddyboss' ); ?></p>

		<p><?php esc_html_e( 'Thank you for using BuddyBoss!', 'buddyboss' ); ?></p>

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
