<?php
/**
 * Single View Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums" class="bb-rl-forums-topic-page">

	<div class="bb-rl-container-inner">

	<?php bbp_set_query_name( bbp_get_view_rewrite_id() ); ?>

	<?php if ( bbp_view_query() ) : ?>

		<?php bbp_get_template_part( 'loop', 'topics' ); ?>

		<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

	<?php else : ?>

		<?php bbp_get_template_part( 'feedback', 'no-topics' ); ?>

	<?php endif; ?>

	<?php bbp_reset_query_name(); ?>
	</div>
</div>
