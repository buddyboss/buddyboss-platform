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
							<# } else { #><a href="{{other_recipients[i].user_link}}">{{other_recipients[i].user_name}}</a><# } #><# if ( i != other_recipients.length -1 || ( i == other_recipients.length -1 ) && data.toOthers ) { #><?php _e(',', 'buddyboss'); ?><# } #>
		                </span>
					<# } #>

				<# } #>
			</dt>
			<dd>
				<span class="thread-date"><?php esc_html_e( 'Started', 'buddyboss' ); ?> {{data.started_date}}</span>
			</dd>
		</dl>
		<# } #>
        <div class="actions">
	        <?php
	        if ( bp_current_user_can( 'bp_moderate' ) ) {
		        ?>
		        <div class="message_actions">
			        <a href="#" class="message_action__anchor">
				        <i class="bb-icon-menu-dots-v"></i>
			        </a>
			        <div class="message_action__list">
				        <ul>
				        	<li class="unread"><a data-bp-action="unread" href="#"><?php esc_html_e( 'Mark unread', 'buddyboss' ); ?></a></li>
				        	<li class="hide_thread"><a data-bp-action="hide_thread" href="#"><?php esc_html_e( 'Hide conversation', 'buddyboss' ); ?></a></li>
					        <li class="delete_messages"><a data-bp-action="delete" href="#"><?php esc_html_e( 'Delete your messages', 'buddyboss' ); ?></a></li>
					        <li class="delete_thread"><a data-bp-action="delete_thread" href="#"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></a></li>
				        </ul>
			        </div>
		        </div>
		        <?php
	        } else {

		        $old_user = false;
		        if ( class_exists( 'BP_Core_Members_Switching' ) ) {
			        $old_user = BP_Core_Members_Switching::get_old_user();
		        }
	        	?>
				<div class="message_actions">
			        <a href="#" class="message_action__anchor">
				        <i class="bb-icon-menu-dots-v"></i>
			        </a>
			        <div class="message_action__list">
				        <ul>
				        	<li class="unread"><a data-bp-action="unread" href="#"><?php esc_html_e( 'Mark unread', 'buddyboss' ); ?></a></li>
				        	<li class="hide_thread"><a data-bp-action="hide_thread" href="#"><?php esc_html_e( 'Hide conversation', 'buddyboss' ); ?></a></li>
					        <li class="delete_messages" data-bp-action="delete"><a data-bp-action="delete" href="#"><?php esc_html_e( 'Delete your messages', 'buddyboss' ); ?></a></li>
					        <?php
					        if ( ! empty( $old_user ) ) {
					        	?>
						        <li class="delete_thread"><a data-bp-action="delete_thread" href="#"><?php esc_html_e( 'Delete conversation', 'buddyboss' ); ?></a></li>
					            <?php
					        }
					        ?>
				        </ul>
			        </div>
		        </div>
	            <?php
	        }
	        ?>

        </div>
	</header>
</script>
