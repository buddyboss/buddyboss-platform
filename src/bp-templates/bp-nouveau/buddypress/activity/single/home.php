<?php
/**
 * BuddyBoss - Home
 *
 * @version 3.0.0
 */

?>

	<?php bp_nouveau_template_notices(); ?>

	<?php bp_nouveau_before_single_activity_content(); ?>

	<div class="activity" data-bp-single="<?php echo esc_attr( bp_current_action() ); ?>">

		<?php do_action( 'bp_before_single_activity_content' ); ?>

		<ul id="activity-stream" class="activity-list item-list bp-list" data-bp-list="activity">

			<li id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'single-activity-loading' ); ?></li>

		</ul>


		<?php do_action( 'bp_after_single_activity_content' ); ?>

	</div>
