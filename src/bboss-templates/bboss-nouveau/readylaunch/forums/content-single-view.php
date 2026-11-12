<?php
/**
 * Single View Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums" class="bb-rl-forums-topic-page">

	<div class="bb-rl-container-inner">

	<?php bboss_bbp_set_query_name( bboss_bbp_get_view_rewrite_id() ); ?>

	<?php if ( bboss_bbp_view_query() ) : ?>

		<?php bboss_bbp_get_template_part( 'loop', 'topics' ); ?>

		<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

	<?php else : ?>

		<?php bboss_bbp_get_template_part( 'feedback', 'no-topics' ); ?>

	<?php endif; ?>

	<?php bboss_bbp_reset_query_name(); ?>
	</div>
</div>
