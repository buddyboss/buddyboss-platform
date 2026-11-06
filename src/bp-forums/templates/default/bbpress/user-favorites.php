<?php

/**
 * User Favorites
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

	<?php do_action( 'bbp_template_before_user_favorites' ); ?>

	<div id="bbp-user-favorites" class="bbp-user-favorites">
		<h2 class="screen-heading favorite-topics-screen"><?php esc_html_e( 'My Favorite Discussions', 'buddyboss' ); ?></h2>
		<div class="bbp-user-section">

			<?php if ( bboss_bbp_get_user_favorites() ) : ?>

				<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

				<?php bboss_bbp_get_template_part( 'loop', 'topics' ); ?>

				<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php else : ?>

				<aside class="bp-feedback bp-messages info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php bboss_bbp_is_user_home() ? esc_html_e( 'You currently have no favorite discussions.', 'buddyboss' ) : esc_html_e( 'This user has no liked discussions.', 'buddyboss' ); ?></p>
				</aside>

			<?php endif; ?>

		</div>
	</div><!-- #bbp-user-favorites -->

	<?php do_action( 'bbp_template_after_user_favorites' ); ?>
