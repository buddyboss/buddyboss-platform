<?php
/**
 * Search Loop - Single Reply
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div id="post-<?php bbp_reply_id(); ?>"
	<?php
	bbp_reply_class(
		bbp_get_reply_id(),
		array(
			'bb-rl-forum-reply-list-item',
			'scrubberpost',
		)
	);
	?>
	data-date="<?php echo esc_attr( get_post_time( 'F Y', false, bbp_get_reply_id(), true ) ); ?>">

	<div class="flex items-center bb-rl-reply-header">

		<div class="bb-rl-reply-author-avatar item-avatar">
			<?php
			$args = array( 'type' => 'avatar' );
			echo bbp_get_reply_author_link( $args );
			?>
		</div><!-- .bbp-reply-author -->

		<div class="bb-rl-reply-author-info">
			<h3>
				<?php
				$args = array( 'type' => 'name' );
				echo bbp_get_reply_author_link( $args );
				?>
			</h3>
			<span class="bb-rl-timestamp"><?php bbp_reply_post_date(); ?></span>

			<?php if ( bbp_is_single_user_replies() ) : ?>

				<span class="bbp-header">
				<?php esc_html_e( 'in reply to: ', 'buddyboss' ); ?>
					<a class="bbp-topic-permalink"
						href="<?php bbp_topic_permalink( bbp_get_reply_topic_id() ); ?>"><?php bbp_topic_title( bbp_get_reply_topic_id() ); ?></a>
				</span>

			<?php endif; ?>

		</div>

	</div>

	<div class="bbp-after-author-hook">
		<?php do_action( 'bbp_theme_after_reply_author_details' ); ?>
	</div>

	<div class="bb-rl-reply-content">

		<?php do_action( 'bbp_theme_before_reply_content' ); ?>

		<?php bbp_reply_content(); ?>

		<?php do_action( 'bbp_theme_after_reply_content' ); ?>

	</div><!-- .bbp-reply-content -->

</div><!-- .reply -->

