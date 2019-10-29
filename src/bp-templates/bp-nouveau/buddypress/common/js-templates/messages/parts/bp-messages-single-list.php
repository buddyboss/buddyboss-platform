<script type="text/html" id="tmpl-bp-messages-single-list">

	<# if ( data.date_separator && '' !== data.date_separator ) { #>
		<hr class="hr-text" data-content="{{{data.date_separator}}}"></hr>
	<# } else { #>
	<div class="bp-single-message-wrap">
		<div class="bp-avatar-wrap">
			<a href="{{data.sender_link}}" class="bp-user-avatar">
				<img class="avatar" src="{{data.sender_avatar}}" alt="" />
			</a>
		</div>

		<div class="bp-single-message-content">
			<div class="message-metadata">
				<# if ( data.beforeMeta ) { #>
				<div class="bp-messages-hook before-message-meta">{{{data.beforeMeta}}}</div>
				<# } #>

				<a href="{{data.sender_link}}" class="bp-user-link">
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

			<div class="bp-message-content-wrap">{{{data.content}}}</div>

			<# if ( data.media ) { #>
			<div class="bb-activity-media-wrap bb-media-length-{{data.media.length}}">
				<# for ( i in data.media ) { #>
				<div class="bb-activity-media-elem">
					<a class="bb-open-media-theatre bb-photo-cover-wrap"
					   data-id="{{data.media[i].id}}"
					   data-attachment-full="{{data.media[i].full}}"
					   href="#">
						<img src="{{data.media[i].thumbnail}}" alt="{{data.media[i].title}}"/>
					</a>
				</div>
				<# } #>
			</div>
			<# } #>

            <# if ( data.gif ) { #>
            <div class="activity-attached-gif-container">
                <div class="gif-image-container">
                    <div class="gif-player">
                        <video preload="auto" playsinline poster="{{data.gif.preview_url}}" loop muted playsinline>
                            <source src="{{data.gif.video_url}}" type="video/mp4">
                        </video>
                        <a href="#" class="gif-play-button">
                            <span class="dashicons dashicons-video-alt3"></span>
                        </a>
                        <span class="gif-icon"></span>
                    </div>
                </div>
            </div>
            <# } #>

			<# if ( data.afterContent ) { #>
			<div class="bp-messages-hook after-message-content">{{{data.afterContent}}}</div>
			<# } #>

		</div>
	</div>
	<# } #>
</script>
