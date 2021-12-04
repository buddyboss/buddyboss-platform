<script type="text/html" id="tmpl-activity-post-form-privacy">
	<div class="bp-activity-privacy__list">
		<?php foreach( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>
			
			<label for="<?php echo $key; ?>" class="bp-activity-privacy__label">
				<div class="privacy-tag-wrapper">
					<span class="privacy-figure privacy-figure--<?php echo $key; ?>"></span>
					<div class="privacy-tag">
						<div class="privacy-label"><?php echo $privacy; ?></div>
						<span class="privacy-sub-label">Proin sapien ipsum porta</span>
					</div>
				</div>
				<span class="privacy-radio"><input type="radio" id="<?php echo $key; ?>" class="bp-activity-privacy__input" name="privacy" value="<?php echo $key; ?>" data-title="<?php echo $privacy; ?>" <?php if( $key == 'public' ) echo 'checked'; ?>></span>
			</label>
		<?php endforeach; ?>
	</div>
</script>
