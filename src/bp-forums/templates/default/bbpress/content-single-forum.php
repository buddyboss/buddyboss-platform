<?php
/**
 * Single Forum Content Part
 *
 * @package BuddyBoss\Theme
 */

?>

<div id="bbpress-forums">

	<?php bbp_breadcrumb(); ?>

	<?php bbp_forum_subscription_link(); ?>

	<?php if ( bbp_get_forum_report_link( array( 'id' => bbp_get_forum_id() ) ) ) { ?>
		<div class="bb_more_options action">
			<a href="#" class="bb_more_options_action">
				<i class="bb-icon-menu-dots-h"></i>
			</a>
			<div class="bb_more_options_list">
				<?php bbp_forum_report_link( array( 'id' => bbp_get_forum_id() ) ); ?>
			</div>
		</div><!-- .bb_more_options -->
	<?php } ?>

	<?php do_action( 'bbp_template_before_single_forum' ); ?>

	<?php if ( post_password_required() ) : ?>

		<?php bbp_get_template_part( 'form', 'protected' ); ?>

	<?php else : ?>

		<?php if ( bbp_has_forums() ) : ?>

			<?php bbp_get_template_part( 'loop', 'forums' ); ?>

		<?php endif; ?>

		<?php if ( ! bbp_is_forum_category() && bbp_has_topics() ) : ?>

			<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php bbp_get_template_part( 'loop', 'topics' ); ?>

			<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php bbp_get_template_part( 'form', 'topic' ); ?>

		<?php elseif ( ! bbp_is_forum_category() ) : ?>

			<?php bbp_get_template_part( 'feedback', 'no-topics' ); ?>

			<?php bbp_get_template_part( 'form', 'topic' ); ?>

		<?php endif; ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_single_forum' ); ?>

</div>
