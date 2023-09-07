<?php

/**
 * Forums Widgets
 *
 * Contains the forum list, topic list, reply list and login form widgets.
 *
 * @package BuddyBoss\Widgets
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Forums Login Widget
 *
 * Adds a widget which displays the login form
 *
 * @since bbPress (r2827)
 *
 * @uses WP_Widget
 */
class BBP_Login_Widget extends WP_Widget {

	/**
	 * Forums Login Widget
	 *
	 * Registers the login widget
	 *
	 * @since bbPress (r2827)
	 *
	 * @uses apply_filters() Calls 'bbp_login_widget_options' with the
	 *                        widget options
	 */
	public function __construct() {
		$widget_ops = apply_filters(
			'bbp_login_widget_options',
			array(
				'classname'                   => 'bbp_widget_login',
				'description'                 => __( 'A simple login form with optional links to sign-up and lost password pages.', 'buddyboss' ),
				'customize_selective_refresh' => true,
			)
		);

		parent::__construct( false, __( '(BB) Forum Login Widget', 'buddyboss' ), $widget_ops );
	}

	/**
	 * Register the widget
	 *
	 * @since bbPress (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'BBP_Login_Widget' );
	}

	/**
	 * Displays the output, the login form.
	 *
	 * @since bbPress (r2827)
	 *
	 * @param mixed $args     Arguments.
	 * @param array $instance Instance.
	 *
	 * @uses  apply_filters() Calls 'bbp_login_widget_title' with the title.
	 * @uses  get_template_part() To get the login/logged in form.
	 */
	public function widget( $args = array(), $instance = array() ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		// Typical WordPress filter.
		$settings['title'] = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );

		// Forums filters.
		$settings['title']    = apply_filters( 'bbp_login_widget_title', $settings['title'], $instance, $this->id_base );
		$settings['register'] = apply_filters( 'bbp_login_widget_register', $settings['register'], $instance, $this->id_base );
		$settings['lostpass'] = apply_filters( 'bbp_login_widget_lostpass', $settings['lostpass'], $instance, $this->id_base );

		echo $args['before_widget'];

		if ( ! empty( $settings['title'] ) ) {
			echo $args['before_title'] . $settings['title'] . $args['after_title'];
		}

		if ( ! is_user_logged_in() ) : ?>

			<form method="post" action="<?php bbp_wp_login_action( array( 'context' => 'login_post' ) ); ?>" class="bbp-login-form">
				<fieldset>
					<legend><?php _e( 'Log In', 'buddyboss' ); ?></legend>

					<div class="bbp-username">
						<label for="user_login"><?php _e( 'Username', 'buddyboss' ); ?>: </label>
						<input type="text" name="log" value="<?php bbp_sanitize_val( 'user_login', 'text' ); ?>" size="20" id="user_login" tabindex="<?php bbp_tab_index(); ?>" />
					</div>

					<div class="bbp-password">
						<label for="user_pass"><?php _e( 'Password', 'buddyboss' ); ?>: </label>
						<input type="password" name="pwd" value="<?php bbp_sanitize_val( 'user_pass', 'password' ); ?>" size="20" id="user_pass" tabindex="<?php bbp_tab_index(); ?>" />
					</div>

					<div class="bbp-remember-me">
						<input type="checkbox" name="rememberme" class="bs-styled-checkbox" value="forever" <?php checked( bbp_get_sanitize_val( 'rememberme', 'checkbox' ), true, true ); ?> id="rememberme" tabindex="<?php bbp_tab_index(); ?>" />
						<label for="rememberme"><?php _e( 'Remember Me', 'buddyboss' ); ?></label>
					</div>

					<div class="bbp-submit-wrapper">

						<?php do_action( 'login_form' ); ?>

						<button type="submit" name="user-submit" id="user-submit" tabindex="<?php bbp_tab_index(); ?>" class="button submit user-submit"><?php _e( 'Log In', 'buddyboss' ); ?></button>

						<?php bbp_user_login_fields(); ?>

					</div>

					<?php if ( ! empty( $settings['register'] ) || ! empty( $settings['lostpass'] ) ) : ?>

						<div class="bbp-login-links">

							<?php if ( ! empty( $settings['register'] ) ) : ?>

								<a href="<?php echo esc_url( $settings['register'] ); ?>" title="<?php esc_attr_e( 'Register', 'buddyboss' ); ?>" class="bbp-register-link"><?php _e( 'Register', 'buddyboss' ); ?></a>

							<?php endif; ?>

							<?php if ( ! empty( $settings['lostpass'] ) ) : ?>

								<a href="<?php echo esc_url( $settings['lostpass'] ); ?>" title="<?php esc_attr_e( 'Lost Password', 'buddyboss' ); ?>" class="bbp-lostpass-link"><?php _e( 'Lost Password', 'buddyboss' ); ?></a>

