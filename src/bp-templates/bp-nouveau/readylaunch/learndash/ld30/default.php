<?php
/**
 * Default LearnDash Template for ReadyLaunch
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @version 1.0.0
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

// Get the ReadyLaunch instance to check if sidebar is enabled.
$readylaunch = bb_load_readylaunch();

// Debug message - this will be visible on the page if it's loading correctly.
echo '<!-- ReadyLaunch LearnDash template loading. Default template used. -->';
?>

<div class="bb-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-learndash-content' ); ?>>
					<header class="bb-rl-entry-header">
						<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
					</header>

					<div class="bb-rl-entry-content">
						<?php the_content(); ?>
					</div>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No content found.', 'buddyboss' ); ?></p>
		<?php endif; ?>
	</main>

	<?php if ( $readylaunch->bb_is_sidebar_enabled_for_courses() ) : ?>
		<aside class="bb-learndash-sidebar">
			<div class="bb-rl-sidebar-content">
				<?php do_action( 'bb_readylaunch_learndash_sidebar' ); ?>
			</div>
		</aside>
	<?php endif; ?>
</div>
