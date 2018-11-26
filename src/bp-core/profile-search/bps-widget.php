<?php

class BP_Widget_Profile_Search extends WP_Widget {

	/**
	 * Sets up a new Search widget instance.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'bp-profile-search-widget',
			'description' => __( 'Displays the profile search form.', 'buddyboss' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'bps_widget', __( '(BuddyBoss) Profile Search', 'buddyboss' ), $widget_ops );
	}

	/**
	 * Outputs the content for the current Search widget instance.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Search widget instance.
	 */
	public function widget( $args ) {
		echo $args['before_widget'];

		// Profile search form
		bp_profile_search_show_form();

		echo $args['after_widget'];
	}

	/**
	 * Outputs the settings form for the Search widget.
	 *
	 * @param array $instance Current settings.
	 */
	public function form() {
		?>
		<p><?php printf( __('<a href="%s">Click here</a> to manage the profile search form.', 'buddyboss'), admin_url( 'edit.php?post_type=bp_ps_form' ) ); ?></p>
		<?php
	}

}

function buddyboss_profile_search_widget_init () {
	register_widget ( 'BP_Widget_Profile_Search' );
}

add_action ( 'widgets_init', 'buddyboss_profile_search_widget_init' );