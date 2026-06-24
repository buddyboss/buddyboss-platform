<?php
/**
 * The template for displaying activity post case privacy
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-post-case-privacy.php.
 *
 * @since   BuddyBoss 1.8.6
 * @version 1.8.6
 */

?>
<script type="text/html" id="tmpl-activity-post-case-privacy">
	<div id="bp-activity-privacy-point" class="{{data.privacy}}" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Set by album privacy', 'buddyboss-platform' ); ?>">
		<span class="privacy-point-icon"></span>
		<span class="bp-activity-privacy-status">
			<# if ( data.privacy === 'public' ) {  #>
				<?php esc_html_e( 'Public', 'buddyboss-platform' ); ?>
			<# } else if ( data.privacy === 'loggedin' ) { #>
				<?php esc_html_e( 'All Members', 'buddyboss-platform' ); ?>
			<# } else if ( data.privacy === 'friends' ) { #>
				<?php esc_html_e( 'My Connections', 'buddyboss-platform' ); ?>
			<# } else if ( data.privacy === 'onlyme' ) { #>
				<?php esc_html_e( 'Only Me', 'buddyboss-platform' ); ?>
			<# } else { #>
				<?php esc_html_e( 'Group', 'buddyboss-platform' ); ?>
			<# } #>
		</span>
		<i class="bb-icon-f bb-icon-caret-down"></i>
	</div>
</script>
