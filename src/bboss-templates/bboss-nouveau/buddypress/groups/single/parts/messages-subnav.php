<?php
/**
 * BuddyPress Single Groups Messages Navigation
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/parts/messages-subnav.php.
 *
 * @since   BuddyBoss 1.5.7
 * @version 1.5.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<nav class="<?php bboss_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group Messages menu', 'buddyboss-platform' ); ?>">
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

						if ( bboss_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bboss_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
	<?php endif; ?>
</nav><!-- #isubnav -->
