<?php
/**
 * LD default template for ReadyLaunch.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$page_class       = isset( $args['page_class'] ) ? $args['page_class'] : 'archive';
$course_post_type = isset( $args['post_type'] ) ? $args['post_type'] : '';
?>

<div class="bb-rl-ld-default bb-rl-ld-<?php echo esc_attr( $page_class ); ?> bb-rl-<?php echo esc_attr( $page_class ); ?>" >
	<?php
	if ( 'page' !== $course_post_type ) {
		?>
		<header class="entry-header">
			<h2 class="entry-title">
				<a href="<?php the_permalink(); ?>" rel="bookmark">
					<?php the_title(); ?>
				</a>
			</h2>
		</header>
		<?php
	}
	?>
	<div class="entry-content">
		<?php the_content(); ?>
	</div>
</div>
