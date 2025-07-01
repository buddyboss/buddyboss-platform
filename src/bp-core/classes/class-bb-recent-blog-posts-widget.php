<?php
/**
 * BuddyBoss Recent Blog Posts Widget for ReadyLaunch.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 2.9.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Recent Blog Posts Widget.
 *
 * @since BuddyBoss 2.9.00
 */
class BB_Recent_Blog_Posts_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function __construct() {
		parent::__construct(
			'bb_recent_blog_posts_widget',
			esc_html__( 'Recent Blog Posts', 'buddyboss' ),
			array( 'description' => esc_html__( 'Displays recent blog posts.', 'buddyboss' ) )
		);
	}

	/**
	 * Display the widget.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {

		$recent_posts = wp_get_recent_posts(
			array(
				'numberposts' => 3,
				'post_status' => 'publish',
			)
		);
		$class_prefix = 'bb-';
		if ( bb_is_readylaunch_enabled() ) {
			$class_prefix = 'bb-rl-';
		}
		if ( ! empty( $recent_posts ) ) {
			echo wp_kses_post( $args['before_widget'] );

			$blogs_dir_url = function_exists( 'bp_get_blogs_directory_permalink' ) ? bp_get_blogs_directory_permalink() : '';
			?>
			<div class="widget-header">
				<h2 class="widget-title"><?php esc_html_e( 'Recent blog posts', 'buddyboss' ); ?></h2>
				<?php
				if ( ! empty( $blogs_dir_url ) ) {
					?>
					<a href="<?php echo esc_url( $blogs_dir_url ); ?>" class="widget-link">
						<?php esc_html_e( 'See all', 'buddyboss' ); ?>
					</a>
					<?php
				}
				?>
			</div>
			<div class="widget-content">
			<?php
			foreach ( $recent_posts as $post ) {
				?>
				<div class="<?php echo esc_attr( $class_prefix . 'recent-post' ); ?>">
					<?php
					if ( has_post_thumbnail( $post['ID'] ) ) {
						?>
						<div class="<?php echo esc_attr( $class_prefix . 'recent-post-thumb' ); ?>">
							<a href="<?php echo esc_url( get_permalink( $post['ID'] ) ); ?>" class="<?php echo esc_attr( $class_prefix . 'recent-post-thumb-link' ); ?>">
								<?php echo get_the_post_thumbnail( $post['ID'], 'medium' ); ?>
							</a>
						</div>
						<?php
					}
					?>
					<div class="<?php echo esc_attr( $class_prefix . 'recent-post-title' ); ?>">
						<a href="<?php echo esc_url( get_permalink( $post['ID'] ) ); ?>" class="<?php echo esc_attr( $class_prefix . 'recent-post-title-link' ); ?>">
							<?php echo esc_html( get_the_title( $post['ID'] ) ); ?>
						</a>
					</div>
					<div class="<?php echo esc_attr( $class_prefix . 'recent-post-date' ); ?>">
						<?php echo esc_html( human_time_diff( get_the_time( 'U', $post['ID'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'buddyboss' ) ); ?>
					</div>
				</div>
				<?php
			}
			?>
			</div>
			<?php
			echo wp_kses_post( $args['after_widget'] );
		}
	}
}
