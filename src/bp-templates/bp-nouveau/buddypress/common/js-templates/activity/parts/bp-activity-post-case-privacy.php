<script type="text/html" id="tmpl-activity-post-case-privacy">
	<div id="bp-activity-privacy-point" class="{{data.privacy}}">
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
		<i class="bb-icon-chevron-down"></i>
	</div>
</script>