							<?php endif; ?>

						</div>

					<?php endif; ?>

				</fieldset>
			</form>

		<?php else : ?>

			<div class="bbp-logged-in">
				<a href="<?php bbp_user_profile_url( bbp_get_current_user_id() ); ?>" class="submit user-submit"><?php echo get_avatar( bbp_get_current_user_id(), '40' ); ?></a>
				<h5><?php bbp_user_profile_link( bbp_get_current_user_id() ); ?></h5>

				<?php bbp_logout_link(); ?>
			</div>

			<?php
		endif;

		echo $args['after_widget'];
	}

	/**
	 * Update the login widget options.
	 *
	 * @since bbPress (r2827)
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance             = $old_instance;
		$instance['title']    = strip_tags( $new_instance['title'] );
		$instance['register'] = esc_url_raw( $new_instance['register'] );
		$instance['lostpass'] = esc_url_raw( $new_instance['lostpass'] );

		return $instance;
	}

	/**
	 * Output the login widget options form.
	 *
	 * @since bbPress (r2827)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  BBP_Login_Widget::get_field_id() To output the field id.
	 * @uses  BBP_Login_Widget::get_field_name() To output the field name.
	 */
	public function form( $instance = array() ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>" /></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'register' ); ?>"><?php _e( 'Register URI:', 'buddyboss' ); ?>
			<input class="widefat" id="<?php echo $this->get_field_id( 'register' ); ?>" name="<?php echo $this->get_field_name( 'register' ); ?>" type="text" value="<?php echo esc_url( $settings['register'] ); ?>" /></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'lostpass' ); ?>"><?php _e( 'Lost Password URI:', 'buddyboss' ); ?>
			<input class="widefat" id="<?php echo $this->get_field_id( 'lostpass' ); ?>" name="<?php echo $this->get_field_name( 'lostpass' ); ?>" type="text" value="<?php echo esc_url( $settings['lostpass'] ); ?>" /></label>
		</p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since bbPress (r4802)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  bbp_parse_args() To merge widget settings into defaults
	 *
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bbp_parse_args(
			$instance,
			array(
				'title'    => '',
				'register' => '',
				'lostpass' => '',
			),
			'login_widget_settings'
		);
	}
}

/**
 * Forums Views Widget
 *
 * Adds a widget which displays the view list
 *
 * @since bbPress (r3020)
 *
 * @uses WP_Widget
 */
class BBP_Views_Widget extends WP_Widget {

	/**
	 * Forums View Widget
	 *
	 * Registers the view widget
	 *
	 * @since bbPress (r3020)
	 *
	 * @uses apply_filters() Calls 'bbp_views_widget_options' with the
	 *                        widget options
	 */
	public function __construct() {
		$widget_ops = apply_filters(
			'bbp_views_widget_options',
			array(
				'classname'                   => 'widget_display_views',
				'description'                 => __( 'A list of registered optional discussion views.', 'buddyboss' ),
				'customize_selective_refresh' => true,
			)
		);

		parent::__construct( false, __( '(BB) Forum Discussion Views List', 'buddyboss' ), $widget_ops );
	}

	/**
	 * Register the widget
	 *
	 * @since bbPress (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'BBP_Views_Widget' );
	}

	/**
	 * Displays the output, the view list.
	 *
	 * @since bbPress (r3020)
	 *
	 * @param mixed $args     Arguments.
	 * @param array $instance Instance.
	 *
	 * @uses  apply_filters() Calls 'bbp_view_widget_title' with the title.
	 * @uses  bbp_get_views() To get the views.
	 * @uses  bbp_view_url() To output the view url.
	 * @uses  bbp_view_title() To output the view title.
	 */
	public function widget( $args = array(), $instance = array() ) {

		// Only output widget contents if views exist.
		if ( ! bbp_get_views() ) {
			return;
		}

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		// Typical WordPress filter.
		$settings['title'] = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );

		// Forums filter.
		$settings['title'] = apply_filters( 'bbp_view_widget_title', $settings['title'], $instance, $this->id_base );

		// Start an output buffer.
		ob_start();

		echo $args['before_widget'];

		if ( ! empty( $settings['title'] ) ) {
			echo $args['before_title'] . $settings['title'] . $args['after_title'];
		}
		?>

		<ul>

			<?php foreach ( array_keys( bbp_get_views() ) as $view ) : ?>

				<li><a class="bbp-view-title" href="<?php bbp_view_url( $view ); ?>"><?php bbp_view_title( $view ); ?></a></li>

			<?php endforeach; ?>

		</ul>

		<?php
		echo $args['after_widget'];

