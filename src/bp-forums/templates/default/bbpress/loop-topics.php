<?php

/**
 * Topics Loop
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<?php do_action( 'bbp_template_before_topics_loop' ); ?>

<ul id="bbp-forum-<?php bboss_bbp_forum_id(); ?>" class="bbp-topics">

	<li class="bbp-header">

		<ul class="forum-titles">
			<li class="bbp-topic-title"><?php esc_html_e( 'Discussions', 'buddyboss' ); ?></li>
			<li class="bbp-topic-reply-count"><?php bboss_bbp_show_lead_topic() ? esc_html_e( 'Replies', 'buddyboss' ) : esc_html_e( 'Posts', 'buddyboss' ); ?></li>
			<li class="bbp-topic-freshness"><?php esc_html_e( 'Last Post', 'buddyboss' ); ?></li>
		</ul>

	</li>

	<li class="bbp-body">

		<?php
		while ( bboss_bbp_topics() ) :
			bboss_bbp_the_topic();
			?>

			<?php bboss_bbp_get_template_part( 'loop', 'single-topic' ); ?>

		<?php endwhile; ?>

	</li>

	<li class="bbp-footer">

		<div class="tr">
			<p>
				<span class="td colspan<?php echo ( bboss_bbp_is_user_home() && ( bboss_bbp_is_favorites() || bboss_bbp_is_subscriptions() ) ) ? '5' : '4'; ?>">&nbsp;</span>
			</p>
		</div><!-- .tr -->

	</li>

</ul><!-- #bbp-forum-<?php bboss_bbp_forum_id(); ?> -->

<?php do_action( 'bbp_template_after_topics_loop' ); ?>
