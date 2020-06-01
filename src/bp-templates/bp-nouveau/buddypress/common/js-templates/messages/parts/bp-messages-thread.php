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

	<# if ( ! data.is_search ) { #>
	<a href="javascript:void(0);" data-bp-thread-id="{{data.id}}" data-bp-action="hide_thread" class="close-conversation"> <i class="dashicons dashicons-no-alt"></i> </a>
	<# } #>
	<a class="bp-message-link bp-message-link-{{data.id}}" href="../view/{{data.id}}/" data-thread-id="{{data.id}}">
		<div class="thread-avatar">

			<# if ( data.group_avatar && data.group_avatar.length > 1 && data.is_group_thread ) { #>
				<img class="avatar" src="{{data.group_avatar}}" alt="{{data.group_name}}" />
			<# } else { #>
				<# if ( other_recipients.length > 1 ) { #>
					<span class="recipients-count">{{other_recipients.length}}</span>
					<img class="avatar" src="{{data.sender_avatar}}" alt="{{data.sender_name}}" />
				<# } else { #>
					<# var recipient = _.first(other_recipients)? _.first(other_recipients) : current_user; #>
					<# if ( typeof( recipient ) != "undefined" && recipient !== null && recipient.avatar.length > 1 && recipient.user_name.length > 1 ) { #>
						<img class="avatar" src="{{recipient.avatar}}" alt="{{recipient.user_name}}" />
					<# } #>
				<# } #>
			<# } #>
		</div>

		<div class="thread-content" data-thread-id="{{data.id}}">
			<div class="thread-to">

				<# if ( data.group_name && data.group_name.length && data.is_group_thread ) { #>
					<span class="user-name">{{data.group_name}}</span>
				<# } else { #>

					<# for ( i in first_three ) { #>
						<span class="user-name">
							{{other_recipients[i].user_name}}<# if ( i != first_three.length - 1  || ( i == first_three.length -1 && data.toOthers ) ) { #><?php _e(',', 'buddyboss'); ?><# } #>
						</span>
					<# } #>

					<# if ( data.toOthers ) { #>
						<span class="num-name">{{data.toOthers}}</span>
					<# } #>

				<# } #>
			</div>

			<div class="thread-subject">
				<span class="last-message-sender">
				  <# if ( data.sender_is_you ) { #>
					<?php _e('You', 'buddyboss'); ?>:
				  <# } else if ( other_recipients && other_recipients.length ) { #>
					{{ data.sender_name }}:
				  <# } #>
				</span>
				{{{data.excerpt}}}
			</div>
		</div>

		<div class="thread-date">
			<time datetime="{{data.date.toISOString()}}">{{data.display_date}}</time>
		</div>
	</a>
</script>
