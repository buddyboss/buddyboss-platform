<script type="text/html" id="tmpl-activity-header">
    <h3>
        <span class="activity-header-data">
			<# if ( data.privacy_modal === 'profile' ) {  #>	
				<?php esc_html_e( 'Who can see your post?', 'buddyboss' ); ?>
			<# } else if ( data.privacy_modal === 'group' ) { #>
				<?php esc_html_e( 'Select a group', 'buddyboss' ); ?>
			<# } else if ( data.privacy_modal === 'edit_activity' ) { #>
				<?php esc_html_e( 'Edit activity', 'buddyboss' ); ?>
			<# } else { #>
				<?php esc_html_e( 'Create a post', 'buddyboss' ); ?>
			<# } #>
			<span>
    </h3>
    <a class="bb-model-close-button" href="#">
        <span class="bb-icon bb-icon-close"></span>
    </a>
</script>