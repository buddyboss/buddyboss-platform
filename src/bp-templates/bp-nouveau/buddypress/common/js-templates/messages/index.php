<?php
/**
 * BP Nouveau Messages main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with user's private messages.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>
<div class="subnav-filters filters user-subnav bp-messages-filters" id="subsubnav"></div>

<input type="hidden" id="thread-id" value="" />
<div class="bp-messages-feedback"></div>
<div class="bp-messages-threads-list"></div>
<div class="bp-messages-content"></div>

<?php if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) {
	bp_get_template_part( 'media/theatre' );
}  ?>

<script type="text/html" id="tmpl-bp-messages-feedback">
	<div class="bp-feedback {{data.type}}">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>{{{data.message}}}</p>
	</div>
</script>

<script type="text/html" id="tmpl-whats-new-messages-toolbar">
	<?php if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ): ?>
        <div class="post-elements-buttons-item post-media">
            <a href="#" id="messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip="<?php _e('Attach a photo', 'buddyboss'); ?>">
                <span class="dashicons dashicons-admin-media"></span>
            </a>
        </div>
	<?php endif; ?>
</script>

<script type="text/html" id="tmpl-messages-media">
    <div class="dropzone closed" id="messages-post-media-uploader"></div>
</script>


<?php
/**
 * This view is used to inject hooks buffer
 */
?>
<script type="text/html" id="tmpl-bp-messages-hook">
	{{{data.extraContent}}}
</script>


<script type="text/html" id="tmpl-bp-messages-form">
	<?php bp_nouveau_messages_hook( 'before', 'compose_content' ); ?>

	<label for="send-to-input"><?php esc_html_e( 'New Message', 'buddyboss' ); ?></label>
	<select
		name="send_to[]"
		class="send-to-input"
		id="send-to-input"
		placeholder="<?php esc_html_e( 'Type the names of one or more people', 'buddyboss' ); ?>"
		autocomplete="off"
		multiple="multiple"
		style="width: 100%"
	>
		<?php if ( ! empty( $_GET['r'] ) ): ?>
			<option value="@<?php echo esc_attr( $_GET['r'] ); ?>" selected>
				<?php echo bp_core_get_user_displayname( get_user_by( 'login', $_GET['r'] )->ID ); ?>
			</option>
		<?php endif; ?>
	</select>

	<div id="bp-message-content"></div>

	<?php bp_nouveau_messages_hook( 'after', 'compose_content' ); ?>

	<div class="submit">
		<input type="button" id="bp-messages-send" class="button bp-primary-action" value="<?php esc_attr_e( 'Send', 'buddyboss' ); ?>"/>
		<input type="button" id="bp-messages-reset" class="text-button small bp-secondary-action" value="<?php esc_attr_e( 'Reset', 'buddyboss' ); ?>"/>
	</div>
</script>


<script type="text/html" id="tmpl-bp-messages-editor">
	<?php
	// Add a temporary filter on editor buttons
	add_filter( 'mce_buttons', 'bp_nouveau_messages_mce_buttons', 10, 1 );

	wp_editor(
		'',
		'message_content',
		array(
			'textarea_name' => 'message_content',
			'teeny'         => false,
			'media_buttons' => false,
			'dfw'           => false,
			'tinymce'       => true,
			'quicktags'     => false,
			'tabindex'      => '3',
			'textarea_rows' => 5,
		)
	);

	// Remove the temporary filter on editor buttons
	remove_filter( 'mce_buttons', 'bp_nouveau_messages_mce_buttons', 10, 1 );
	?>
</script>


<script type="text/html" id="tmpl-bp-messages-paginate">
	<# if ( 1 !== data.page ) { #>
		<button id="bp-messages-prev-page"class="button messages-button">
			<span class="dashicons dashicons-arrow-left"></span>
			<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span>
		</button>
	<# } #>

	<# if ( data.total_page !== data.page ) { #>
		<button id="bp-messages-next-page"class="button messages-button">
			<span class="dashicons dashicons-arrow-right"></span>
			<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span>
		</button>
	<# } #>
</script>


<script type="text/html" id="tmpl-bp-messages-filters">
	<li class="user-messages-search" role="search" data-bp-search="{{data.box}}">
		<div class="bp-search messages-search">
			<?php bp_nouveau_message_search_form(); ?>
		</div>
	</li>
	<li class="user-messages-bulk-actions"></li>
</script>


