<?php

/**
 * Single Topic Content Part
 *
 * @package BuddyBoss\Theme
 */

?>
<div class="bb-rl-container-inner">
	<div id="bbpress-forums" class="bb-rl-forums-topic-page">
		<div class="bb-rl-forums-container-inner">
			<?php do_action( 'bbp_template_before_single_topic' ); ?>

			<?php if ( post_password_required() ) : ?>

				<?php bbp_get_template_part( 'form', 'protected' ); ?>

			<?php else : ?>

				<?php bbp_topic_tag_list(); ?>

				<?php if ( bbp_show_lead_topic() ) : ?>

					<?php bbp_get_template_part( 'content', 'single-topic-lead' ); ?>

				<?php endif; ?>

				<?php if ( bbp_has_replies() ) : ?>

					<?php bbp_get_template_part( 'pagination', 'replies' ); ?>

					<?php bbp_get_template_part( 'loop', 'replies' ); ?>

					<?php bbp_get_template_part( 'pagination', 'replies' ); ?>

				<?php endif; ?>

				<?php bbp_get_template_part( 'form', 'reply' ); ?>

			<?php endif; ?>

			<?php do_action( 'bbp_template_after_single_topic' ); ?>
		</div><!-- .bb-rl-forums-container-inner -->
	</div><!-- .bb-forums-topic-page -->
</div><!-- .bb-rl-container-inner -->