<?php
/**
 * ReadyLaunch - Single Groups Members Navigation template.
 *
 * This template displays the navigation menu for group members pages
 * including all members and group leaders sections.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group administration menu', 'buddyboss' ); ?>">
	<?php if ( bp_nouveau_has_nav( array( 'object' => 'group_members' ) ) ) : ?>
		<ul class="subnav">
			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();
				?>
				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
						<?php
						bp_nouveau_nav_link_text();

						if ( bp_nouveau_nav_has_count() ) :
							?>
							<span class="count"><?php bp_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
	<?php endif; ?>
</nav><!-- #isubnav -->
