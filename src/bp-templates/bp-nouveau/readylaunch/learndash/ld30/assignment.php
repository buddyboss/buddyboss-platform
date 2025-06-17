<?php
/**
 * Assignment template for ReadyLaunch.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-rl-container">
	<div class="bb-rl-assignment">
		<?php the_content(); ?>
	</div>

	<?php
	bp_get_template_part( 'learndash/ld30/comments' );
	?>
</div>
