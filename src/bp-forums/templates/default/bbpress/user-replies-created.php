<?php

/**
 * User Replies Created
 *
 * @package BuddyBoss\Theme
 */

?>

	<?php do_action( 'bbp_template_before_user_replies' ); ?>

	<div id="bbp-user-replies-created" class="bbp-user-replies-created">
		<h2 class="screen-heading forum-replies-screen"><?php _e( 'Forum Replies Created', 'buddyboss' ); ?></h2>
		<div class="bbp-user-section">

			<?php if ( bbp_get_user_replies_created() ) : ?>

				<?php bbp_get_template_part( 'pagination', 'replies' ); ?>

				<?php bbp_get_template_part( 'loop', 'replies' ); ?>

				<?php bbp_get_template_part( 'pagination', 'replies' ); ?>

			<?php else : ?>

				<aside class="bp-feedback bp-messages info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php bbp_is_user_home() ? _e( 'You have not replied to any discussions.', 'buddyboss' ) : _e( 'This user has not replied to any topics.', 'buddyboss' ); ?></p>
				</aside>

			<?php endif; ?>

		</div>
	</div><!-- #bbp-user-replies-created -->

	<?php do_action( 'bbp_template_after_user_replies' ); ?>
