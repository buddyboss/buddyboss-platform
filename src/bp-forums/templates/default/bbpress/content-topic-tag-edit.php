<?php

/**
 * Topic Tag Edit Content Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php bboss_bbp_breadcrumb(); ?>

	<?php bboss_bbp_topic_tag_description(); ?>

	<?php do_action( 'bbp_template_before_topic_tag_edit' ); ?>

	<?php bboss_bbp_get_template_part( 'form', 'topic-tag' ); ?>

	<?php do_action( 'bbp_template_after_topic_tag_edit' ); ?>

</div>
