<?php
/**
 * ReadyLaunch - Single Groups Admin Navigation template.
 *
 * This template displays the navigation menu for group administration pages
 * including settings, members management, and other admin functions.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?> bb-rl-admin-subnav bb-rl-group-admin-subnav" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group administration menu', 'buddyboss' ); ?>">
	<?php if ( bp_nouveau_has_nav( array( 'object' => 'group_manage' ) ) ) : ?>
		<ul class="subnav">
			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();
				?>
				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?> bb-rl-admin-subnav-item">
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
