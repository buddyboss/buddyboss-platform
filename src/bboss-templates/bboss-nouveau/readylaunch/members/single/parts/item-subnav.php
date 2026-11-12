<?php
/**
 * ReadyLaunch - Member Item Sub Navigation template.
 *
 * This template handles the secondary navigation for single member pages.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php
$bp_nouveau = bboss_nouveau();
$has_nav    = bboss_nouveau_has_nav( array( 'type' => 'secondary' ) );
$nav_count  = count( $bp_nouveau->sorted_nav );

if ( ! $has_nav || $nav_count <= 1 ) {
	unset( $bp_nouveau->sorted_nav, $bp_nouveau->displayed_nav, $bp_nouveau->object_nav );
	return;
}
?>
<nav class="
	<?php
	bboss_nouveau_single_item_subnav_classes();
	echo esc_attr( bboss_is_user_settings() ? ' bb-rl-profile-edit-subnav' : ' bb-rl-profile-subnav' );
	?>
	" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Sub Menu', 'buddyboss' ); ?>">
	<ul class="subnav">

		<?php
		while ( bboss_nouveau_nav_items() ) :
			bboss_nouveau_nav_item();

			$nav_item = bboss_nouveau()->current_nav_item;
			if ( 'archived' === $nav_item->slug ) {
				continue;
			}

			// Change the nav_item -> name "New Message" to "New"
			if ( 'New Message' === $nav_item->name ) {
				$nav_item->name = 'New';
			}
			?>

			<li id="<?php bboss_nouveau_nav_id(); ?>" class="<?php bboss_nouveau_nav_classes(); ?> bb-rl-profile-subnav-item" <?php bboss_nouveau_nav_scope(); ?>>
				<a href="<?php bboss_nouveau_nav_link(); ?>" id="<?php bboss_nouveau_nav_link_id(); ?>" class="<?php bboss_nouveau_nav_link_class(); ?>">
					<?php
					bboss_nouveau_nav_link_text();

					if ( bboss_nouveau_nav_has_count() ) :
						?>
						<span class="count bb-rl-heading-count"><?php bboss_nouveau_nav_count(); ?></span>
					<?php endif; ?>
				</a>

				<?php do_action( 'bb_nouveau_after_nav_link' . '_' . bboss_nouveau_get_nav_link_id() ); ?>
			</li>

		<?php endwhile; ?>

	</ul>
</nav><!-- .item-list-tabs#subnav -->
