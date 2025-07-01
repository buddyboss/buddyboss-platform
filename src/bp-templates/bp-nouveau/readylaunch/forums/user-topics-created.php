<?php
/**
 * User Topics Created Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

	<?php do_action( 'bbp_template_before_user_topics_created' ); ?>

	<div id="bbp-user-topics-started" class="bbp-user-topics-started">
		<div class="bbp-user-section">

			<?php if ( bbp_get_user_topics_started() ) : ?>

				<?php bbp_get_template_part( 'loop', 'topics' ); ?>

				<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php else : ?>

				<aside class="bp-feedback bp-messages info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php bbp_is_user_home() ? esc_html_e( 'You have not created any discussions.', 'buddyboss' ) : esc_html_e( 'This user has not created any discussions.', 'buddyboss' ); ?></p>
				</aside>

			<?php endif; ?>

		</div>
	</div><!-- #bbp-user-topics-started -->

	<?php do_action( 'bbp_template_after_user_topics_created' ); ?>
