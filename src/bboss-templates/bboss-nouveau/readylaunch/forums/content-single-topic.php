<?php
/**
 * Single Topic Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-container-inner">
	<?php if ( bboss_is_group() ) { ?>
		<a href="<?php echo esc_url( bboss_bbp_get_forum_permalink( bboss_bbp_get_topic_forum_id() ) ); ?>" class="bb-rl-group-forum-header-back">
			<i class="bb-icons-rl-arrow-left"></i> <?php esc_html_e( 'Back to Discussions', 'buddyboss-platform' ); ?>
		</a>
	<?php } ?>
	<div id="bbpress-forums" class="bb-rl-forums-topic-page">
		<div class="bb-rl-forums-container-inner">
			<?php do_action( 'bbp_template_before_single_topic' ); ?>

			<?php if ( post_password_required() ) : ?>

				<?php bboss_bbp_get_template_part( 'form', 'protected' ); ?>

			<?php else : ?>

				<?php if ( bboss_bbp_show_lead_topic() ) : ?>

					<?php bboss_bbp_get_template_part( 'content', 'single-topic-lead' ); ?>

				<?php endif; ?>

				<div class="bb-rl-forums-content-wrapper">
					<?php if ( bboss_bbp_has_replies() ) : ?>

						<?php bboss_bbp_get_template_part( 'loop', 'replies' ); ?>

					<?php else: ?>

						<?php bboss_bbp_get_template_part( 'loop', 'replies' ); ?>
						<?php bboss_bbp_get_template_part( 'feedback', 'no-replies' ); ?>

					<?php endif; ?>

					<?php bboss_bbp_get_template_part( 'form', 'reply' ); ?>
				</div>
			<?php endif; ?>

			<?php do_action( 'bbp_template_after_single_topic' ); ?>
		</div><!-- .bb-rl-forums-container-inner -->
	</div><!-- .bb-forums-topic-page -->
	<?php if ( bboss_bbp_has_replies() ) : ?>
		<?php bboss_bbp_get_template_part( 'pagination', 'replies' ); ?>
	<?php endif; ?>
</div><!-- .bb-rl-container-inner -->
