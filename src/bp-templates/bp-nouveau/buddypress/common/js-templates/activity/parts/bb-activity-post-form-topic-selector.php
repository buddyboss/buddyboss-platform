<?php
/**
 * The template for displaying topic selector in activity post form.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-post-form-topic-selector.php.
 *
 * @since   BuddyBoss 2.8.80
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-activity-post-form-topic-selector">

	<# 
	if ( data.topics && data.topics.topic_lists && data.topics.topic_lists.length > 0 ) { #>
		<span class="bb-topic-selector-button" data-select-topic-text="<?php esc_html_e( 'Select Topic', 'buddyboss' ); ?>">
			<#
			var topicId = parseInt( data.topics.topic_id );
			if ( 0 !== topicId && data.topics.topic_name ) { #>
				{{ data.topics.topic_name }}
			<# } else { #>
				<?php esc_html_e( 'Select Topic', 'buddyboss' ); ?>
			<# } #>
		</span>
		<div class="bb-topic-selector-list">
			<ul>
				<?php
				if ( ! bb_is_activity_topic_required() ) {
				?>
					<li>
						<a href="#" data-topic-id="">
							<?php esc_html_e( 'None', 'buddyboss' ); ?>
						</a>
					</li>
				<?php
				}
				?>
				<# _.each( data.topics.topic_lists, function( topic ) { #>
					<li>
						<a href="#" 
						data-topic-id="{{ topic.topic_id }}" 
						<# if ( data.topics.topic_id && parseInt( data.topics.topic_id ) === parseInt( topic.topic_id ) ) { #>class="selected"<# } #>
						>
							{{ topic.name }}
						</a>
					</li>
				<# }); #>
			</ul>
		</div>
	<# } #>
</script>
