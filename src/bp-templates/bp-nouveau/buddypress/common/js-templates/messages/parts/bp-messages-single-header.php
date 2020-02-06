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
				<# for ( i in other_recipients ) { #>
				<span class="participants-name">
              <a href="{{other_recipients[i].user_link}}">{{other_recipients[i].user_name}}</a><# if ( i != other_recipients.length -1 || ( i == other_recipients.length -1 && include_you ) ) { #><?php _e(',', 'buddyboss'); ?><# } #>
            </span>
				<# } #>

				<# if ( include_you ) { #>
				<span class="participants-name"><a href="{{current_user.user_link}}"><?php esc_html_e( 'You', 'buddyboss' ); ?></a></span>
				<# } #>
			</dt>
			<dd>
				<span class="thread-date"><?php esc_html_e( 'Started', 'buddyboss' ); ?> {{data.started_date}}</span>
			</dd>
		</dl>
		<# } #>

		<div class="actions">
			<button type="button" class="message-action-delete bp-icons" data-bp-action="delete" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Delete conversation', 'buddyboss' ); ?>">
				<i class="bb-icon-trash"></i>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></span>
			</button>
		</div>
	</header>
</script>