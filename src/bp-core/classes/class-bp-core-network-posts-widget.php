<?php
/**
 * BuddyBoss Network Posts Widget.
 *
 * @package BuddyBoss\Connections
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Network Posts Widget.
 *
 * @since 1.0.3
 */
class BP_Core_Network_Posts_Widget extends WP_Widget {


	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		// Setup widget name & description.
		$name        = _x( 'BuddyBoss - Network Posts', 'widget name', 'buddyboss' );
		$description = __( 'A dynamic list of network posts', 'buddyboss' );

		// Call WP_Widget constructor.
		parent::__construct(
			false,
			$name,
			array(
				'description'                 => $description,
				'classname'                   => 'network_posts_widget buddypress widget',
				'customize_selective_refresh' => true,
			)
		);

		if ( is_customize_preview() || is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.6.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'bp-widget-members' );
	}

	/**
	 * Display the Members widget.
	 *
	 * @since 1.0.3
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );

		$number     = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		$show_date  = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		$show_image = isset( $instance['show_image'] ) ? $instance['show_image'] : true;

		if ( ! function_exists( 'get_sites' ) ) {
			return;
		}

		$blogs = get_sites(
			array(
				'fields'  => 'ids',
				'orderby' => 'last_updated',
				'order'   => 'DESC',
			)
		);

		$args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		if ( $blogs ) {
			?> <ul id="network-list" class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true"> 
			<?php
			foreach ( $blogs as $blog_key ) {
				switch_to_blog( $blog_key );

				/**
				 * Filters the arguments for the Recent Posts widget.
				 *
				 * @since 3.4.0
				 *
				 * @see WP_Query::get_posts()
				 *
				 * @param array $args An array of arguments used to retrieve the recent posts.
				 */
				$r = new WP_Query(
					apply_filters(
						'widget_network_posts_args',
						array(
							'posts_per_page'      => $number,
							'no_found_rows'       => true,
							'post_status'         => 'publish',
							'ignore_sticky_posts' => true,
						)
					)
				);

				if ( $r->have_posts() ) {
					?>
								<?php
								while ( $r->have_posts() ) :
									$r->the_post();
									?>
						<li class="vcard">
							<div class="item-avatar">
								<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_nicename' ) ); ?>">
									<?php echo get_avatar( get_the_author_meta( 'ID' ), 80 ); ?>
								</a>
							</div>

							<div class="item">
								<div class="item-title">
									<a class="post-author" href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_nicename' ) ); ?>"><?php the_author(); ?></a>
									<span class="netowrk-post-type">created a post:</span>

								</div>
								<div class="item-data">
							<span class="netowrk-post-content">
								<a href="<?php the_permalink(); ?>" class="bb-title"><?php echo wp_trim_words( the_title( '', '', false ), 6, '&hellip;' ); ?></a>
							</span>
									<?php if ( $show_image && has_post_thumbnail() ) { ?>
										<div class="data-photo"><a href="<?php the_permalink(); ?>"
																   title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'buddyboss' ), the_title_attribute( 'echo=0' ) ) ); ?>"
																   class="entry-media entry-img">
												<?php the_post_thumbnail(); ?>
											</a>
										</div>
									<?php } ?>
									<?php if ( $show_date ) : ?>
										<br/><span class="netowrk-post-activity"><?php echo get_the_date(); ?></span>
									<?php endif; ?>
								</div>
							</div>
						</li>
					<?php endwhile; ?>
								<?php
								// Reset the global $the_post as this query will have stomped on it
								wp_reset_postdata();
				}
				// Back the current blog
				restore_current_blog();
			}
			?>
			 </ul>
			<?php
		}

		echo $args['after_widget'];

	}

	/**
	 * Update the Members widget options.
	 *
	 * @since 1.0.3
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['number']     = (int) $new_instance['number'];
		$instance['show_date']  = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$instance['show_image'] = isset( $new_instance['show_image'] ) ? (bool) $new_instance['show_image'] : false;

		return $instance;
	}

	/**
	 * Output the Members widget options form.
	 *
	 * @since 1.0.3
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {

		// Get widget settings.
		$title      = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : __( 'Recent Networkside Posts', 'buddyboss' );
		$number     = isset( $instance['number'] ) ? absint( $instance['number'] ) : 1;
		$show_date  = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : true;
		$show_image = isset( $instance['show_image'] ) ? (bool) $instance['show_image'] : true;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show for each blog:', 'buddyboss' ); ?></label>
			<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?', 'buddyboss' ); ?></label></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $show_image ); ?> id="<?php echo $this->get_field_id( 'show_image' ); ?>" name="<?php echo $this->get_field_name( 'show_image' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_image' ); ?>"><?php _e( 'Display post featured image?', 'buddyboss' ); ?></label></p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since 2.3.0
	 *
	 * @param array $instance Widget instance settings.
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bp_parse_args(
			$instance,
			array(
				'title'      => __( 'Recent Networkside Posts', 'buddyboss' ),
				'number'     => 1,
				'show_date'  => true,
				'show_image' => true,
			),
			'bb_network_posts_widget_settings'
		);
	}
}
