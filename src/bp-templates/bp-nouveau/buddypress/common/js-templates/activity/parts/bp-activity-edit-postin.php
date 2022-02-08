<?php
/**
 * The template for displaying activity edit postin
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-edit-postin.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-edit-postin">
	<div id="whats-new-post-in-box">
		<select disabled id="whats-new-post-in">
			<# if ( data.object !== 'user' ) { #>
			<option><?php esc_html_e( 'Post in: Group', 'buddyboss' ); ?></option>
			<# } #>
			<# if ( data.object === 'user' ) { #>
			<option><?php esc_html_e( 'Post in: Profile', 'buddyboss' ); ?></option>
			<# } #>
		</select>

		<# if ( data.group_name ) { #>
			<div id="whats-new-post-in-box-items">
				<input disabled type="text" id="activity-autocomplete" value="{{{data.group_name}}}" />
			</div>
		<# } #>
	</div>
</script>
