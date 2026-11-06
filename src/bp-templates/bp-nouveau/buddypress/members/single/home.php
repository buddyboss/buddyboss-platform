<?php
/**
 * The template for members home
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/home.php.
 *
 * @since   BuddyPress 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_member_hook( 'before', 'home_content' );
?>

<div id="item-header" role="complementary" data-bp-item-id="<?php echo esc_attr( bboss_displayed_user_id() ); ?>" data-bp-item-component="members" class="users-header single-headers">
	<?php bboss_nouveau_member_header_template_part(); ?>
</div><!-- #item-header -->

<div class="bp-wrap">
	<?php
	if ( ! bboss_nouveau_is_object_nav_in_sidebar() ) {
		bboss_get_template_part( 'members/single/parts/item-nav' );
	}
	?>

	<div id="item-body" class="item-body">
		<?php bboss_nouveau_member_template_part(); ?>
	</div><!-- #item-body -->
</div><!-- // .bp-wrap -->

<?php bboss_nouveau_member_hook( 'after', 'home_content' ); ?>
