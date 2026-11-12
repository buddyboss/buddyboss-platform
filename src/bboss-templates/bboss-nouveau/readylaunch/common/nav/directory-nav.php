<?php
/**
 * ReadyLaunch - Directory navigation template.
 *
 * This template handles the navigation menu for directory pages
 * including tabs, filters, and sorting options.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<nav class="<?php bboss_nouveau_directory_type_navs_class(); ?>" role="navigation" aria-label="<?php esc_attr_e( 'Directory menu', 'buddyboss' ); ?>">

	<?php if ( bboss_nouveau_has_nav( array( 'object' => 'directory' ) ) ) : ?>

		<ul class="bb-rl-component-navigation <?php bboss_nouveau_directory_list_class(); ?>">

			<?php
			while ( bboss_nouveau_nav_items() ) :
				bboss_nouveau_nav_item();
				?>

				<li id="<?php bboss_nouveau_nav_id(); ?>" class="<?php bboss_nouveau_nav_classes(); ?>" <?php bboss_nouveau_nav_scope(); ?> data-bp-object="<?php bboss_nouveau_directory_nav_object(); ?>">
					<a href="<?php bboss_nouveau_nav_link(); ?>">
						<?php bboss_nouveau_nav_link_text(); ?>

						<?php if ( bboss_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bboss_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>

			<?php endwhile; ?>

		</ul><!-- .bb-rl-component-navigation -->

	<?php endif; ?>

</nav><!-- .bp-navs -->