		// Output the current buffer.
		echo ob_get_clean();
	}

	/**
	 * Update the view widget options.
	 *
	 * @since bbPress (r3020)
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 *
	 * @return array
	 */
	public function update( $new_instance = array(), $old_instance = array() ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Output the view widget options form.
	 *
	 * @since bbPress (r3020)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  BBP_Views_Widget::get_field_id() To output the field id.
	 * @uses  BBP_Views_Widget::get_field_name() To output the field name.
	 */
	public function form( $instance = array() ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>" />
			</label>
		</p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since bbPress (r4802)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  bbp_parse_args() To merge widget settings into defaults.
	 *
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bbp_parse_args(
			$instance,
			array(
				'title' => '',
			),
			'view_widget_settings'
		);
	}
}

/**
 * Forums Search Widget
 *
 * Adds a widget which displays the forum search form
 *
 * @since bbPress (r4579)
 *
 * @uses WP_Widget
 */
class BBP_Search_Widget extends WP_Widget {

	/**
	 * Forums Search Widget
	 *
	 * Registers the search widget
	 *
	 * @since bbPress (r4579)
	 *
	 * @uses apply_filters() Calls 'bbp_search_widget_options' with the
	 *                        widget options
	 */
	public function __construct() {
		$widget_ops = apply_filters(
			'bbp_search_widget_options',
			array(
				'classname'                   => 'widget_display_search',
				'description'                 => __( 'The forums search form.', 'buddyboss' ),
				'customize_selective_refresh' => true,
			)
		);

		parent::__construct( false, __( '(BB) Forum Search Form', 'buddyboss' ), $widget_ops );
	}

	/**
	 * Register the widget
	 *
	 * @since bbPress (r4579)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'BBP_Search_Widget' );
	}

	/**
	 * Displays the output, the search form.
	 *
	 * @since bbPress (r4579)
	 *
	 * @param mixed $args     Arguments.
	 * @param array $instance Instance.
	 *
	 * @uses  apply_filters() Calls 'bbp_search_widget_title' with the title.
	 * @uses  get_template_part() To get the search form.
	 */
	public function widget( $args, $instance ) {

		// Bail if search is disabled.
		if ( ! bbp_allow_search() ) {
			return;
		}

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		// Typical WordPress filter.
		$settings['title'] = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );

		// Forums filter.
		$settings['title'] = apply_filters( 'bbp_search_widget_title', $settings['title'], $instance, $this->id_base );

		echo $args['before_widget'];

		if ( ! empty( $settings['title'] ) ) {
			echo $args['before_title'] . $settings['title'] . $args['after_title'];
		}

		bbp_get_template_part( 'form', 'search' );

		echo $args['after_widget'];
	}

	/**
	 * Update the widget options.
	 *
	 * @since bbPress (r4579)
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Output the search widget options form.
	 *
	 * @since bbPress (r4579)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  BBP_Search_Widget::get_field_id() To output the field id.
	 * @uses  BBP_Search_Widget::get_field_name() To output the field name.
	 */
	public function form( $instance ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>" />
			</label>
		</p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since bbPress (r4802)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  bbp_parse_args() To merge widget settings into defaults.
	 *
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bbp_parse_args(
			$instance,
			array(
				'title' => __( 'Search Forums', 'buddyboss' ),
			),
			'search_widget_settings'
		);
	}
}

/**
 * Forums Forum Widget
 *
 * Adds a widget which displays the forum list
 *
 * @since bbPress (r2653)
 *
 * @uses WP_Widget
 */
class BBP_Forums_Widget extends WP_Widget {

	/**
	 * Forums Forum Widget.
	 *
	 * Registers the forum widget.
	 *
	 * @since bbPress (r2653)
	 *
	 * @uses apply_filters() Calls 'bbp_forums_widget_options' with the
	 *                        widget options.
	 */
	public function __construct() {
		$widget_ops = apply_filters(
			'bbp_forums_widget_options',
			array(
				'classname'                   => 'widget_display_forums',
				'description'                 => __( 'A list of forums with an option to set the parent.', 'buddyboss' ),
				'customize_selective_refresh' => true,
			)
		);

		parent::__construct( false, __( '(BB) Forums List', 'buddyboss' ), $widget_ops );
	}

	/**
	 * Register the widget.
	 *
	 * @since bbPress (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'BBP_Forums_Widget' );
	}

	/**
	 * Displays the output, the forum list.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param mixed $args     Arguments.
	 * @param array $instance Instance.
	 *
	 * @uses  apply_filters() Calls 'bbp_forum_widget_title' with the title.
	 * @uses  get_option() To get the forums per page option.
	 * @uses  current_user_can() To check if the current user can read
	 *                           private() To resety name.
	 * @uses  bbp_has_forums() The main forum loop.
	 * @uses  bbp_forums() To check whether there are more forums available
	 *                     in the loop.
	 * @uses  bbp_the_forum() Loads up the current forum in the loop.
	 * @uses  bbp_forum_permalink() To display the forum permalink.
	 * @uses  bbp_forum_title() To display the forum title.
	 */
	public function widget( $args, $instance ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		// Typical WordPress filter.
		$settings['title'] = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );

