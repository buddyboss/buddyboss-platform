<?php
/**
 * The template for BP Nouveau Component's directory nav template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/nav/directory-nav.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<nav class="<?php bboss_nouveau_directory_type_navs_class(); ?>" role="navigation" aria-label="<?php esc_attr_e( 'Directory menu', 'buddyboss' ); ?>">

	<?php if ( bboss_nouveau_has_nav( array( 'object' => 'directory' ) ) ) : ?>

		<ul class="component-navigation <?php bboss_nouveau_directory_list_class(); ?>">

			<?php
			while ( bboss_nouveau_nav_items() ) :
				bboss_nouveau_nav_item();
				?>

				<li id="<?php bboss_nouveau_nav_id(); ?>" class="<?php bboss_nouveau_nav_classes(); ?>" <?php bboss_nouveau_nav_scope(); ?> data-bp-object="<?php bboss_nouveau_directory_nav_object(); ?>">
					<a href="<?php bboss_nouveau_nav_link(); ?>">
						<?php bboss_nouveau_nav_link_text(); ?>
					</a>
				</li>

			<?php endwhile; ?>

		</ul><!-- .component-navigation -->

	<?php endif; ?>

</nav><!-- .bp-navs -->
