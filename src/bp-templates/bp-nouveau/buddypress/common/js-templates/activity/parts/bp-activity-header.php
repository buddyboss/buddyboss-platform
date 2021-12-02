<script type="text/html" id="tmpl-activity-header">
    <h4>
        <span class="activity-header-data">
			<# if ( data.privacy_modal ) {  #>	
				<?php esc_html_e( 'Who can see your post?', 'buddyboss' ); ?>
			<# } else { #>
				<?php esc_html_e( 'Create a post', 'buddyboss' ); ?>
			<# } #>
			<span>
    </h4>
    <a class="bb-model-close-button" href="#">
        <span class="bb-icon bb-icon-close"></span>
    </a>
</script>