		// Forums filter.
		$settings['title'] = apply_filters( 'bbp_forum_widget_title', $settings['title'], $instance, $this->id_base );

		$parent_id = ( ! empty( $settings['parent_forum'] ) ? $settings['parent_forum'] : 0 );

		$parent_id = ! is_numeric( $parent_id ) && 'any' !== $parent_id ? 0 : $parent_id;

		// Note: private and hidden forums will be excluded via the
		// bbp_pre_get_posts_normalize_forum_visibility action and function.
		$widget_query = new WP_Query(
			array(
				'post_type'              => bbp_get_forum_post_type(),
				'post_parent'            => 'any' !== $parent_id ? $parent_id : 0,
				'post_status'            => bbp_get_public_status_id(),
				// Order.
				'posts_per_page'         => bbp_get_forums_per_page(),
				'orderby'                => 'menu_order title',
				'order'                  => 'ASC',

				// Performance.
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		// Bail if no posts.
		if ( ! $widget_query->have_posts() ) {
			return;
		}

		echo $args['before_widget'];

		if ( ! empty( $settings['title'] ) ) {
			echo $args['before_title'] . $settings['title'] . $args['after_title'];
		}
		?>

		<ul class="bb-sidebar-forums">

			<?php
			while ( $widget_query->have_posts() ) :
				$widget_query->the_post();
				?>

				<li>
					<a class="bbp-forum-title" href="<?php bbp_forum_permalink( $widget_query->post->ID ); ?>"><?php bbp_forum_title( $widget_query->post->ID ); ?></a>
					<span class="topics-count">
						<?php
						echo bbp_get_forum_topic_count( $widget_query->post->ID );
						?>
					</span>
					<?php
					if ( 0 !== $parent_id ) {
						$r = array(
							'before'           => '<ul class="bb-sidebar-forums">',
							'after'            => '</ul>',
							'link_before'      => '<li class="bbp-sub-forum">',
							'link_after'       => '</li>',
							'count_before'     => ' (',
							'count_after'      => ')',
							'count_sep'        => ', ',
							'separator'        => ' ',
							'forum_id'         => $widget_query->post->ID,
							'show_topic_count' => false,
							'show_reply_count' => false,
						);

						echo bb_get_list_forums_recursively( $r );
					}
					?>
				</li>

			<?php endwhile; ?>

		</ul>

		<?php
		echo $args['after_widget'];

		// Reset the $post global.
		wp_reset_postdata();
	}

	/**
	 * Update the forum widget options.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                 = $old_instance;
		$instance['title']        = strip_tags( $new_instance['title'] );
		$instance['parent_forum'] = sanitize_text_field( $new_instance['parent_forum'] );

		// Force to any.
		if ( ! empty( $instance['parent_forum'] ) && ! is_numeric( $instance['parent_forum'] ) ) {
			$instance['parent_forum'] = 'any';
		}

		return $instance;
	}

	/**
	 * Output the forum widget options form.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  BBP_Forums_Widget::get_field_id() To output the field id.
	 * @uses  BBP_Forums_Widget::get_field_name() To output the field name.
	 */
	public function form( $instance ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'parent_forum' ); ?>"><?php _e( 'Parent Forum ID:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'parent_forum' ); ?>" name="<?php echo $this->get_field_name( 'parent_forum' ); ?>" type="text" value="<?php echo esc_attr( $settings['parent_forum'] ); ?>" />
			</label>

			<br />

			<small><?php _e( '"0" to show only root - "any" to show all', 'buddyboss' ); ?></small>
		</p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since bbPress (r4802)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  bbp_parse_args() To merge widget settings into defaults.
	 *
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bbp_parse_args(
			$instance,
			array(
				'title'        => __( 'Forums', 'buddyboss' ),
				'parent_forum' => 0,
			),
			'forum_widget_settings'
		);
	}
}

/**
 * Forums Topic Widget.
 *
 * Adds a widget which displays the topic list.
 *
 * @since bbPress (r2653)
 *
 * @uses WP_Widget
 */
class BBP_Topics_Widget extends WP_Widget {

