<?php
/**
 * The template for displaying topic selector in activity post form.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-post-form-topic-selector.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-activity-post-form-topic-selector">

	<span class="bb-topic-selector-button">
		<?php esc_html_e( 'Select Topic', 'buddyboss' ); ?>
	</span>
	<div class="bb-topic-selector-list">
		<ul>
			<?php
			$topics = bb_topics_manager_instance()->bb_get_topics(
				array(
					'item_id'   => 0,
					'item_type' => 'activity',
				)
			);

			if ( ! empty( $topics['topics'] ) ) {
				foreach ( $topics['topics'] as $topic ) {
					echo '<li><a href="#" data-topic-id="' . esc_attr( $topic->topic_id ) . '" data-topic-rel-id="' . esc_attr( $topic->id ) . '">' . esc_html( $topic->name ) . '</a></li>';
				}
			}
			?>		
		</ul>
	</div>
</script>
