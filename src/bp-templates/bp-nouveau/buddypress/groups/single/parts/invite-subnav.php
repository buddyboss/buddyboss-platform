<?php
/**
 * BuddyPress Single Groups Invites Navigation
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/parts/invite-subnav.php.
 *
 * @since   BuddyBoss 1.2.3
 * @version 1.2.3
 */
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group administration menu', 'buddyboss' ); ?>">

	<?php if ( bp_nouveau_has_nav( array( 'object' => 'group_invite' ) ) ) : ?>

		<ul class="subnav">

			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();
			?>

				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
						<?php bp_nouveau_nav_link_text(); ?>

						<?php if ( bp_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bp_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>

			<?php endwhile; ?>

		</ul>

	<?php endif; ?>

</nav><!-- #isubnav -->
