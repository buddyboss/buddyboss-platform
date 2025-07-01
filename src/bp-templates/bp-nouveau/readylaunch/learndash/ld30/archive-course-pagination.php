<?php
/**
 * Archive course pagination template for ReadyLaunch.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\ReadyLaunch
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="bb-rl-course-pagination">
	<div class="bb-rl-pagination-links">
		<?php
		echo wp_kses_post(
			paginate_links(
				array(
					'prev_text' => sprintf(
					/* translators: %s is the previous text. */
						'<i class="bb-icons-rl-arrow-left"></i> %s',
						esc_html__( 'Previous', 'buddyboss' )
					),
					'next_text' => sprintf(
					/* translators: %s is the next text. */
						'%s <i class="bb-icons-rl-arrow-right"></i>',
						esc_html__( 'Next', 'buddyboss' )
					),
				)
			)
		);
		?>
	</div>
</div>