	/**
	 * Forums Topic Widget.
	 *
	 * Registers the topic widget.
	 *
	 * @since bbPress (r2653)
	 *
	 * @uses apply_filters() Calls 'bbp_topics_widget_options' with the
	 *                        widget options.
	 */
	public function __construct() {
		$widget_ops = apply_filters(
			'bbp_topics_widget_options',
			array(
				'classname'                   => 'widget_display_topics',
				'description'                 => __( 'A list of recent discussions, sorted by popularity or freshness.', 'buddyboss' ),
				'customize_selective_refresh' => true,
			)
		);

		parent::__construct( false, __( '(BB) Forum Recent Topics', 'buddyboss' ), $widget_ops );
	}

	/**
	 * Register the widget.
	 *
	 * @since bbPress (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'BBP_Topics_Widget' );
	}

	/**
	 * Displays the output, the topic list.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param mixed $args     Arguments.
	 * @param array $instance Instance.
	 *
	 * @uses  apply_filters() Calls 'bbp_topic_widget_title' with the title.
	 * @uses  bbp_topic_permalink() To display the topic permalink.
	 * @uses  bbp_topic_title() To display the topic title.
	 * @uses  bbp_get_topic_last_active_time() To get the topic last active
	 *                                         time.
	 * @uses  bbp_get_topic_id() To get the topic id.
	 */
	public function widget( $args = array(), $instance = array() ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		// Typical WordPress filter.
		$settings['title'] = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );

		// Forums filter.
		$settings['title'] = apply_filters( 'bbp_topic_widget_title', $settings['title'], $instance, $this->id_base );

		// How do we want to order our results?
		switch ( $settings['order_by'] ) {

			// Order by most recent replies.
			case 'freshness':
				$topics_query = array(
					'post_type'              => bbp_get_topic_post_type(),
					'post_parent'            => $settings['parent_forum'],
					'posts_per_page'         => (int) $settings['max_shown'],
					'post_status'            => array( bbp_get_public_status_id(), bbp_get_closed_status_id() ),
					'meta_query'             => array(
						array(
							'key'  => '_bbp_last_active_time',
							'type' => 'DATETIME',
						),
					),
					// Ordering.
					'orderby'                => 'meta_value',
					'order'                  => 'DESC',
					// Performance.
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				);
				break;

			// Order by total number of replies.
			case 'popular':
				$topics_query = array(
					'post_type'              => bbp_get_topic_post_type(),
					'post_parent'            => $settings['parent_forum'],
					'posts_per_page'         => (int) $settings['max_shown'],
					'meta_query'             => array(
						array(
							'key'  => '_bbp_reply_count',
							'type' => 'NUMERIC',
						),
					),
					'post_status'            => array( bbp_get_public_status_id(), bbp_get_closed_status_id() ),
					// Ordering.
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					// Performance.
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				);
				break;

			// Order by which topic was created most recently.
			case 'newness':
			default:
				$topics_query = array(
					'post_type'              => bbp_get_topic_post_type(),
					'post_parent'            => $settings['parent_forum'],
					'posts_per_page'         => (int) $settings['max_shown'],
					'post_status'            => array( bbp_get_public_status_id(), bbp_get_closed_status_id() ),
					// Ordering.
					'orderby'                => 'date',
					'order'                  => 'DESC',
					// Performance.
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				);
				break;
		}

		// Note: private and hidden forums will be excluded via the
		// bbp_pre_get_posts_normalize_forum_visibility action and function.
		$widget_query = new WP_Query( $topics_query );

		// Bail if no topics are found.
		if ( ! $widget_query->have_posts() ) {
			return;
		}

		// Start an output buffer.
		ob_start();

		echo $args['before_widget'];

		if ( ! empty( $settings['title'] ) ) {
			echo $args['before_title'] . $settings['title'] . $args['after_title'];
		}
		?>

		<ul>

			<?php
			while ( $widget_query->have_posts() ) :

				$widget_query->the_post();
				$topic_id    = bbp_get_topic_id( $widget_query->post->ID );
				$author_link = '';

				// Maybe get the topic author.
				if ( ! empty( $settings['show_user'] ) ) :
					$author_link = bbp_get_topic_author_link(
						array(
							'post_id' => $topic_id,
							'type'    => 'both',
							'size'    => 14,
						)
					);
					$author_related_class = 'bbp-topic-has-avatar';
				else :
					$author_related_class = 'bbp-topic-no-avatar';
				endif;

				$author_url = bbp_get_topic_author_url( $topic_id );
				?>

				<li class="<?php echo $author_related_class; ?>">

					<?php if ( ! empty( $author_link ) ) : ?>

						<a href="<?php echo esc_url( $author_url ); ?>" class="bbp-author-link" rel="nofollow">
							<span class="bbp-author-avatar">
								<?php echo bbp_get_topic_author_avatar( $topic_id ); ?>
							</span>
						</a>

					<?php endif; ?>

