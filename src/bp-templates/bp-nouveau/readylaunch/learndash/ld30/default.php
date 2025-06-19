<?php
/**
 * LD default template for ReadyLaunch.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-rl-container bb-rl-container--ld-archive">
	<div class="bb-rl-lms-inner-block bb-rl-lms-inner-block--ld-archive">
		<div class="bb-rl-ld-archive">
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
	</div>
</div>
