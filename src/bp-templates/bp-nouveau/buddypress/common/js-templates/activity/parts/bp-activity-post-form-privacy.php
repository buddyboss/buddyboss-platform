<script type="text/html" id="tmpl-activity-post-form-privacy">
    <select id="bp-activity-privacy" class="bp-activity-privacy" name="privacy">
	    <?php foreach( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>
            <option value="<?php echo $key; ?>"><?php echo $privacy; ?></option>
	    <?php endforeach; ?>
    </select>
</script>
