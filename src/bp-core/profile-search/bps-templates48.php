<?php

function bps_escaped_form_data48 ()
{
	list ($form, $location) = bps_template_args ();

	$meta = bps_meta ($form);
	$fields = bps_parse_request (bps_get_request ('form', $form));

	$F = new stdClass;
	$F->id = $form;
	$F->location = $location;
	$F->header = bps_wpml ($form, '-', 'header', $meta['header']);
	$F->toggle = ($meta['toggle'] == 'Enabled');
	$F->toggle_text = bps_wpml ($form, '-', 'toggle form', $meta['button']);

	$dirs = bps_directories ();
	$F->action = $location == 'directory'?
		parse_url ($_SERVER['REQUEST_URI'], PHP_URL_PATH):
		$dirs[bps_wpml_id ($meta['action'])]->link;

	if (defined ('DOING_AJAX'))
		$F->action = parse_url ($_SERVER['HTTP_REFERER'], PHP_URL_PATH);

	$F->method = $meta['method'];
	$F->fields = array ();

	foreach ($meta['field_code'] as $k => $id)
	{
		if (empty ($fields[$id]))  continue;

		$f = $fields[$id];
		$mode = $meta['field_mode'][$k];
		if (!bps_Fields::set_display ($f, $mode))  continue;

		$f->label = $f->name;
		$custom_label = bps_wpml ($form, $id, 'label', $meta['field_label'][$k]);
		if (!empty ($custom_label))
		{
			$f->label = $custom_label;
			$F->fields[] = bps_set_hidden_field ($f->code. '_label', $f->label);
		}

		$custom_desc = bps_wpml ($form, $id, 'comment', $meta['field_desc'][$k]);
		if ($custom_desc == '-')
			$f->description = '';
		else if (!empty ($custom_desc))
			$f->description = $custom_desc;

		if (!isset ($f->filter))
		{
			$f->min = $f->max = $f->value = '';
			$f->values = array ();
			if ($f->display == 'distance')
				$f->value['distance'] = $f->value['units'] = $f->value['location'] = $f->value['lat'] = $f->value['lng'] = '';
		}
		else
		{
			$f->min = isset ($f->value['min'])? $f->value['min']: '';
			$f->max = isset ($f->value['max'])? $f->value['max']: '';
			$f->values = (array)$f->value;
		}

		do_action ('bps_field_before_search_form', $f);

		if ($mode != '')  $f->code .= '_'. $mode;
		$f->unique_id = bps_unique_id ($f->code);

		$F->fields[] = $f;
	}

	$F->fields[] = bps_set_hidden_field (BPS_FORM, $form);

	do_action ('bps_before_search_form', $F);

	$F->toggle_text = esc_attr ($F->toggle_text);
	foreach ($F->fields as $f)
	{
		if (!is_array ($f->value))  $f->value = esc_attr (stripslashes ($f->value));
		if ($f->display == 'hidden')  continue;

		$f->label = esc_attr ($f->label);
		$f->description = esc_attr ($f->description);
		foreach ($f->values as $k => $value)  $f->values[$k] = esc_attr (stripslashes ($value));
		$options = array ();
		foreach ($f->options as $key => $label)  $options[esc_attr ($key)] = esc_attr ($label);
		$f->options = $options;
	}

	return $F;
}

function bps_escaped_filters_data48 ()
{
	list ($request, $full) = bps_template_args ();

	$F = new stdClass;

	$action = parse_url ($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$action = add_query_arg (BPS_FORM, 'clear', $action);
	$F->action = $full? esc_url ($action): '';
	$F->fields = array ();

	$fields = bps_parse_request ($request);
	foreach ($fields as $f)
	{
		if (!isset ($f->filter))  continue;

		if (empty ($f->label))
			$f->label = $f->name;
		$f->label = esc_html ($f->label);
		$f->value = stripslashes_deep ($f->value);

		do_action ('bps_field_before_filters', $f);
		$F->fields[] = $f;
	}

	usort ($F->fields, function($a, $b) {return ($a->order <= $b->order)? -1: 1;});

	do_action ('bps_before_filters', $F);
	return $F;
}
