<?php
/**
 * Single Forum Content Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php bboss_bbp_breadcrumb(); ?>

	<?php
	// Remove subscription link if forum assigned to the group.
	if ( ! function_exists( 'bboss_is_forum_group_forum' ) || ! bboss_is_forum_group_forum( bboss_bbp_get_forum_id() ) ) {
		bboss_bbp_forum_subscription_link();
	}
	?>

	<?php if ( bboss_bbp_get_forum_report_link( array( 'id' => bboss_bbp_get_forum_id() ) ) ) { ?>
		<div class="bb_more_options action">
			<a href="#" class="bb_more_options_action" aria-label="<?php esc_attr_e( 'More options', 'buddyboss' ); ?>">
				<i class="bb-icon-f bb-icon-ellipsis-v"></i>
			</a>
			<div class="bb_more_options_list bb_more_dropdown">
				<?php bboss_get_template_part( 'common/more-options-view' ); ?>
				<?php bboss_bbp_forum_report_link( array( 'id' => bboss_bbp_get_forum_id() ) ); ?>
			</div>
			<div class="bb_more_dropdown_overlay"></div>
		</div><!-- .bb_more_options -->
	<?php } ?>

	<?php do_action( 'bbp_template_before_single_forum' ); ?>

	<?php if ( bboss_bbp_is_single_forum() && ! bboss_is_group_single() ) { ?>
		<div class="bbp-forum-content-wrap"><?php echo wp_kses_post( bboss_bbp_get_forum_content_excerpt_view_more() ); ?></div>
	<?php } ?>

	<?php if ( post_password_required() ) : ?>

		<?php bboss_bbp_get_template_part( 'form', 'protected' ); ?>

	<?php else : ?>

		<?php if ( bboss_bbp_has_forums() ) : ?>
			<?php bboss_bbp_get_template_part( 'pagination', 'forums' ); ?>

			<?php bboss_bbp_get_template_part( 'loop', 'forums' ); ?>

			<?php bboss_bbp_get_template_part( 'pagination', 'forums' ); ?>

		<?php endif; ?>

		<?php if ( ! bboss_bbp_is_forum_category() && bboss_bbp_has_topics() ) : ?>

			<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php bboss_bbp_get_template_part( 'loop', 'topics' ); ?>

			<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php bboss_bbp_get_template_part( 'form', 'topic' ); ?>

		<?php elseif ( ! bboss_bbp_is_forum_category() ) : ?>

			<?php bboss_bbp_get_template_part( 'feedback', 'no-topics' ); ?>

			<?php bboss_bbp_get_template_part( 'form', 'topic' ); ?>

		<?php elseif ( bboss_bbp_is_forum_category() ) : ?>

			<?php bboss_bbp_get_template_part( 'feedback', 'forum-category' ); ?>

		<?php endif; ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_single_forum' ); ?>

</div>
