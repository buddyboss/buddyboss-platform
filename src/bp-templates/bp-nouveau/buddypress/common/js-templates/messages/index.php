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

<div class="bp-messages-feedback"></div>
<div class="bp-messages-content"></div>

<script type="text/html" id="tmpl-bp-messages-feedback">
	<div class="bp-feedback {{data.type}}">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>{{{data.message}}}</p>
	</div>
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

	<label for="send-to-input"><?php esc_html_e( 'Send @Username', 'buddyboss' ); ?></label>
	<input type="text" name="send_to" class="send-to-input" id="send-to-input" value="<?php if ( isset( $_GET['r'] ) ) : ?>@<?php echo esc_textarea( $_GET['r'] ); ?> <?php endif; ?>"/>

	<div id="bp-message-content"></div>

	<?php bp_nouveau_messages_hook( 'after', 'compose_content' ); ?>

	<div class="submit">
		<input type="button" id="bp-messages-send" class="button bp-primary-action" value="<?php echo esc_attr_x( 'Send', 'button', 'buddyboss' ); ?>"/>
		<input type="button" id="bp-messages-reset" class="text-button small bp-secondary-action" value="<?php echo esc_attr_x( 'Reset', 'form reset button', 'buddyboss' ); ?>"/>
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
			<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Previous page', 'link', 'buddyboss' ); ?></span>
		</button>
	<# } #>

	<# if ( data.total_page !== data.page ) { #>
		<button id="bp-messages-next-page"class="button messages-button">
			<span class="dashicons dashicons-arrow-right"></span>
			<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Next page', 'link', 'buddyboss' ); ?></span>
		</button>
	<# } #>
</script>

<script type="text/html" id="tmpl-bp-messages-filters">
	<li class="user-messages-search" role="search" data-bp-search="{{data.box}}">
		<div class="bp-search messages-search">
			<form action="" method="get" id="user_messages_search_form" class="bp-messages-search-form" data-bp-search="messages">
				<label for="user_messages_search" class="bp-screen-reader-text">
					<?php _e( 'Search Messages', 'buddyboss' ); ?>
				</label>
				<input type="search" id="user_messages_search" placeholder="<?php echo esc_attr_x( 'Search', 'search placeholder text', 'buddyboss' ); ?>"/>
				<button type="submit" id="user_messages_search_submit">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Search', 'button', 'buddyboss' ); ?></span>
				</button>
			</form>
		</div>
	</li>
	<li class="user-messages-bulk-actions"></li>
</script>

<script type="text/html" id="tmpl-bp-bulk-actions">
	<input type="checkbox" id="user_messages_select_all" value="1"/>
	<label for="user_messages_select_all"><?php esc_html_e( 'Bulk Actions', 'buddyboss' ); ?></label>
	<div class="bulk-actions-wrap bp-hide">
		<div class="bulk-actions select-wrap">
			<label for="user-messages-bulk-actions" class="bp-screen-reader-text">
				<?php esc_html_e( 'Select bulk action', 'buddyboss' ); ?>
			</label>
			<select id="user-messages-bulk-actions">
				<# for ( i in data ) { #>
					<option value="{{data[i].value}}">{{data[i].label}}</option>
				<# } #>
			</select>
			<span class="select-arrow" aria-hidden="true"></span>
		</div>
		<button class="messages-button bulk-apply bp-tooltip" type="submit" data-bp-tooltip="<?php echo esc_attr_x( 'Apply', 'button', 'buddyboss' ); ?>">
			<span class="dashicons dashicons-yes" aria-hidden="true"></span>
			<span class="bp-screen-reader-text"><?php echo esc_html_x( 'Apply', 'button', 'buddyboss' ); ?></span>
		</button>
	</div>
</script>

