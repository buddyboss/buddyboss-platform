<?php
/**
 * The template for displaying activity edit postin
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-post-form-topic-selector.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-post-form-topic-selector">

	<span class="bb-topic-selector-button">
		<?php esc_html_e( 'Select Topic', 'buddyboss' ); ?>
	</span>
	<div class="bb-topic-selector-list">
		<ul>
			<!-- TODO: Load topics from the server -->
			<li>
				<a href="#" data-topic-id="animation">
					<?php esc_html_e( 'Animation', 'buddyboss' ); ?>
				</a>
			</li>
			<li>
				<a href="#" data-topic-id="ux-design">
					<?php esc_html_e( 'UX Design', 'buddyboss' ); ?>
				</a>
			</li>
			<li>
				<a href="#" data-topic-id="web-design">
					<?php esc_html_e( 'Web Design', 'buddyboss' ); ?>
				</a>
			</li>
			<li>
				<a href="#" data-topic-id="graphics-design">
					<?php esc_html_e( 'Graphics Design', 'buddyboss' ); ?>
				</a>
			</li>
			<li>
				<a href="#" data-topic-id="motion">
					<?php esc_html_e( 'Motion', 'buddyboss' ); ?>
				</a>
			</li>			
		</ul>
	</div>
	<select disabled id="whats-new-topic-selector">
		<!-- TODO: Load topics from the server -->
		<option value="animation"><?php esc_html_e( 'Animation', 'buddyboss' ); ?></option>
		<option value="ux-design"><?php esc_html_e( 'UX Design', 'buddyboss' ); ?></option>
		<option value="web-design"><?php esc_html_e( 'Web Design', 'buddyboss' ); ?></option>
		<option value="graphics-design"><?php esc_html_e( 'Graphics Design', 'buddyboss' ); ?></option>
		<option value="motion"><?php esc_html_e( 'Motion', 'buddyboss' ); ?></option>
	</select>
</script>
