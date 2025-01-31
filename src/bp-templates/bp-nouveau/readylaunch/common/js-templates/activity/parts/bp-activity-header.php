<?php
/**
 * ReadyLaunch - The template for displaying activity header.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

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
	<a class="bb-rl-model-close-button" href="#">
		<span class="bb-icons-rl-x"></span>
	</a>
</script>
