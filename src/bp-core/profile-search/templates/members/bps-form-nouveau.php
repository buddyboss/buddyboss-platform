<?php

/*
 * BP Profile Search - form template 'bps-form-nouveau'
 *
 * See http://dontdream.it/bp-profile-search/form-templates/ if you wish to modify this template or develop a new one.
 *
 * A new or modified template should be moved to the 'buddypress/members' directory in your theme's root,
 * to avoid being overwritten by the next plugin update.
 *
 */

	if (!isset ($options['theme']))  $options['theme'] = 'base';

	if (is_admin ())
	{
?>
		<p><strong><?php _e('jQuery UI theme', 'buddyboss'); ?></strong></p>
		<select name="options[theme]">
		<?php foreach (bps_jquery_ui_themes() as $theme => $name) { ?>
			<option value="<?php echo $theme; ?>" <?php selected ($options['theme'], $theme); ?>><?php echo $name; ?></option>
		<?php } ?>
		</select>
<?php
		return 'end_of_options';
	}

	$F = bps_escaped_form_data ();
	wp_enqueue_script ('bps-template', plugins_url ('bp-profile-search/bps-template.js'), array (), BPS_VERSION);

	if (empty ($options['theme']))
	{
		$div_classes = '';
		$form_classes = '';
	}
	else
	{
		$div_classes = 'ui-accordion ui-widget ui-helper-reset';
		$form_classes = 'ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active';
		wp_enqueue_style ('jquery-ui-theme', 'https://code.jquery.com/ui/1.12.1/themes/'. $options['theme']. '/jquery-ui.min.css');
	}

	if ($F->location != 'directory')
	{
		echo "<div class='buddypress'>";
		echo "<div id='buddypress' class='buddypress-wrap $div_classes'>";
	}
	else
	{
		$accordion = 'bps-accordion'. $F->id;
		wp_enqueue_script ('jquery-ui-accordion');
?>
		<script>
		jQuery(function($) {
			$('#<?php echo $accordion; ?>').accordion({
				icons: {"header": "ui-icon-plus", "activeHeader": "ui-icon-minus"},
				active: false,
				collapsible: true,
			});
		});
		</script>

		<div id="<?php echo $accordion; ?>">
			<span class="bps-form-title"> <?php echo get_the_title ($F->id); ?></span>
<?php
	}

	echo "<form action='$F->action' method='$F->method' class='standard-form bps-form $form_classes'>\n";

	foreach ($F->fields as $f)
	{
		if ($f->display == 'hidden')
		{
			echo "<input type='hidden' name='$f->code' value='$f->value'>\n";
			continue;
		}

		$class = "editfield $f->code";
		echo "<div class='$class'>\n";

		switch ($f->display)
		{
		case 'range':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<input style='width: 10%; display: inline;' type='text' name='{$f->code}_min' id='$f->code' value='$f->min'>";
			echo '&nbsp;-&nbsp;';
			echo "<input style='width: 10%; display: inline;' type='text' name='{$f->code}_max' value='$f->max'>\n";
			break;

		case 'textbox':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<input type='text' name='$f->code' id='$f->code' value='$f->value'>\n";
			break;

		case 'number':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<input type='number' name='$f->code' id='$f->code' value='$f->value'>\n";
			break;

		case 'distance':
			$within = __('Within', 'buddyboss');
			$of = __('of', 'buddyboss');
			$km = __('km', 'buddyboss');
			$miles = __('miles', 'buddyboss');
?>
			<label for="<?php echo $f->unique_id; ?>"><?php echo $f->label; ?></label>
			<span><?php echo $within; ?></span>
			<input style="width: 4em;" type="number" min="1"
				name="<?php echo $f->code. '[distance]'; ?>"
				value="<?php echo $f->value['distance']; ?>">
			<select name="<?php echo $f->code. '[units]'; ?>">
				<option value="km" <?php selected ($f->value['units'], "km"); ?>><?php echo $km; ?></option>
				<option value="miles" <?php selected ($f->value['units'], "miles"); ?>><?php echo $miles; ?></option>
			</select>
			<span><?php echo $of; ?></span>
			<input style="width: 80%;" type="text" id="<?php echo $f->unique_id; ?>"
				name="<?php echo $f->code. '[location]'; ?>"
				value="<?php echo $f->value['location']; ?>"
				placeholder="<?php _e('Start typing, then select a location', 'buddyboss'); ?>">
			<img id="Btn_<?php echo $f->unique_id; ?>" style="cursor: pointer;" src="<?php echo plugins_url ('bp-profile-search/templates/members/locator.png'); ?>" title="<?php _e('get current location', 'buddyboss'); ?>">
<?php
			bps_autocomplete_script ($f);
			break;

		case 'selectbox':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<select name='$f->code' id='$f->code'>\n";

			$no_selection = apply_filters ('bps_field_selectbox_no_selection', '', $f);
			if (is_string ($no_selection))
				echo "<option  value=''>$no_selection</option>\n";

			foreach ($f->options as $key => $label)
			{
				$selected = in_array ($key, $f->values)? "selected='selected'": "";
				echo "<option $selected value='$key'>$label</option>\n";
			}
			echo "</select>\n";
			break;

		case 'multiselectbox':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<select name='{$f->code}[]' id='$f->code' multiple='multiple'>\n";

			foreach ($f->options as $key => $label)
			{
				$selected = in_array ($key, $f->values)? "selected='selected'": "";
				echo "<option $selected value='$key'>$label</option>\n";
			}
			echo "</select>\n";
			break;

		case 'radio':
			echo "<div class='radio'>\n";
			echo "<span class='label'>$f->label</span>\n";
			echo "<div id='$f->unique_id'>\n";

			foreach ($f->options as $key => $label)
			{
				$checked = in_array ($key, $f->values)? "checked='checked'": "";
				echo "<label><input $checked type='radio' name='$f->code' value='$key'>$label</label>\n";
			}
			echo "</div>\n";
			echo "<a style='display: inline;' class='clear-value' href='javascript:bps_clear_radio(\"$f->unique_id\");'>". __('Clear', 'buddypress'). "</a>\n";
			echo "</div>\n";
			break;

		case 'checkbox':
			echo "<div class='checkbox'>\n";
			echo "<span class='label'>$f->label</span>\n";

			foreach ($f->options as $key => $label)
			{
				$checked = in_array ($key, $f->values)? "checked='checked'": "";
				echo "<label><input $checked type='checkbox' name='{$f->code}[]' value='$key'>$label</label>\n";
			}
			echo "</div>\n";
			break;

		default:
			echo "<p>BP Profile Search: unknown display <em>$f->display</em> for field <em>$f->name</em>.</p>\n";
			break;
		}

		if (!empty ($f->description) && $f->description != '-')
			echo "<p class='description'>$f->description</p>\n";

		echo "</div>\n";
	}

	echo "<div class='submit'>\n";
	echo "<input type='submit' value='". __('Search', 'buddypress'). "'>\n";
	echo "</div>\n";
	echo "</form>\n";

	if ($F->location != 'directory')
	{
		echo "</div><!-- #buddypress --><br>";
		echo "</div><!-- .buddypress -->";
	}
	else
	{
		echo "</div><!-- accordion --><br>\n";
	}

// BP Profile Search - end of template
