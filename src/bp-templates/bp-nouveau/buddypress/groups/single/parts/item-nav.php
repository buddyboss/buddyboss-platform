<?php
/**
 * BuddyPress Single Groups item Navigation
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/parts/item-nav.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<nav class="<?php bp_nouveau_single_item_nav_classes(); ?>" id="object-nav" role="navigation" aria-label="<?php esc_attr_e( 'Group menu', 'buddyboss-platform' ); ?>">
	<?php if ( bp_nouveau_has_nav( array( 'object' => 'groups' ) ) ) : ?>
		<ul>
			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();

				$hidden_tabs = bp_nouveau_get_appearance_settings( 'group_nav_hide' );
				$bp_nouveau  = bp_nouveau();
				$nav_item    = $bp_nouveau->current_nav_item;

				if ( ! is_admin() && is_array( $hidden_tabs ) && ! empty( $hidden_tabs ) && in_array( $nav_item->slug, $hidden_tabs, true ) ) {
					continue;
				}
				?>
				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
						<div class="bb-single-nav-item-point"><?php bp_nouveau_nav_link_text(); ?></div>
					</a>
				</li>
				<?php
			endwhile;

			bp_nouveau_group_hook( '', 'options_nav' );
			?>
		</ul>
	<?php endif; ?>
</nav>
