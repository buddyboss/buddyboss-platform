<?php
/**
 * BuddyBoss - Groups Home
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/home.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bboss_is_group_subgroups() ) {
	ob_start();
	bboss_nouveau_group_template_part();
	$template_content = ob_get_contents();
	ob_end_clean();
}

if ( bboss_has_groups() ) {
	while ( bboss_groups() ) :
		bboss_the_group();
		?>

		<?php bboss_nouveau_group_hook( 'before', 'home_content' ); ?>

		<div id="item-header" role="complementary" data-bp-item-id="<?php bboss_group_id(); ?>" data-bp-item-component="groups" class="groups-header single-headers">
			<?php bboss_nouveau_group_header_template_part(); ?>
		</div><!-- #item-header -->

		<div class="bp-wrap">
			<?php
			if ( ! bboss_nouveau_is_object_nav_in_sidebar() ) {
				bboss_get_template_part( 'groups/single/parts/item-nav' );
			}
			?>

			<div id="item-body" class="item-body">
				<?php
				if ( bboss_is_group_subgroups() ) {
					echo $template_content; // phpcs:ignore
				} else {
					bboss_nouveau_group_template_part();
				}
				?>
			</div><!-- #item-body -->
		</div><!-- // .bp-wrap -->

		<?php
		bboss_nouveau_group_hook( 'after', 'home_content' );

	endwhile;
}
