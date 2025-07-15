<?php
/**
 * ReadyLaunch - Activity Edit Post JS Templates.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<script type="text/html" id="tmpl-activity-edit-postin">
	<div id="bb-rl-whats-new-post-in-box">
		<select disabled id="bb-rl-whats-new-post-in">
			<# if ( data.object !== 'user' ) { #>
			<option><?php esc_html_e( 'Post in: Group', 'buddyboss' ); ?></option>
			<# } #>
			<# if ( data.object === 'user' ) { #>
			<option><?php esc_html_e( 'Post in: Profile', 'buddyboss' ); ?></option>
			<# } #>
		</select>

		<# if ( data.group_name ) { #>
			<div id="bb-rl-whats-new-post-in-box-items">
				<input disabled type="text" id="activity-autocomplete" value="{{{data.group_name}}}" />
			</div>
		<# } #>
	</div>
</script>
