<?php
/**
 * Assignment template for ReadyLaunch.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 2.9.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-rl-container bb-rl-container--assignment">
	<div class="bb-rl-lms-inner-block bb-rl-lms-inner-block--assignment bb-rl-course-content-comments">
		<div class="bb-rl-assignment">
			<?php the_content(); ?>
		</div>

		<?php
		bp_get_template_part( 'learndash/ld30/comments' );
		?>
	</div>
</div>
