<?php
/**
 * BuddyBoss Messages Sitewide Notices Widget.
 *
 * @package BuddyBoss\Messages
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * A widget that displays sitewide notices.
 *
 * @since BuddyPress 1.9.0
 */
class BP_Messages_Sitewide_Notices_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 */
	function __construct() {
		parent::__construct(
			'bp_messages_sitewide_notices_widget',
			__( '(BB) Sitewide Notices', 'buddyboss-platform' ),
			array(
				'classname'                   => 'widget_bp_core_sitewide_messages buddypress widget',
				'description'                 => __( 'Display Sitewide Notices posted by the site administrator', 'buddyboss-platform' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Render the widget.
	 *
	 * @see WP_Widget::widget() for a description of parameters.
	 *
	 * @param array $args     See {@WP_Widget::widget()}.
	 * @param array $instance See {@WP_Widget::widget()}.
	 */
	public function widget( $args, $instance ) {

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Don't display the widget if there are no Notices to show.
		$notices = BP_Messages_Notice::get_active();
		if ( empty( $notices ) ) {
			return;
		}

		extract( $args );

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';

		/**
		 * Filters the title of the Messages widget.
		 *
		 * @since BuddyPress 1.9.0
		 * @since BuddyPress 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $instance The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo wp_kses_post( $before_widget );
		echo wp_kses_post( $before_title ) . esc_html( $title ) . wp_kses_post( $after_title ); ?>

		<div class="bp-site-wide-message">
			<?php bp_message_get_notices(); ?>
		</div>

		<?php

		echo wp_kses_post( $after_widget );
	}

	/**
	 * Process the saved settings for the widget.
	 *
	 * @see WP_Widget::update() for a description of parameters and
	 *      return values.
	 *
	 * @param array $new_instance See {@WP_Widget::update()}.
	 * @param array $old_instance See {@WP_Widget::update()}.
	 * @return array $instance See {@WP_Widget::update()}.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
		return $instance;
	}

	/**
	 * Render the settings form for Appearance > Widgets.
	 *
	 * @see WP_Widget::form() for a description of parameters.
	 *
	 * @param array $instance See {@WP_Widget::form()}.
	 *
	 * @return string|null Widget form output.
	 */
	public function form( $instance ) {
		$instance = bp_parse_args(
			(array) $instance,
			array(
				'title' => '',
			)
		);

		$title = wp_strip_all_tags( $instance['title'] );
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'buddyboss-platform' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<?php
	}
}
