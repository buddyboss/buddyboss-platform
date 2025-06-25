<?php
/**
 * LD default template for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_ld_group_single = isset( $args['is_ld_group_single'] ) ? $args['is_ld_group_single'] : false;
$single_class       = $is_ld_group_single ? 'single' : 'archive';
?>

<div class="bb-rl-ld-<?php echo esc_attr( $single_class ); ?>">
	<header class="entry-header">
		<h2 class="entry-title">
			<a href="<?php the_permalink(); ?>" rel="bookmark">
				<?php the_title(); ?>
			</a>
		</h2>
	</header>
	<div class="entry-content">
		<?php the_content(); ?>
	</div>
</div>
