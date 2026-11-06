<?php

/**
 * User Topics Created
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

	<?php do_action( 'bbp_template_before_user_topics_created' ); ?>

	<div id="bbp-user-topics-started" class="bbp-user-topics-started">
		<h2 class="screen-heading topics-started-screen"><?php esc_html_e( 'Forum Discussions Started', 'buddyboss' ); ?></h2>
		<div class="bbp-user-section">

			<?php if ( bboss_bbp_get_user_topics_started() ) : ?>

				<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

				<?php bboss_bbp_get_template_part( 'loop', 'topics' ); ?>

				<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php else : ?>

				<aside class="bp-feedback bp-messages info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php bboss_bbp_is_user_home() ? esc_html_e( 'You have not created any discussions.', 'buddyboss' ) : esc_html_e( 'This user has not created any discussions.', 'buddyboss' ); ?></p>
				</aside>

			<?php endif; ?>

		</div>
	</div><!-- #bbp-user-topics-started -->

	<?php do_action( 'bbp_template_after_user_topics_created' ); ?>
