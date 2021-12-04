<script type="text/html" id="tmpl-activity-post-form-privacy">
	<div class="bp-activity-privacy__list">
		<?php foreach( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>
			<div class="bp-activity-privacy__item">
				<label for="<?php echo $key; ?>" class="bp-activity-privacy__label"><?php echo $privacy; ?></label>
				<input type="radio" id="<?php echo $key; ?>" class="bp-activity-privacy__input" name="privacy" value="<?php echo $key; ?>" data-title="<?php echo $privacy; ?>" <?php if( $key == 'public' ) echo 'checked'; ?>>	
			</div>
		<?php endforeach; ?>
	</div>
</script>
