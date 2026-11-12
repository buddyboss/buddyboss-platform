<?php
/**
 * User Subscriptions Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

do_action( 'bbp_template_before_user_subscriptions' );

if ( bboss_bbp_is_user_home() || current_user_can( 'edit_users' ) ) : ?>

	<div id="bbp-user-subscriptions" class="bbp-user-subscriptions">
		<h2 class="screen-heading subscribed-forums-screen"><?php esc_html_e( 'Subscribed Forums', 'buddyboss' ); ?></h2>
		<div class="bbp-user-section">

			<?php if ( bboss_is_enabled_subscription( 'forum' ) && bboss_bbp_get_user_forum_subscriptions() ) : ?>

				<?php bboss_bbp_get_template_part( 'loop', 'forums' ); ?>

			<?php else : ?>

				<aside class="bp-feedback bp-messages info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php bboss_bbp_is_user_home() ? esc_html_e( 'You are not currently subscribed to any forums.', 'buddyboss' ) : esc_html_e( 'This user is not currently subscribed to any forums.', 'buddyboss' ); ?></p>
				</aside>

				<br />

			<?php endif; ?>

		</div>

		<h2 class="screen-heading subscribed-topics-screen"><?php esc_html_e( 'Subscribed Discussions', 'buddyboss' ); ?></h2>
		<div class="bbp-user-section">

			<?php if ( bboss_is_enabled_subscription( 'topic' ) && bboss_bbp_get_user_topic_subscriptions() ) : ?>

				<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

				<?php bboss_bbp_get_template_part( 'loop', 'topics' ); ?>

				<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php else : ?>

				<aside class="bp-feedback bp-messages info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php bboss_bbp_is_user_home() ? esc_html_e( 'You are not currently subscribed to any discussions.', 'buddyboss' ) : esc_html_e( 'This user is not currently subscribed to any discussions.', 'buddyboss' ); ?></p>
				</aside>

			<?php endif; ?>

		</div>
	</div><!-- #bbp-user-subscriptions -->

<?php endif;
do_action( 'bbp_template_after_user_subscriptions' ); ?>
