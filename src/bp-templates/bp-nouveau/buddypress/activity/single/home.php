<?php
/**
 * The template for BuddyBoss - Home
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/single/home.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

?>

<div id="bp-nouveau-single-activity-edit-form-wrap" style="display: none;">
	<div id="bp-nouveau-activity-form" class="activity-update-form<?php if ( !bp_is_active( 'media' ) ){ echo ' media-off'; } ?>"></div>
</div>

<?php bp_nouveau_template_notices(); ?>

<?php bp_nouveau_before_single_activity_content(); ?>

<div class="activity" data-bp-single="<?php echo esc_attr( bp_current_action() ); ?>">



	<?php do_action( 'bp_before_single_activity_content' ); ?>

	<ul id="activity-stream" class="activity-list item-list bp-list" data-bp-list="activity">

		<li id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'single-activity-loading' ); ?></li>

	</ul>


	<?php do_action( 'bp_after_single_activity_content' ); ?>

</div>
