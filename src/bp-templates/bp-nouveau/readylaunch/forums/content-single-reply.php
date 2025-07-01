<?php
/**
 * Single Reply Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="bbpress-forums" class="bb-rl-forums-topic-page bb-rl-reply-single-page">
	<div class="bb-rl-forums-container-inner">


		<div class="bb-rl-single-forum-list">
			<?php do_action( 'bbp_template_before_single_reply' ); ?>

			<?php if ( post_password_required() ) : ?>

				<?php bbp_get_template_part( 'form', 'protected' ); ?>

			<?php else : ?>

				<?php bbp_get_template_part( 'loop', 'single-reply' ); ?>

			<?php endif; ?>

			<?php do_action( 'bbp_template_after_single_reply' ); ?>
		</div>
	</div>
</div>
