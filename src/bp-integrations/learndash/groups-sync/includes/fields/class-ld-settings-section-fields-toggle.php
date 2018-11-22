<?php

class LearnDash_Settings_Section_Fields_Toggle extends LearnDash_Settings_Section_Fields
{
	public function __construct()
    {
		$this->field_type	= 'toggle';

		parent::__construct();
	}

	public function create_section_field($field_args = [])
    {
        if (! $this->has_value($field_args, 'options')) {
            return;
        }

        $field_args['type'] = 'checkbox';

		$html = '';

		if ($this->has_value($field_args, 'desc')) {
			$html .= $field_args['desc'];
		}

		$html .= '<fieldset>';
		$html .= '<legend class="screen-reader-text">';
		$html .= '<span>'. $field_args['label'] .'</span>';
		$html .= '</legend>';

		foreach ($field_args['options'] as $option_key => $option_label) {

			$html .= ' <label for="'. $field_args['id'] .'-'. $option_key .'" >';
            $html .= sprintf('<input type="hidden" %s value="0" />', $this->get_field_attribute_name($field_args));

			$html .= '<input ';

			$html .= $this->get_field_attribute_type($field_args);
			$html .= $this->get_field_attribute_id($field_args);
			$html .= $this->get_field_attribute_name($field_args);
			$html .= $this->get_field_attribute_class($field_args);
			$html .= $this->get_field_attribute_misc($field_args);
			$html .= $this->get_field_attribute_required($field_args);
			$html .= ' value="1" ';

			$html .= ' '. checked($option_key, $field_args['value'], false) .' ';

			$html .= ' />';

			$html .= $option_label .'</label>';
			$html .= '</br>';
		}
		$html .= '</fieldset>';

		echo $html;
	}

    protected function has_value($object, $key)
    {
        return isset($object[$key]) && $object[$key];
    }
}
