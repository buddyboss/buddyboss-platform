<script type="text/html" id="tmpl-activity-post-privacy-stage">
    <div id="bp-activity-privacy-stage" class="bp-activity-privacy-stage">
		<div class="privacy-stage-list">
			<?php foreach( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>
				<div class="bp-activity-privacy-selector" data-key="<?php echo $key; ?>"><?php echo $privacy; ?></div>
			<?php endforeach; ?>
		</div>
		<div class="privacy-status-form-footer">
			<div class="privacy-status-actions">
				<input type="button" id="privacy-status-back" class="text-button small" value="Back">
				<input type="submit" id="privacy-status-submit" class="button" name="privacy-status-submit" value="Save">
			</div>
		</div>
	</div>
</script>
