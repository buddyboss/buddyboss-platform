<?php
/**
 * ReadyLaunch - Member Item Navigation template.
 *
 * This template handles the primary navigation for single member pages.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<nav class="<?php bboss_nouveau_single_item_nav_classes(); ?> bb-rl-main-nav" id="object-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'buddyboss-platform' ); ?>">

	<?php if ( bboss_nouveau_has_nav( array( 'type' => 'primary' ) ) ) : ?>
		<ul class="bb-rl-main-nav-list">
			<?php
			while ( bboss_nouveau_nav_items() ) :
				bboss_nouveau_nav_item();

				$hidden_tabs = bboss_nouveau_get_appearance_settings( 'user_nav_hide' );
				$bp_nouveau  = bboss_nouveau();
				$nav_item    = $bp_nouveau->current_nav_item;

				if ( ! is_admin() && is_array( $hidden_tabs ) && ! empty( $hidden_tabs ) && in_array( $nav_item->slug, $hidden_tabs, true ) ) {
					continue;
				}

				$excluded_slugs = array();

				// Check for notifications function.
				if ( function_exists( 'bboss_get_notifications_slug' ) ) {
					$excluded_slugs[] = bboss_get_notifications_slug();
				}

				// Check for messages function.
				if ( function_exists( 'bboss_get_messages_slug' ) ) {
					$excluded_slugs[] = bboss_get_messages_slug();
				}

				// Check for settings function.
				if ( function_exists( 'bboss_get_settings_slug' ) ) {
					$excluded_slugs[] = bboss_get_settings_slug();
				}

				if ( in_array( $nav_item->slug, $excluded_slugs, true ) ) {
					continue;
				}
				?>

				<li id="<?php bboss_nouveau_nav_id(); ?>" class="<?php bboss_nouveau_nav_classes(); ?>">
					<a href="<?php bboss_nouveau_nav_link(); ?>" id="<?php bboss_nouveau_nav_link_id(); ?>" class="<?php bboss_nouveau_nav_link_class(); ?>">
						<div class="bb-single-nav-item-point"><?php bboss_nouveau_nav_link_text(); ?></div>

						<?php if ( bboss_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bboss_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>

				<?php
			endwhile;

			bboss_nouveau_member_hook( '', 'options_nav' );
			?>

		</ul>

	<?php endif; ?>

</nav>
