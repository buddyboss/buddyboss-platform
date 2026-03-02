<?php
/**
 * Template for displaying course progress in the classroom sidebar.
 *
 * @package BuddyBoss
 * @version BuddyBoss 2.9.30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use memberpress\courses\helpers as helpers;
?>
<h1>
	<?php if ( helpers\Lessons::is_a_lesson( $post ) ) : ?>
		<a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>" class="text-black">
			<?php echo esc_html( $course->post_title ); ?>
		</a>
	<?php else : ?>
		<?php echo esc_html( $course->post_title ); ?>
	<?php endif; ?>
</h1>

<?php if ( is_user_logged_in() ) : ?>
	<div class="progress-bar">
		<div class="user-progress" data-value="<?php echo esc_attr( $course->user_progress( $current_user->ID ) ); ?>"></div>
	</div>
	<p class="progress-text">
		<span><?php echo esc_html( $course->user_progress( $current_user->ID ) . '% ' ); ?></span>
		<?php esc_html_e( 'Complete', 'buddyboss' ); ?>
	</p>
<?php endif; ?>
