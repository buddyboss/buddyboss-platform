<?php
/**
 * ReadyLaunch - Single Groups Admin Navigation template.
 *
 * This template displays the navigation menu for group administration pages
 * including settings, members management, and other admin functions.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// The forum tab's nav slug follows the Forum Permalink setting, so its id is not stable to style against.
$bb_rl_forum_nav_slug = bboss_is_active( 'forums' ) ? urlencode( get_option( '_bbp_forum_slug', 'forum' ) ) : '';
?>

<nav class="<?php bboss_nouveau_single_item_subnav_classes(); ?> bb-rl-admin-subnav bb-rl-group-admin-subnav" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group administration menu', 'buddyboss-platform' ); ?>">
	<?php if ( bboss_nouveau_has_nav( array( 'object' => 'group_manage' ) ) ) : ?>
		<ul class="subnav">
			<?php
			while ( bboss_nouveau_nav_items() ) :
				bboss_nouveau_nav_item();

				$bb_rl_nav_item    = bboss_nouveau()->current_nav_item;
				$bb_rl_item_class  = 'bb-rl-admin-subnav-item';
				$bb_rl_item_class .= ( '' !== $bb_rl_forum_nav_slug && ! empty( $bb_rl_nav_item->slug ) && $bb_rl_forum_nav_slug === $bb_rl_nav_item->slug ) ? ' bb-rl-admin-subnav-item--forum' : '';
				?>
				<li id="<?php bboss_nouveau_nav_id(); ?>" class="<?php bboss_nouveau_nav_classes(); ?> <?php echo esc_attr( $bb_rl_item_class ); ?>">
					<a href="<?php bboss_nouveau_nav_link(); ?>" id="<?php bboss_nouveau_nav_link_id(); ?>">
						<?php
						bboss_nouveau_nav_link_text();

						if ( bboss_nouveau_nav_has_count() ) :
							?>
							<span class="count"><?php bboss_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
	<?php endif; ?>
</nav><!-- #isubnav -->
