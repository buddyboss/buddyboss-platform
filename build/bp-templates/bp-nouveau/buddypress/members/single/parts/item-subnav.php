<?php
/**
 * BuddyPress Single Members item Sub Navigation
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<?php
	$bp_nouveau = bp_nouveau();
	$has_nav    = bp_nouveau_has_nav( array( 'type' => 'secondary' ) );
	$nav_count  = count( $bp_nouveau->sorted_nav );

	if ( ! $has_nav || $nav_count <= 1 ) {
		unset( $bp_nouveau->sorted_nav, $bp_nouveau->displayed_nav, $bp_nouveau->object_nav );
		return;
	}
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Sub Menu', 'buddyboss' ); ?>">
	<ul class="subnav">

		<?php
		while ( bp_nouveau_nav_items() ) :
			bp_nouveau_nav_item();
		?>

			<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>" <?php bp_nouveau_nav_scope(); ?>>
				<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
					<?php bp_nouveau_nav_link_text(); ?>

					<?php if ( bp_nouveau_nav_has_count() ) : ?>
						<span class="count"><?php bp_nouveau_nav_count(); ?></span>
					<?php endif; ?>
				</a>
			</li>

		<?php endwhile; ?>

	</ul>
</nav><!-- .item-list-tabs#subnav -->
