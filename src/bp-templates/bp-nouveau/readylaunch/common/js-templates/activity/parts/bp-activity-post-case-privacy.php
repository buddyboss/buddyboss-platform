<?php
/**
 * ReadyLaunch - The template for displaying activity post case privacy.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-post-case-privacy">
	<div id="bp-activity-privacy-point" class="{{data.privacy}}" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Set by album privacy', 'buddyboss' ); ?>">
		<span class="privacy-point-icon"></span>
		<span class="bp-activity-privacy-status">
			<# if ( data.privacy === 'public' ) {  #>
				<?php esc_html_e( 'Public', 'buddyboss' ); ?>
			<# } else if ( data.privacy === 'loggedin' ) { #>
				<?php esc_html_e( 'All Members', 'buddyboss' ); ?>
			<# } else if ( data.privacy === 'friends' ) { #>
				<?php esc_html_e( 'My Connections', 'buddyboss' ); ?>
			<# } else if ( data.privacy === 'onlyme' ) { #>
				<?php esc_html_e( 'Only Me', 'buddyboss' ); ?>
			<# } else { #>
				<?php esc_html_e( 'Group', 'buddyboss' ); ?>
			<# } #>
		</span>
		<i class="bb-icon-f bb-icon-caret-down"></i>
	</div>
</script>
