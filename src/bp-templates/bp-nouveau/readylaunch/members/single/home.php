<?php
/**
 * The template for members home
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

bp_nouveau_member_hook( 'before', 'home_content' );
?>

<div id="item-header" role="complementary" data-bp-item-id="<?php echo esc_attr( bp_displayed_user_id() ); ?>" data-bp-item-component="members" class="users-header single-headers">
	<?php
	$template = 'member-header';
	/**
	 * Fires before the display of a member's header.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_before_member_header' );

	// Get the template part for the header.
	bp_nouveau_member_get_template_part( $template );

	/**
	 * Fires after the display of a member's header.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_after_member_header' );

	bp_nouveau_template_notices();

	if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
		bp_get_template_part( 'members/single/parts/item-nav' );
	}
	?>
</div><!-- #item-header -->

<div class="bp-wrap">
	<div id="item-body" class="item-body">
		<?php bp_nouveau_member_template_part(); ?>
	</div><!-- #item-body -->
</div><!-- // .bp-wrap -->

<?php bp_nouveau_member_hook( 'after', 'home_content' ); ?>
