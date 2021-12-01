<script type="text/html" id="tmpl-activity-post-privacy-stage">
    <div id="bp-activity-privacy-stage" class="bp-activity-privacy-stage">
		<?php foreach( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>
            <div class="bp-activity-privacy-selector" data-key="<?php echo $key; ?>"><?php echo $privacy; ?></div>
	    <?php endforeach; ?>
	</div>
</script>
