<?php
/**
 * ReadyLaunch - Single Groups item Navigation template.
 *
 * This template displays the main navigation menu for group single pages
 * with various group sections and features.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<nav class="<?php bp_nouveau_single_item_nav_classes(); ?>" id="object-nav" role="navigation" aria-label="<?php esc_attr_e( 'Group menu', 'buddyboss' ); ?>">
	<?php if ( bp_nouveau_has_nav( array( 'object' => 'groups' ) ) ) : ?>
		<ul>
			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();
				?>
				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
						<div class="bb-single-nav-item-point"><?php bp_nouveau_nav_link_text(); ?></div>

						<?php if ( bp_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bp_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>
				<?php
			endwhile;

			bp_nouveau_group_hook( '', 'options_nav' );
			?>
		</ul>
	<?php endif; ?>
</nav>
