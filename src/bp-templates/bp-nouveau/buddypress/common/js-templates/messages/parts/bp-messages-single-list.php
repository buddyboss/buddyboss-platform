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

	<# if ( data.media ) { #>
	<# for ( i in data.media ) { #>
	<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3" data-id="" data-date-created="">

		<div class="bb-photo-thumb">
			<a class="bb-open-media-theatre bb-photo-cover-wrap"
			   data-id="{{data.media[i].id}}"
			   data-attachment-full="{{data.media[i].full}}"
			   href="#">
				<img src="{{data.media[i].thumbnail}}" alt="{{data.media[i].title}}"/>
			</a>
		</div>

	</li>
	<# } #>
	<# } #>

	<# if ( data.afterContent ) { #>
	<div class="bp-messages-hook after-message-content">{{{data.afterContent}}}</div>
	<# } #>

</script>