					<div class="bbp-topic-info">
						<a class="bbp-forum-title" href="<?php bbp_topic_permalink( $topic_id ); ?>"><?php bbp_topic_title( $topic_id ); ?></a>

						<?php
						if ( ! empty( $author_link ) ) :
							printf( __( 'by %1$s', 'buddyboss' ), '<span class="topic-author"><a href="' . esc_url( $author_url ) . '">' . bbp_get_topic_author_display_name( $topic_id ) . '</a></span>' );
						endif;
						?>

						<?php if ( ! empty( $settings['show_date'] ) ) : ?>

							<div class="time-since"><?php bbp_topic_last_active_time( $topic_id ); ?></div>

						<?php endif; ?>
					</div>
				</li>

			<?php endwhile; ?>

		</ul>

		<?php
		echo $args['after_widget'];

		// Reset the $post global.
		wp_reset_postdata();

		// Output the current buffer.
		echo ob_get_clean();
	}

	/**
	 * Update the topic widget options.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 *
	 * @return array
	 */
	public function update( $new_instance = array(), $old_instance = array() ) {
		$instance                 = $old_instance;
		$instance['title']        = strip_tags( $new_instance['title'] );
		$instance['order_by']     = strip_tags( $new_instance['order_by'] );
		$instance['parent_forum'] = sanitize_text_field( $new_instance['parent_forum'] );
		$instance['max_shown']    = (int) $new_instance['max_shown'];

		// Date.
		$instance['show_date'] = isset( $new_instance['show_date'] )
			? (bool) $new_instance['show_date']
			: false;

		// Author.
		$instance['show_user'] = isset( $new_instance['show_user'] )
			? (bool) $new_instance['show_user']
			: false;

		// Force to any.
		if ( ! empty( $instance['parent_forum'] ) && ! is_numeric( $instance['parent_forum'] ) ) {
			$instance['parent_forum'] = 'any';
		}

		return $instance;
	}

	/**
	 * Output the topic widget options form.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  BBP_Topics_Widget::get_field_id() To output the field id.
	 * @uses  BBP_Topics_Widget::get_field_name() To output the field name.
	 */
	public function form( $instance = array() ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );
		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'max_shown' ); ?>"><?php _e( 'Maximum topics to show:', 'buddyboss' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_shown' ); ?>" name="<?php echo $this->get_field_name( 'max_shown' ); ?>" type="text" value="<?php echo esc_attr( $settings['max_shown'] ); ?>" /></label></p>

		<p>
			<label for="<?php echo $this->get_field_id( 'parent_forum' ); ?>"><?php _e( 'Parent Forum ID:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'parent_forum' ); ?>" name="<?php echo $this->get_field_name( 'parent_forum' ); ?>" type="text" value="<?php echo esc_attr( $settings['parent_forum'] ); ?>" />
			</label>

			<br />

			<small><?php _e( '"0" to show only root - "any" to show all', 'buddyboss' ); ?></small>
		</p>

		<p><label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show post date:', 'buddyboss' ); ?> <input type="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" <?php checked( true, $settings['show_date'] ); ?> value="1" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'show_user' ); ?>"><?php _e( 'Show discussion author:', 'buddyboss' ); ?> <input type="checkbox" id="<?php echo $this->get_field_id( 'show_user' ); ?>" name="<?php echo $this->get_field_name( 'show_user' ); ?>" <?php checked( true, $settings['show_user'] ); ?> value="1" /></label></p>

		<p>
			<label for="<?php echo $this->get_field_id( 'order_by' ); ?>"><?php _e( 'Order By:', 'buddyboss' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'order_by' ); ?>" id="<?php echo $this->get_field_name( 'order_by' ); ?>">
				<option <?php selected( $settings['order_by'], 'newness' ); ?> value="newness"><?php _e( 'Newest Discussions', 'buddyboss' ); ?></option>
				<option <?php selected( $settings['order_by'], 'popular' ); ?> value="popular"><?php _e( 'Popular Discussions', 'buddyboss' ); ?></option>
				<option <?php selected( $settings['order_by'], 'freshness' ); ?> value="freshness"><?php _e( 'Discussions With Recent Replies', 'buddyboss' ); ?></option>
			</select>
		</p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since bbPress (r4802)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  bbp_parse_args() To merge widget options into defaults.
	 *
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bbp_parse_args(
			$instance,
			array(
				'title'        => __( 'Recent Discussions', 'buddyboss' ),
				'max_shown'    => 5,
				'show_date'    => false,
				'show_user'    => false,
				'parent_forum' => 'any',
				'order_by'     => false,
			),
			'topic_widget_settings'
		);
	}
}

/**
 * Forums Stats Widget.
 *
 * Adds a widget which displays the forum statistics.
 *
 * @since bbPress (r4509)
 *
 * @uses WP_Widget
 */
