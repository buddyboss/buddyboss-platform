<?php

/**
 * User Subscriptions
 *
 * @package BuddyBoss\Theme
 */

?>

	<?php do_action( 'bbp_template_before_user_subscriptions' ); ?>

	<?php if ( bbp_is_subscriptions_active() ) : ?>

		<?php if ( bbp_is_user_home() || current_user_can( 'edit_users' ) ) : ?>

			<div id="bbp-user-subscriptions" class="bbp-user-subscriptions">
				<h2 class="screen-heading subscribed-forums-screen"><?php _e( 'Subscribed Forums', 'buddyboss' ); ?></h2>
				<div class="bbp-user-section">

					<?php if ( bbp_get_user_forum_subscriptions() ) : ?>

						<?php bbp_get_template_part( 'loop', 'forums' ); ?>

					<?php else : ?>

						<aside class="bp-feedback bp-messages info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php bbp_is_user_home() ? _e( 'You are not currently subscribed to any forums.', 'buddyboss' ) : _e( 'This user is not currently subscribed to any forums.', 'buddyboss' ); ?></p>
						</aside>

						<br />

					<?php endif; ?>

				</div>

				<h2 class="screen-heading subscribed-topics-screen"><?php _e( 'Subscribed Discussions', 'buddyboss' ); ?></h2>
				<div class="bbp-user-section">

					<?php if ( bbp_get_user_topic_subscriptions() ) : ?>

						<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

						<?php bbp_get_template_part( 'loop', 'topics' ); ?>

						<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

					<?php else : ?>

						<aside class="bp-feedback bp-messages info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php bbp_is_user_home() ? _e( 'You are not currently subscribed to any discussions.', 'buddyboss' ) : _e( 'This user is not currently subscribed to any discussions.', 'buddyboss' ); ?></p>
						</aside>

					<?php endif; ?>

				</div>
			</div><!-- #bbp-user-subscriptions -->

		<?php endif; ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_user_subscriptions' ); ?>
