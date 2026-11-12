<?php
/**
 * Topics Loop Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php do_action( 'bbp_template_before_topics_loop' ); ?>

<div id="bp-rl-forum-<?php bboss_bbp_forum_id(); ?>" class="bb-rl-topics-list">

	<ul class="bb-rl-topics-list-body">

		<?php
		while ( bboss_bbp_topics() ) :
			bboss_bbp_the_topic();
			?>

			<?php bboss_bbp_get_template_part( 'loop', 'single-topic' ); ?>

		<?php endwhile; ?>

	</ul>

	<div class="bb-rl-topics-list-footer">

		<div class="tr">
			<p>
				<span class="td colspan<?php echo ( bboss_bbp_is_user_home() && ( bboss_bbp_is_favorites() || bboss_bbp_is_subscriptions() ) ) ? '5' : '4'; ?>">&nbsp;</span>
			</p>
		</div><!-- .tr -->

	</div>

</div><!-- #bbp-forum-<?php bboss_bbp_forum_id(); ?> -->

<?php do_action( 'bbp_template_after_topics_loop' ); ?>