<script type="text/html" id="tmpl-bp-messages-thread">
	<div class="thread-cb">
		<input class="message-check" type="checkbox" name="message_ids[]" id="bp-message-thread-{{data.id}}" value="{{data.id}}">
		<label for="bp-message-thread-{{data.id}}" class="bp-screen-reader-text"><?php esc_html_e( 'Select message:', 'buddyboss' ); ?> {{data.subject}}</label>
	</div>

	<div class="thread-avatar">
		<# if ( data.recipients.length > 1 ) { #>
			<img class="avatar" src="{{data.sender_avatar}}" alt="{{data.sender_name}}" />
		<# } else { #>
			<# var recipient = _.first(data.recipients); #>
			<img class="avatar" src="{{recipient.avatar}}" alt="{{recipient.user_name}}" />
		<# } #>
	</div>

	<div class="thread-content" data-thread-id="{{data.id}}">
		<div class="thread-to">
			<a class="subject" href="../view/{{data.id}}/">
				<# for ( i in _.first(data.recipients, 3) ) { #>
					<span class="user-name">{{data.recipients[i].user_name}}</span>
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
					<# } else { #>
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
	<header class="single-message-thread-header">
		<# if ( undefined !== data.recipients ) { #>
			<dl class="thread-participants">
				<dt>
					<# for ( i in data.recipients ) { #>
						<span><a href="{{data.recipients[i].user_link}}">{{data.recipients[i].user_name}}</a></span>
					<# } #>
				</dt>
				<dd>
					<span class="thread-date">Started {{data.started_date}}</span>
				</dd>
			</dl>
		<# } #>

		<div class="actions">
			<button type="button" class="message-action-delete bp-tooltip bp-icons" data-bp-action="delete" data-bp-tooltip="<?php esc_attr_e( 'Delete conversation.', 'buddyboss' ); ?>">
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete conversation.', 'buddyboss' ); ?></span>
			</button>
		</div>
	</header>
</script>

<script type="text/html" id="tmpl-bp-messages-single-list">
	<div class="message-metadata">
		<# if ( data.beforeMeta ) { #>
			<div class="bp-messages-hook before-message-meta">{{{data.beforeMeta}}}</div>
		<# } #>

		<a href="{{data.sender_link}}" class="user-link">
			<img class="avatar" src="{{data.sender_avatar}}" alt="" />
			<strong>{{data.sender_name}}</strong>
		</a>

		<time datetime="{{data.date.toISOString()}}" class="activity">{{data.display_date}}</time>

		<div class="actions">
			<# if ( undefined !== data.star_link ) { #>

				<button type="button" class="message-action-unstar bp-tooltip bp-icons <# if ( false === data.is_starred ) { #>bp-hide<# } #>" data-bp-star-link="{{data.star_link}}" data-bp-action="unstar" data-bp-tooltip="<?php esc_attr_e( 'Unstar Message', 'buddyboss' ); ?>">
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Unstar Message', 'buddyboss' ); ?></span>
				</button>

				<button type="button" class="message-action-star bp-tooltip bp-icons <# if ( false !== data.is_starred ) { #>bp-hide<# } #>" data-bp-star-link="{{data.star_link}}" data-bp-action="star" data-bp-tooltip="<?php esc_attr_e( 'Star Message', 'buddyboss' ); ?>">
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Star Message', 'buddyboss' ); ?></span>
				</button>

			<# } #>
		</div>

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

<script type="text/html" id="tmpl-bp-messages-single">
	<?php bp_nouveau_messages_hook( 'before', 'thread_content' ); ?>

	<div id="bp-message-thread-header" class="message-thread-header"></div>

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
					<input type="submit" name="send" value="<?php echo esc_attr_x( 'Send Reply', 'button', 'buddyboss' ); ?>" id="send_reply_button"/>
				</div>

			</div><!-- .message-content -->

		</div><!-- .message-box -->
	</form>

	<?php bp_nouveau_messages_hook( 'after', 'thread_reply' ); ?>

	<?php bp_nouveau_messages_hook( 'after', 'thread_content' ); ?>
</script>
