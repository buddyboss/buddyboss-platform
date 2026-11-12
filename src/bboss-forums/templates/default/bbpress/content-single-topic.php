<?php

/**
 * Single Topic Content Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php bboss_bbp_breadcrumb(); ?>

	<?php do_action( 'bbp_template_before_single_topic' ); ?>

	<?php if ( post_password_required() ) : ?>

		<?php bboss_bbp_get_template_part( 'form', 'protected' ); ?>

	<?php else : ?>

		<?php bboss_bbp_topic_tag_list(); ?>

		<?php if ( bboss_bbp_show_lead_topic() ) : ?>

			<?php bboss_bbp_get_template_part( 'content', 'single-topic-lead' ); ?>

		<?php endif; ?>

		<?php if ( bboss_bbp_has_replies() ) : ?>

			<?php bboss_bbp_get_template_part( 'pagination', 'replies' ); ?>

			<?php bboss_bbp_get_template_part( 'loop', 'replies' ); ?>

			<?php bboss_bbp_get_template_part( 'pagination', 'replies' ); ?>

		<?php endif; ?>

		<?php bboss_bbp_get_template_part( 'form', 'reply' ); ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_single_topic' ); ?>

</div>
