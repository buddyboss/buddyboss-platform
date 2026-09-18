<?php
/**
 * ReadyLaunch - Activity Header JS Templates.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<script type="text/html" id="tmpl-activity-header">
	<h3>
		<span class="activity-header-data">
			<# if ( data.edit_activity === true ) {  #>
				<# if ( data.activity_action_type === 'scheduled' ) {  #>
					<?php esc_html_e( 'Edit Scheduled Post', 'buddyboss' ); ?>
				<# } else { #>
					<?php esc_html_e( 'Edit post', 'buddyboss' ); ?>
				<# } #>
			<# } else { #>
				<?php esc_html_e( 'Create a post', 'buddyboss' ); ?>
			<# } #>
		<span>
	</h3>
	<span class="bb-rl-modal-header-actions">
		<a class="bb-rl-model-close-button" href="#">
			<span class="bb-icons-rl-x"></span>

			<?php
			/**
			* Fires after the activity header close button.
			*
			* @since BuddyBoss 2.13.0
			*/
			do_action( 'bb_activity_header_after' );
			?>
		</a>
	</span>
</script>
