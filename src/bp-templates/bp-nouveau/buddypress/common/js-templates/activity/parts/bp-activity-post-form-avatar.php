<script type="text/html" id="tmpl-activity-post-form-avatar">
	<# if ( data.display_avatar ) {  #>
	<a class="activity-post-avatar" href="{{data.user_domain}}">
		<img src="{{{data.avatar_url}}}" class="avatar user-{{data.user_id}}-avatar avatar-{{data.avatar_width}} photo" width="{{data.avatar_width}}" height="{{data.avatar_width}}" alt="{{data.avatar_alt}}" />
		<span class="user-name">{{data.user_display_name}}</span>
	</a>
	<# } #>
</script>