class BBP_Stats_Widget extends WP_Widget {

	/**
	 * Forums Stats Widget.
	 *
	 * Registers the stats widget
	 *
	 * @since bbPress (r4509)
	 *
	 * @uses  apply_filters() Calls 'bbp_stats_widget_options' with the
	 *        widget options.
	 */
	public function __construct() {
		$widget_ops = apply_filters(
			'bbp_stats_widget_options',
			array(
				'classname'                   => 'widget_display_stats',
				'description'                 => __( 'Some statistics from your forum.', 'buddyboss' ),
				'customize_selective_refresh' => true,
			)
		);

		parent::__construct( false, __( '(BB) Forum Statistics', 'buddyboss' ), $widget_ops );
	}

	/**
	 * Register the widget.
	 *
	 * @since bbPress (r4509)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'BBP_Stats_Widget' );
	}

	/**
	 * Displays the output, the statistics.
	 *
	 * @since bbPress (r4509)
	 *
	 * @param mixed $args     Arguments.
	 * @param array $instance Instance.
	 *
	 * @uses  apply_filters() Calls 'bbp_stats_widget_title' with the title.
	 * @uses  bbp_get_template_part() To get the content-forum-statistics template.
	 */
	public function widget( $args = array(), $instance = array() ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		// Typical WordPress filter.
		$settings['title'] = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );

		// Forums widget title filter.
		$settings['title'] = apply_filters( 'bbp_stats_widget_title', $settings['title'], $instance, $this->id_base );

		echo $args['before_widget'];

		if ( ! empty( $settings['title'] ) ) {
			echo $args['before_title'] . $settings['title'] . $args['after_title'];
		}

		bbp_get_template_part( 'content', 'statistics' );

		echo $args['after_widget'];
	}

	/**
	 * Update the stats widget options.
	 *
	 * @since bbPress (r4509)
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Output the stats widget options form.
	 *
	 * @since bbPress (r4509)
	 *
	 * @param array $instance Instance.
	 *
	 * @return string|void
	 */
	public function form( $instance ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>"/>
			</label>
		</p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since bbPress (r4802)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  bbp_parse_args() To merge widget settings into defaults.
	 *
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bbp_parse_args(
			$instance,
			array(
				'title' => __( 'Forum Statistics', 'buddyboss' ),
			),
			'stats_widget_settings'
		);
	}
}

/**
 * Forums Replies Widget.
 *
 * Adds a widget which displays the replies list.
 *
 * @since bbPress (r2653)
 *
 * @uses WP_Widget
 */
class BBP_Replies_Widget extends WP_Widget {

	/**
	 * Forums Replies Widget.
	 *
	 * Registers the replies widget.
	 *
	 * @since bbPress (r2653)
	 *
	 * @uses apply_filters() Calls 'bbp_replies_widget_options' with the
	 *                        widget options.
	 */
	public function __construct() {
		$widget_ops = apply_filters(
			'bbp_replies_widget_options',
			array(
				'classname'                   => 'widget_display_replies',
				'description'                 => __( 'A list of the most recent replies.', 'buddyboss' ),
				'customize_selective_refresh' => true,
			)
		);

		parent::__construct( false, __( '(BB) Forum Recent Replies', 'buddyboss' ), $widget_ops );
	}

	/**
	 * Register the widget.
	 *
	 * @since bbPress (r3389)
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'BBP_Replies_Widget' );
	}

	/**
	 * Displays the output, the replies list.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param mixed $args     Arguments.
	 * @param array $instance Instance.
	 *
	 * @uses  apply_filters() Calls 'bbp_reply_widget_title' with the title.
	 * @uses  bbp_get_reply_author_link() To get the reply author link.
	 * @uses  bbp_get_reply_id() To get the reply id.
	 * @uses  bbp_get_reply_url() To get the reply url.
	 * @uses  bbp_get_reply_excerpt() To get the reply excerpt.
	 * @uses  bbp_get_reply_topic_title() To get the reply topic title.
	 * @uses  get_the_date() To get the date of the reply.
	 * @uses  get_the_time() To get the time of the reply.
	 */
	public function widget( $args, $instance ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		// Typical WordPress filter.
		$settings['title'] = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );

		// Forums filter.
		$settings['title'] = apply_filters( 'bbp_replies_widget_title', $settings['title'], $instance, $this->id_base );

