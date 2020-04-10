<?php
/**
 * BuddyPress Single Groups Zoom Navigation
 *
 * @since BuddyBoss 1.2.10
 */
?>

<?php
add_filter( 'bp_nouveau_group_secondary_nav_parent_slug', 'bp_zoom_nouveau_group_secondary_nav_parent_slug' );
add_filter( 'bp_nouveau_get_classes', 'bp_zoom_nouveau_group_secondary_nav_selected_classes', 10, 3 );
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group zoom navigation menu', 'buddyboss' ); ?>">

	<?php if ( bp_nouveau_has_nav( array( 'object' => 'group_zoom' ) ) ) : ?>

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

<?php
remove_filter( 'bp_nouveau_group_nav_get_secondary_parent_slug', 'bp_zoom_nouveau_group_secondary_nav_parent_slug' );
remove_filter( 'bp_nouveau_get_classes', 'bp_zoom_nouveau_group_secondary_nav_selected_classes', 10, 3 );
?>
