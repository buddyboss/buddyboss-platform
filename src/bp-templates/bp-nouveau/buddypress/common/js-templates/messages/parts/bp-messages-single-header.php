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
		<a href="#" class="bp-back-to-thread-list"><span class="dashicons dashicons-arrow-left-alt2"></span></a>
		<# if ( undefined !== other_recipients ) { #>
		<dl class="thread-participants">
			<dt>
				<# if ( data.group_name.length > 1 && data.is_group_thread ) { #>
				<span class="participants-name">
					<# if ( data.is_deleted ) { #>
						{{data.group_name}}
					<# } else { #>
						<a href="{{data.group_link}}">{{data.group_name}}</a>
					<# } #>

				</span>
				<# } else { #>
					<# for ( i in other_recipients ) { #>
						<span class="participants-name">
                            <# if ( other_recipients[i].is_deleted ) { #>
								{{other_recipients[i].user_name}}
							<# } else { #><a href="{{other_recipients[i].user_link}}">{{other_recipients[i].user_name}}</a><# } #><# if ( i != other_recipients.length -1 || ( i == other_recipients.length -1 && include_you ) ) { #><?php _e(',', 'buddyboss'); ?><# } #>
		                </span>
					<# } #>

					<# if ( include_you ) { #>
						<span class="participants-name"><a href="{{current_user.user_link}}"><?php esc_html_e( 'You', 'buddyboss' ); ?></a></span>
					<# } #>

				<# } #>
			</dt>
			<dd>
				<span class="thread-date"><?php esc_html_e( 'Started', 'buddyboss' ); ?> {{data.started_date}}</span>
			</dd>
		</dl>
		<# } #>

		<# if ( data.group_name.length > 1 && data.is_group_thread ) { #>
			<div class="actions">
				<a type="button" class="message-action-delete bp-icons" href="javascript:void(0);" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'You need to leave the group to be removed from this conversation.', 'buddyboss' ); ?>">
					<i class="dashicons dashicons-info"></i>
					<span class="bp-screen-reader-text"><?php esc_html_e( 'You need to leave the group to be removed from this conversation.', 'buddyboss' ); ?></span>
				</a>
			</div>
		<# } else { #>
			<# if ( data.is_participated ) { #>
				<div class="actions">
					<button type="button" class="message-action-delete bp-icons" data-bp-action="delete" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Delete your messages', 'buddyboss' ); ?>">
						<i class="dashicons dashicons-trash"></i>
						<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete your messages', 'buddyboss' ); ?></span>
					</button>
				</div>
			<# } #>
		<# } #>
	</header>
</script>
