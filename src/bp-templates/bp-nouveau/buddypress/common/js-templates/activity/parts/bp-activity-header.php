<?php
/**
 * The template for displaying activity header
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-header.php.
 *
 * @since   BuddyBoss 1.8.6
 * @version 1.8.6
 */

?>
<script type="text/html" id="tmpl-activity-header">
	<h3>
		<span class="activity-header-data">
			<# if ( data.privacy_modal === 'profile' ) {  #>
				<?php esc_html_e( 'Who can see your post?', 'buddyboss' ); ?>
			<# } else if ( data.privacy_modal === 'group' ) { #>
				<?php esc_html_e( 'Select a group', 'buddyboss' ); ?>
			<# } else { #>
				<# if ( data.edit_activity === true ) {  #>
					<?php esc_html_e( 'Edit post', 'buddyboss' ); ?>
				<# } else { #>
					<?php esc_html_e( 'Create a post', 'buddyboss' ); ?>
				<# } #>
			<# } #>
		<span>
	</h3>
	<a class="bb-model-close-button" href="#">
		<span class="bb-icon-l bb-icon-times"></span>
	</a>
</script>
