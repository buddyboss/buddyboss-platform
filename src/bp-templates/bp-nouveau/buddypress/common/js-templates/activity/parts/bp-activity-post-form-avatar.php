<script type="text/html" id="tmpl-activity-post-form-avatar">
	<# if ( data.display_avatar ) {  #>
	<div class="activity-post-construct">
        <a class="activity-post-avatar" href="{{data.user_domain}}"><img src="{{{data.avatar_url}}}" class="avatar user-{{data.user_id}}-avatar avatar-{{data.avatar_width}} photo" width="{{data.avatar_width}}" height="{{data.avatar_width}}" alt="{{data.avatar_alt}}" /></a>
        <span class="activity-post-target">
            <a class="activity-post-user-name" href="{{data.user_domain}}"><span class="user-name">{{data.user_display_name}}</span></a>
			<div id="bp-activity-privacy-point">
				<i class="bb-icon-globe"></i>
				<span class="bp-activity-privacy-status {{data.privacy}}"><?php esc_html_e( 'Public', 'buddyboss' ); ?></span>
				<i class="bb-icon-angle-down"></i>
			</div>
        </span>
    </div>
	<# } #>
</script>
