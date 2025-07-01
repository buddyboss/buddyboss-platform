<?php
/**
 * User Profile Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

	<?php do_action( 'bbp_template_before_user_profile' ); ?>

	<div id="bbp-user-profile" class="bbp-user-profile">
		<h2 class="entry-title"><?php esc_html_e( 'Profile', 'buddyboss' ); ?></h2>
		<div class="bbp-user-section">

			<?php if ( bbp_get_displayed_user_field( 'description' ) ) : ?>

				<p class="bbp-user-description"><?php bbp_displayed_user_field( 'description' ); ?></p>

			<?php endif; ?>

			<p class="bbp-user-forum-role">
				<?php
				/* translators: %s is the user's forum role */
				printf( esc_html__( 'Forum Role: %s', 'buddyboss' ), bbp_get_user_display_role() );
				?>
			</p>
			<p class="bbp-user-topic-count">
				<?php
				/* translators: %s is the number of topics started by the user */
				printf( esc_html__( 'Discussions Started: %s', 'buddyboss' ), bbp_get_user_topic_count_raw() );
				?>
			</p>
			<p class="bbp-user-reply-count">
				<?php
				/* translators: %s is the number of replies created by the user */
				printf( esc_html__( 'Replies Created: %s', 'buddyboss' ), bbp_get_user_reply_count_raw() );
				?>
			</p>
		</div>
	</div><!-- #bbp-author-topics-started -->

	<?php do_action( 'bbp_template_after_user_profile' ); ?>
