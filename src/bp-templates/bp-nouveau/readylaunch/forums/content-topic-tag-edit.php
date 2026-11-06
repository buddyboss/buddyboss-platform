<?php
/**
 * Topic Tag Edit Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php bboss_bbp_topic_tag_description(); ?>

	<?php do_action( 'bbp_template_before_topic_tag_edit' ); ?>

	<?php bboss_bbp_get_template_part( 'form', 'topic-tag' ); ?>

	<?php do_action( 'bbp_template_after_topic_tag_edit' ); ?>

</div>
