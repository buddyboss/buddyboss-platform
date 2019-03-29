
<script type="text/html" id="tmpl-bp-messages-single-list">
	<div class="message-metadata">
		<# if ( data.beforeMeta ) { #>
			<div class="bp-messages-hook before-message-meta">{{{data.beforeMeta}}}</div>
		<# } #>

		<a href="{{data.sender_link}}" class="user-link">
			<img class="avatar" src="{{data.sender_avatar}}" alt="" />
			<# if ( data.sender_is_you ) { #>
				<strong><?php _e( 'You', 'buddyboss' ); ?></strong>
			<# } else { #>
				<strong>{{data.sender_name}}</strong>
			<# } #>
		</a>

		<time datetime="{{data.date.toISOString()}}" class="activity">{{data.display_date}}</time>

		<# if ( data.afterMeta ) { #>
			<div class="bp-messages-hook after-message-meta">{{{data.afterMeta}}}</div>
		<# } #>
	</div>

	<# if ( data.beforeContent ) { #>
		<div class="bp-messages-hook before-message-content">{{{data.beforeContent}}}</div>
	<# } #>

	<div class="message-content">{{{data.content}}}</div>

	<# if ( data.afterContent ) { #>
		<div class="bp-messages-hook after-message-content">{{{data.afterContent}}}</div>
	<# } #>

</script>
