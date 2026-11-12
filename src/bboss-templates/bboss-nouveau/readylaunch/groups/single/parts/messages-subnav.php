<?php
/**
 * ReadyLaunch - Single Groups Messages Navigation template.
 *
 * This template displays the navigation menu for group messages pages
 * including public and private messaging options.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bp_nouveau_get_nav_link_text', 'BBoss_Group_Readylaunch::bb_rl_modify_nav_link_text', 10, 3 );
?>

<nav class="<?php bboss_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group Messages menu', 'buddyboss' ); ?>">
	<?php if ( bboss_nouveau_has_nav( array( 'object' => 'group_messages' ) ) ) : ?>
		<ul class="subnav">
			<?php
			while ( bboss_nouveau_nav_items() ) :
				bboss_nouveau_nav_item();
				?>
				<li id="<?php bboss_nouveau_nav_id(); ?>" class="<?php bboss_nouveau_nav_classes(); ?>">
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
<?php
remove_filter( 'bp_nouveau_get_nav_link_text', 'BBoss_Group_Readylaunch::bb_rl_modify_nav_link_text', 10, 3 );
?>
