<?php
/**
 * The template for displaying activity reactions in popup modal.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-reactions-popup.php
 *
 * @since   BuddyBoss 2.5.20
 * @package BuddyBoss\Core
 */

?>
<script type="text/html" id="tmpl-activity-reacted-popup-heading">
	<h4>
		<# if ( 0 !== data.popup_heading.length ) { #>
			{{ data.popup_heading }}
		<# } #>
		<# if ( 0 === data.reacted_tabs.length ) { #>
			{{ ' (' + data.popup_heading_count + ')' }}
		<# } #>
	</h4>
</script>

<script type="text/html" id="tmpl-activity-reacted-popup-tab">
	<#
	if ( data.reaction_mode && 'emotions' === data.reaction_mode && 1 < data.reacted_tabs.length ) { #>
		<ul>
			<#
			var isFirstItem = true;
			jQuery.each( data.reacted_tabs, function( key, reaction ) {
			#>
			<li>
				<a href="javascript:void(0);" class="{{ isFirstItem ? 'active' : '' }}" data-tab="activity-state_{{key}}" reaction-id="{{ reaction.id ? reaction.id : '0' }}">
					<# if ( 'all' === reaction.type  ) { #>
						{{ reaction.icon_text }}
					<# } else if ( 'bb-icons' === reaction.type  ) { #>
						<i class="bb-icon-{{ reaction.icon }}" style="font-weight:200;color:{{ reaction.icon_color }};"></i>
						<span>{{ reaction.total_count }}</span>
					<# } else if ( 0 < reaction.icon_path.length ) { #>
						<img src="{{ reaction.icon_path }}" class="{{ reaction.type }}" alt="{{ reaction.icon_text }}" />
						<span>{{ reaction.total_count }}</span>
					<# } else { #>
						<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
						<span>{{ reaction.total_count }}</span>
					<# } #>
				</a>
			</li>
			<#
				isFirstItem = false;
			});
			#>
		</ul>
	<# } #>
</script>

<script type="text/html" id="tmpl-activity-reacted-popup-tab-content">
	<#
	var users            = data.reacted_users;
	var data_reaction_id = data.reaction_id ? data.reaction_id : 0;

	if ( 0 === data.reacted_tabs.length ) {
		var total_pages = 'undefined' !== typeof data.total_pages ? data.total_pages : 1;
		#>
		<div class="activity-state-popup_tab_item activity-state_ active" data-reaction-id="{{ data.reaction_id }}" data-paged="1" data-total-pages="{{ total_pages }}">

			<# if( users.length > 0 ) { #>
				<ul class="activity-state_users">
				<#
					jQuery.each(
						users,
						function( key, user )
						{
						#>
							<li class="activity-state_user">
								<div class="activity-state_user__avatar">
									<# if ( '' !== user.profile_url ) { #>
										<a href="{{ user.profile_url }}">
									<# } #>
										<img class="avatar" src="{{ user.avatar }}" alt="{{ user.name }}" />
										<div class="activity-state_user__reaction">
											<# if ( 'bb-icons' === user.reaction.type ) { #>
												<i class="bb-icon-{{ user.reaction.icon }}" style="font-weight:200;color:{{ user.reaction.icon_color }};"></i>
											<# } else if ( 0 < user.reaction.icon_path.length ) { #>
												<img src="{{ user.reaction.icon_path }}" class="{{ user.reaction.type }}" alt="{{ user.reaction.icon_text }}" />
											<# } else { #>
												<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
											<# } #>
										</div>
									<# if ( '' !== user.profile_url ) { #>
										</a>
									<# } #>
								</div>
								<div class="activity-state_user__name">
									<# if ( '' !== user.profile_url ) { #>
										<a href="{{ user.profile_url }}">{{ user.name }}</a>
									<# } else { #>
										{{ user.name }}
									<# } #>
								</div>
								<# if ( '' !== user.profile_url && user.member_type && user.member_type.label ) { #>
									<div class="activity-state_user__role" style="color:{{user.member_type.color.text}}; background-color:{{user.member_type.color.background}};">
										{{ user.member_type.label }}
									</div>
								<# } #>
							</li>
						<#
						}
					);
				#>
				</ul>
			<# } else { #>
				<ul class="activity-state_users"></ul>
			<# } #>
		</div>
		<#
	} else {
		var isFirstItem = true;

		jQuery.each(
			data.reacted_tabs,
			function( key, reaction )
			{
				var current_reaction_id = reaction.id ? reaction.id : 0;
				#>
				<div class="activity-state-popup_tab_item activity-state_{{key}} {{isFirstItem ? 'active' : ''}}" data-reaction-id="{{reaction.id ? reaction.id : '0'}}" data-paged="{{reaction.paged}}" data-total-pages="{{reaction.total_pages}}">
					<#
					if ( data_reaction_id === current_reaction_id ) {
						if ( 0 < users.length ) {
							#>
							<ul class="activity-state_users">
								<#
								jQuery.each(
									users,
									function( key, user )
									{
										#>
										<li class="activity-state_user">
											<div class="activity-state_user__avatar">
												<# if ( '' !== user.profile_url ) { #>
													<a href="{{ user.profile_url }}">
												<# } #>
													<img class="avatar" src="{{ user.avatar }}" alt="{{ user.name }}" />
													<div class="activity-state_user__reaction">
														<# if ( user.reaction.type === 'bb-icons' ) { #>
														<i class="bb-icon-{{ user.reaction.icon }}" style="font-weight:200;color:{{ user.reaction.icon_color }};"></i>
														<# } else if ( user.reaction.icon_path !== '' ) { #>
														<img src="{{ user.reaction.icon_path }}" class="{{ user.reaction.type }}" alt="{{ user.reaction.icon_text }}" />
														<# } else { #>
														<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
														<# } #>
													</div>
												<# if ( '' !== user.profile_url ) { #>
													</a>
												<# } #>
											</div>
											<div class="activity-state_user__name">
												<# if ( '' !== user.profile_url ) { #>
													<a href="{{ user.profile_url }}">{{ user.name }}</a>
												<# } else { #>
													{{ user.name }}
												<# } #>
											</div>
											<# if ( '' !== user.profile_url && user.member_type && user.member_type.label ) { #>
											<div class="activity-state_user__role" style="color:{{user.member_type.color.text}}; background-color:{{user.member_type.color.background}};">
												{{ user.member_type.label }}
											</div>
											<# } #>
										</li>
										<#
									}
								);
								#>
							</ul>
						<# } else { #>
							<div class="activity-state_no_users">
								{{ data.no_users }}
							</div>
						<# }
					} else { #>
						<ul class="activity-state_users"></ul>
					<# } #>
				</div>
				<#
				isFirstItem = false;
			}
		);
	}
	#>
</script>

<script type="text/html" id="tmpl-activity-reacted-item">
	<div class="activity-state_user__avatar">
		<# if ( '' !== data.profile_url ) { #>
			<a href="{{ data.profile_url }}">
		<# } #>
			<img class="avatar" src="{{ data.avatar }}" alt="{{ data.name }}" />
			<div class="activity-state_user__reaction">
				<# if ( 'bb-icons' === data.reaction.type ) { #>
					<i class="bb-icon-{{ data.reaction.icon }}" style="font-weight:200;color:{{ data.reaction.icon_color }};"></i>
				<# } else if ( 0 < data.reaction.icon_path.length ) { #>
					<img src="{{ data.reaction.icon_path }}" class="{{ data.reaction.type }}" alt="{{ data.reaction.icon_text }}" />
				<# } else { #>
					<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
				<# } #>
			</div>
		<# if ( '' !== data.profile_url ) { #>
			</a>
		<# } #>
	</div>
	<div class="activity-state_user__name">
		<# if ( '' !== data.profile_url ) { #>
			<a href="{{ data.profile_url }}">{{ data.name }}</a>
		<# } else { #>
			{{ data.name }}
		<# } #>
	</div>

	<# if ( '' !== data.profile_url && data.member_type && data.member_type.label ) { #>
		<div class="activity-state_user__role" style="color:{{data.member_type.color.text}}; background-color:{{data.member_type.color.background}};">
			{{ data.member_type.label }}
		</div>
	<# } #>
</script>

<script type="text/html" id="tmpl-activity-reacted-no-data">
	<#
	if ( 'undefined' !== typeof data.message && 0 < data.message.length ) {
		var error_type = ( 'undefined' !== typeof data.type && 0 < data.type.length ) ? data.type : 'error';
		#>
		<aside class="bp-feedback bp-messages bp-template-notice {{ error_type }}">
			<span class="bp-icon" aria-hidden="true"></span>
			<p>
				{{ data.message }}
			</p>
		</aside>
	<# } #>
</script>
