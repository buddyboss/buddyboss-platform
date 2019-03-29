
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
