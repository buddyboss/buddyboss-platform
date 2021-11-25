<script type="text/html" id="tmpl-activity-post-form-privacy">
    <span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="up" data-bp-tooltip="Post in: Profile">
        <span class="privacy profile"><i class="bb-icon-globe" style="font-size:20px;"></i></span>
    </span>
    <div id="bp-activity-privacy" class="bp-activity-privacy" name="privacy" style="display:none;">
	    <?php foreach( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>
            <li data-value="<?php echo $key; ?>"><?php echo $privacy; ?></li>
	    <?php endforeach; ?>
    </div>
</script>