		// Note: private and hidden forums will be excluded via the
		// bbp_pre_get_posts_normalize_forum_visibility action and function.
		$widget_query = new WP_Query(
			array(
				'post_type'              => bbp_get_reply_post_type(),
				'post_status'            => array( bbp_get_public_status_id(), bbp_get_closed_status_id() ),
				'posts_per_page'         => (int) $settings['max_shown'],
				// Performance.
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		// Bail if no replies.
		if ( ! $widget_query->have_posts() ) {
			return;
		}

		// Start an output buffer.
		ob_start();

		echo $args['before_widget'];

		if ( ! empty( $settings['title'] ) ) {
			echo $args['before_title'] . $settings['title'] . $args['after_title'];
		}
		?>

		<ul>

			<?php
			while ( $widget_query->have_posts() ) :
				$widget_query->the_post();
				?>

				<?php

				// Verify the reply ID.
				$reply_id   = bbp_get_reply_id( $widget_query->post->ID );
				$reply_link = '<a class="bbp-reply-topic-title" href="' . esc_url( bbp_get_reply_url( $reply_id ) ) . '" title="' . esc_attr( bbp_get_reply_excerpt( $reply_id, 50 ) ) . '">' . bbp_get_reply_topic_title( $reply_id ) . '</a>';

				// Only query user if showing them.
				if ( ! empty( $settings['show_user'] ) ) :
					$author_link          = bbp_get_reply_author_link(
						array(
							'post_id' => $reply_id,
							'type'    => 'both',
							'size'    => 14,
						)
					);
					$author_related_class = 'bbp-reply-topic-has-avatar';
				else :
					$author_link          = false;
					$author_related_class = 'bbp-reply-topic-no-avatar';
				endif;

				$reply_author_url = bbp_get_reply_author_url( $reply_id );

				?>

				<li class="<?php echo $author_related_class; ?>">
					<?php if ( ! empty( $author_link ) ) : ?>

					<a href="<?php echo esc_url( $reply_author_url ); ?>" class="bbp-author-link" rel="nofollow">
						<span class="bbp-author-avatar">
							<?php echo bbp_get_reply_author_avatar( $reply_id ); ?>
						</span>
					</a>

					<?php endif; ?>

					<div class="bbp-reply-info">

						<?php
						if ( ! empty( $author_link ) ) :
							printf( __( '%1$s on ', 'buddyboss' ), '<span class="reply-author"><a href="' . esc_url( $reply_author_url ) . '">' . bbp_get_reply_author_display_name( $reply_id ) . '</a></span>' );
						endif;

						echo $reply_link;

						if ( ! empty( $settings['show_date'] ) ) : ?>

							<div class="time-since"><?php bbp_topic_last_active_time( $reply_id ); ?></div>

						<?php endif; ?>
					</div>
				</li>

			<?php endwhile; ?>

		</ul>

		<?php
		echo $args['after_widget'];

		// Reset the $post global.
		wp_reset_postdata();

		// Output the current buffer.
		echo ob_get_clean();
	}

	/**
	 * Update the reply widget options.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 *
	 * @return array
	 */
	public function update( $new_instance = array(), $old_instance = array() ) {
		$instance              = $old_instance;
		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['max_shown'] = (int) $new_instance['max_shown'];

		// Date.
		$instance['show_date'] = isset( $new_instance['show_date'] )
			? (bool) $new_instance['show_date']
			: false;

		// Author.
		$instance['show_user'] = isset( $new_instance['show_user'] )
			? (bool) $new_instance['show_user']
			: false;

		return $instance;
	}

	/**
	 * Output the reply widget options form.
	 *
	 * @since bbPress (r2653)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  BBP_Replies_Widget::get_field_id() To output the field id.
	 * @uses  BBP_Replies_Widget::get_field_name() To output the field name.
	 */
	public function form( $instance = array() ) {

		// Get widget settings.
		$settings = $this->parse_settings( $instance );
		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddyboss' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'max_shown' ); ?>"><?php _e( 'Maximum replies to show:', 'buddyboss' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_shown' ); ?>" name="<?php echo $this->get_field_name( 'max_shown' ); ?>" type="text" value="<?php echo esc_attr( $settings['max_shown'] ); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Show post date:', 'buddyboss' ); ?> <input type="checkbox" id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" <?php checked( true, $settings['show_date'] ); ?> value="1" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'show_user' ); ?>"><?php _e( 'Show reply author:', 'buddyboss' ); ?> <input type="checkbox" id="<?php echo $this->get_field_id( 'show_user' ); ?>" name="<?php echo $this->get_field_name( 'show_user' ); ?>" <?php checked( true, $settings['show_user'] ); ?> value="1" /></label></p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since bbPress (r4802)
	 *
	 * @param array $instance Instance.
	 *
	 * @uses  bbp_parse_args() To merge widget settings into defaults.
	 *
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bbp_parse_args(
			$instance,
			array(
				'title'     => __( 'Recent Replies', 'buddyboss' ),
				'max_shown' => 5,
				'show_date' => false,
				'show_user' => false,
			),
			'replies_widget_settings'
		);
	}
}