<script type="text/html" id="tmpl-bp-messages-thread">
	<#
		var other_recipients = _.reject(data.recipients, function(item) {
			return item.is_you;
		});

		var current_user = _.find(data.recipients, function(item) {
			return item.is_you;
		});

		var include_you = other_recipients.length >= 2;
		var first_three = _.first(other_recipients, 3);

		if (first_three.length == 0) {
			include_you = true;
		}
	#>

	<div class="thread-avatar">
		<# if ( other_recipients.length > 1 ) { #>
			<img class="avatar" src="{{data.sender_avatar}}" alt="{{data.sender_name}}" />
		<# } else { #>
			<# var recipient = _.first(other_recipients)? _.first(other_recipients) : current_user; #>
			<img class="avatar" src="{{recipient.avatar}}" alt="{{recipient.user_name}}" />
		<# } #>
	</div>

	<div class="thread-content" data-thread-id="{{data.id}}">
		<div class="thread-to">
			<a class="subject" href="../view/{{data.id}}/">
				<# for ( i in first_three ) { #>
					<span class="user-name">
						{{other_recipients[i].user_name}}<# if ( i != first_three.length - 1  || ( i == first_three.length -1 && include_you ) ) { #><?php _e(',', 'buddyboss'); ?><# } #>
					</span>
				<# } #>

				<# if ( include_you ) { #>
					<span class="user-name"><?php _e('You', 'buddyboss'); ?><# if ( data.toOthers ) { #><?php _e(',', 'buddyboss'); ?><# } #></span>
				<# } #>

				<# if ( data.toOthers ) { #>
					<span class="num-name">{{data.toOthers}}</span>
				<# } #>
			</a>
		</div>

		<div class="thread-subject">
			<a class="subject" href="../view/{{data.id}}/">
				<span class="last-message-sender">
					<# if ( data.sender_is_you ) { #>
						<?php _e('You', 'buddyboss'); ?>:
					<# } else if ( other_recipients.length > 1 ) { #>
						{{ data.sender_name }}:
					<# } #>
				</span>

				{{data.excerpt}}
			</a>
		</div>
	</div>
	<div class="thread-date">
		<time datetime="{{data.date.toISOString()}}">{{data.display_date}}</time>
	</div>
</script>


<script type="text/html" id="tmpl-bp-messages-single-header">
	<#
		var other_recipients = _.reject(data.recipients, function(item) {
			return item.is_you;
		});

		var current_user = _.find(data.recipients, function(item) {
			return item.is_you == true;
		});

		var include_you = other_recipients.length >= 2;

		if (other_recipients.length == 0) {
			include_you = true;
		}
	#>

	<header class="single-message-thread-header">
		<# if ( undefined !== other_recipients ) { #>
			<dl class="thread-participants">
				<dt>
					<# for ( i in other_recipients ) { #>
						<span class="participants-name">
							<a href="{{other_recipients[i].user_link}}">{{other_recipients[i].user_name}}</a><# if ( i != other_recipients.length -1 || ( i == other_recipients.length -1 && include_you ) ) { #><?php _e(',', 'buddyboss'); ?><# } #>
						</span>
					<# } #>

					<# if ( include_you ) { #>
						<span class="participants-name"><a href="{{current_user.user_link}}">You</a></span>
					<# } #>
				</dt>
				<dd>
					<span class="thread-date">Started {{data.started_date}}</span>
				</dd>
			</dl>
		<# } #>

		<div class="actions">
			<button type="button" class="message-action-delete bp-icons" data-bp-action="delete" data-bp-tooltip="<?php esc_attr_e( 'Delete conversation', 'buddyboss' ); ?>">
				<i class="dashicons dashicons-trash"></i>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></span>
			</button>
		</div>
	</header>
</script>


<script type="text/html" id="tmpl-bp-messages-single-load-more">
	<button type="button" class="button" style="display: none;"><?php _e( 'Load previous messages', 'buddyboss' ); ?></button>
</script>


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


<script type="text/html" id="tmpl-bp-messages-single">
	<?php bp_nouveau_messages_hook( 'before', 'thread_content' ); ?>

	<div id="bp-message-thread-header" class="message-thread-header"></div>
	<div id="bp-message-load-more"></div>

	<?php bp_nouveau_messages_hook( 'before', 'thread_list' ); ?>

	<ul id="bp-message-thread-list"></ul>

	<?php bp_nouveau_messages_hook( 'after', 'thread_list' ); ?>

	<?php bp_nouveau_messages_hook( 'before', 'thread_reply' ); ?>

	<form id="send-reply" class="standard-form send-reply">
		<div class="message-box">
			<div class="message-metadata">

				<?php bp_nouveau_messages_hook( 'before', 'reply_meta' ); ?>

				<div class="avatar-box">
					<strong><?php esc_html_e( 'Send a Reply', 'buddyboss' ); ?></strong>
				</div>

				<?php bp_nouveau_messages_hook( 'after', 'reply_meta' ); ?>

			</div><!-- .message-metadata -->

			<div class="message-content">

				<?php bp_nouveau_messages_hook( 'before', 'reply_box' ); ?>

				<label for="message_content" class="bp-screen-reader-text"><?php _e( 'Reply to Message', 'buddyboss' ); ?></label>
				<div id="bp-message-content"></div>

				<?php bp_nouveau_messages_hook( 'after', 'reply_box' ); ?>

				<div class="submit">
					<input type="submit" name="send" value="<?php esc_attr_e( 'Send Reply', 'buddyboss' ); ?>" id="send_reply_button"/>
				</div>

			</div><!-- .message-content -->

		</div><!-- .message-box -->
	</form>

	<?php bp_nouveau_messages_hook( 'after', 'thread_reply' ); ?>

	<?php bp_nouveau_messages_hook( 'after', 'thread_content' ); ?>
</script>