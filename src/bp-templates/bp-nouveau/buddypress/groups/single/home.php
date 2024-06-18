<?php
/**
 * BuddyBoss - Groups Home
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/home.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

if ( bp_is_group_subgroups() ) {
	ob_start();
	bp_nouveau_group_template_part();
	$template_content = ob_get_contents();
	ob_end_clean();
}

if ( bp_has_groups() ) {
	while ( bp_groups() ) :
		bp_the_group();
		?>

		<?php bp_nouveau_group_hook( 'before', 'home_content' ); ?>

		<div id="item-header" role="complementary" data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups" class="groups-header single-headers">
			<?php bp_nouveau_group_header_template_part(); ?>
		</div><!-- #item-header -->

		<div class="bp-wrap">
			<?php
			if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
				bp_get_template_part( 'groups/single/parts/item-nav' );
			}
			?>

			<div id="item-body" class="item-body">
				<?php
				if ( bp_is_group_subgroups() ) {
					echo $template_content; // phpcs:ignore
				} else {
					bp_nouveau_group_template_part();
				}
				?>
			</div><!-- #item-body -->
		</div><!-- // .bp-wrap -->

		<?php
		bp_nouveau_group_hook( 'after', 'home_content' );

	endwhile;
}
