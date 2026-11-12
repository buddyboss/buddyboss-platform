<?php

/**
 * Archive Topic Content Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php if ( bboss_bbp_allow_search() ) : ?>

		<div class="bbp-search-form">

			<?php bboss_bbp_get_template_part( 'form', 'search' ); ?>

		</div>

	<?php endif; ?>

	<?php bboss_bbp_breadcrumb(); ?>

	<?php
	if ( bboss_bbp_is_topic_tag() ) {
		bboss_bbp_topic_tag_description();}
	?>

	<?php do_action( 'bbp_template_before_topics_index' ); ?>

	<?php if ( bboss_bbp_has_topics() ) : ?>

		<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

		<?php bboss_bbp_get_template_part( 'loop', 'topics' ); ?>

		<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

	<?php else : ?>

		<?php bboss_bbp_get_template_part( 'feedback', 'no-topics' ); ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_topics_index' ); ?>

</div>
