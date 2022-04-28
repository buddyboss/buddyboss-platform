<?php
/**
 * The template for single members item navigation
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/parts/item-nav.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<nav class="<?php bp_nouveau_single_item_nav_classes(); ?>" id="object-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'buddyboss' ); ?>">

	<?php if ( bp_nouveau_has_nav( array( 'type' => 'primary' ) ) ) : ?>

		<ul>

			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();

				$hidden_tabs = bp_nouveau_get_appearance_settings( 'user_nav_hide' );
				$bp_nouveau  = bp_nouveau();
				$nav_item    = $bp_nouveau->current_nav_item;

				if ( ! is_admin() && is_array( $hidden_tabs ) && ! empty( $hidden_tabs ) && in_array( $nav_item->slug, $hidden_tabs, true ) ) {
					continue;
				}

			?>

				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>" class="<?php bp_nouveau_nav_link_class(); ?>">
						<div class="bb-single-nav-item-point"><?php bp_nouveau_nav_link_text(); ?></div>

						<?php if ( bp_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bp_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>

			<?php endwhile; ?>

			<?php bp_nouveau_member_hook( '', 'options_nav' ); ?>

		</ul>

	<?php endif; ?>

</nav>
