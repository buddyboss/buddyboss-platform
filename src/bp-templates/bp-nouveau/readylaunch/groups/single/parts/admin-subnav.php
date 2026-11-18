<?php
/**
 * ReadyLaunch - Single Groups Admin Navigation template.
 *
 * This template displays the navigation menu for group administration pages
 * including settings, members management, and other admin functions.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// The forum tab's nav slug follows the Forum Permalink setting, so its id is not stable to style against.
$bb_rl_forum_nav_slug = bp_is_active( 'forums' ) ? urlencode( get_option( '_bbp_forum_slug', 'forum' ) ) : '';
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?> bb-rl-admin-subnav bb-rl-group-admin-subnav" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group administration menu', 'buddyboss' ); ?>">
	<?php if ( bp_nouveau_has_nav( array( 'object' => 'group_manage' ) ) ) : ?>
		<ul class="subnav">
			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();

				$bb_rl_nav_item    = bp_nouveau()->current_nav_item;
				$bb_rl_item_class  = 'bb-rl-admin-subnav-item';
				$bb_rl_item_class .= ( '' !== $bb_rl_forum_nav_slug && ! empty( $bb_rl_nav_item->slug ) && $bb_rl_forum_nav_slug === $bb_rl_nav_item->slug ) ? ' bb-rl-admin-subnav-item--forum' : '';
				?>
				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?> <?php echo esc_attr( $bb_rl_item_class ); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
						<?php
						bp_nouveau_nav_link_text();

						if ( bp_nouveau_nav_has_count() ) :
							?>
							<span class="count"><?php bp_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
	<?php endif; ?>
</nav><!-- #isubnav -->
