<?php
/**
 * The right sidebar for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package ReadyLaunch
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
$available_widgets = array();

// @todo enable based on the enabled widget for specific page.
if ( bp_is_user() ) {
	$available_widgets[] = 'BB_Core_Follow_My_Network_Widget';
	$available_widgets[] = 'BP_Core_Recently_Active_Widget';
	$available_widgets[] = 'BP_Core_Friends_Widget';
}

if ( count( $available_widgets ) ) {
	?>
	<div id="bb-rl-right-sidebar" class="bb-rl-widget-sidebar sm-grid-1-1" role="complementary">
		<?php
			ob_start();
			foreach( $available_widgets as $widget ) {
				the_widget( $widget, false, array( 'before_title'   => '<h2 class="widget-title">' ) );
			}

			$output = ob_get_clean();
			
			if ( ! empty( trim( $output ) ) ) {
				echo $output;
			}
		?>
	</div>
	<?php
}
