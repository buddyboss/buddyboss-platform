<script type="text/html" id="tmpl-activity-target-item">
	<# if ( data.selected ) { #>
	<input type="hidden" value="{{data.id}}">
	<# } #>

	<# if ( data.avatar_url ) { #>
	<img src="{{data.avatar_url}}" class="avatar {{data.object_type}}-{{data.id}}-avatar photo" alt="" />
	<# } #>

	<span class="bp-item-name">{{data.name}}</span>

	<# if ( data.selected ) { #>
	<button type="button" class="bp-remove-item dashicons dashicons-no" data-item_id="{{data.id}}">
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Remove item', 'buddyboss' ); ?></span>
	</button>
	<# } #>
</script>
