<?php
/**
 * Forums Loop Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

do_action( 'bbp_template_before_forums_loop' ); ?>

	<ul class="bb-rl-forums-list">
		<?php
		while ( bbp_forums() ) :
			bbp_the_forum();
			?>
			<?php bbp_get_template_part( 'loop-forum-card' ); ?>
		<?php endwhile; ?>
	</ul>

<?php do_action( 'bbp_template_after_forums_loop' ); ?>
