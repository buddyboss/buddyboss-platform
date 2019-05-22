<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'single' => array(
		'name'   => 'Single Fields',
		'desc'   => 'All fields with a single value possible',
		'fields' => array(
			'datebox'  => array(
				'name'                    => 'DateBox',
				'desc'                    => 'Any kind of date',
				'required'                => '0',
				'default-visibility'      => 'public',
				'allow-custom-visibility' => 'allowed',
				'options'                 => array()
			),
			'textarea' => array(
				'name'                    => 'Textarea',
				'desc'                    => 'Long text in textarea',
				'required'                => '1',
				'default-visibility'      => 'adminsonly',
				'allow-custom-visibility' => 'disabled',
				'options'                 => array()
			),
			'number'   => array(
				'name'                    => 'Number',
				'desc'                    => 'Some number only field',
				'required'                => '0',
				'default-visibility'      => 'loggedin',
				'allow-custom-visibility' => 'allowed',
				'options'                 => array()
			),
			'textbox'  => array(
				'name'                    => 'Textbox',
				'desc'                    => 'Rather short one-line text',
				'required'                => '1',
				'default-visibility'      => 'friends',
				'allow-custom-visibility' => 'disabled',
				'options'                 => array()
			),
			'url'      => array(
				'name'                    => 'URL',
				'desc'                    => 'Link to any web-page or site',
				'required'                => '0',
				'default-visibility'      => 'public',
				'allow-custom-visibility' => 'allowed',
				'options'                 => array()
			)
		)
	),
	'multi'  => array(
		'name'   => 'Multi Fields',
		'desc'   => 'All fields with several possible values',
		'fields' => array(
			'checkbox'       => array(
				'name'                    => 'Checkboxes',
				'desc'                    => 'Several checkboxes to select',
				'required'                => '1',
				'default-visibility'      => 'adminsonly',
				'allow-custom-visibility' => 'disabled',
				'options'                 => array(
					array(
						'name'              => 'checkbox 1',
						'is_default_option' => 1,
						'option_order'      => '1'
					),
					array(
						'name'              => 'checkbox 2',
						'is_default_option' => 1,
						'option_order'      => '2'
					),
					array(
						'name'              => 'checkbox 3',
						'is_default_option' => false,
						'option_order'      => '3'
					),
					array(
						'name'              => 'checkbox 4',
						'is_default_option' => false,
						'option_order'      => '4'
					)
				)
			),
			'selectbox'      => array(
				'name'                    => 'Selectbox',
				'desc'                    => 'One selected value in selectbox',
				'required'                => '0',
				'default-visibility'      => 'loggedin',
				'allow-custom-visibility' => 'allowed',
				'options'                 => array(
					array(
						'name'              => 'selectbox 1',
						'is_default_option' => false,
						'option_order'      => '1'
					),
					array(
						'name'              => 'selectbox 2',
						'is_default_option' => true,
						'option_order'      => '2'
					),
					array(
						'name'              => 'selectbox 3',
						'is_default_option' => false,
						'option_order'      => '3'
					)
				)
			),
			'multiselectbox' => array(
				'name'                    => 'Multiselectbox',
				'desc'                    => 'Several selected values in selectbox',
				'required'                => '1',
				'default-visibility'      => 'friends',
				'allow-custom-visibility' => 'disabled',
				'options'                 => array(
					array(
						'name'              => 'multiselectbox 1',
						'is_default_option' => true,
						'option_order'      => '1'
					),
					array(
						'name'              => 'multiselectbox 2',
						'is_default_option' => false,
						'option_order'      => '2'
					),
					array(
						'name'              => 'multiselectbox 3',
						'is_default_option' => true,
						'option_order'      => '3'
					)
				)
			),
			'radio'          => array(
				'name'                    => 'Radios',
				'desc'                    => 'One radio button to select',
				'required'                => '0',
				'default-visibility'      => 'public',
				'allow-custom-visibility' => 'allowed',
				'options'                 => array(
					array(
						'name'              => 'radio 1',
						'is_default_option' => true,
						'option_order'      => '1'
					),
					array(
						'name'              => 'radio 2',
						'is_default_option' => false,
						'option_order'      => '2'
					),
					array(
						'name'              => 'radio 3',
						'is_default_option' => false,
						'option_order'      => '3'
					)
				)
			)
		)
	),
);

/**
 * <select style="width: 30%" onchange="show_options(this.value)" id="fieldtype" name="fieldtype">
 *  <optgroup label="Multi Fields">
 *      <option value="checkbox">Checkboxes</option>
 *      <option value="selectbox">Drop Down Select Box</option>
 *      <option value="multiselectbox">Multi Select Box</option>
 *      <option value="radio">Radio Buttons</option>
 *  </optgroup>
 *  <optgroup label="Single Fields">
 *      <option value="datebox">Date Selector</option>
 *      <option value="textarea">Multi-line Text Area</option>
 *      <option value="number">Number</option>
 *      <option selected="selected" value="textbox">Text Box</option>
 *      <option value="url">URL</option>
 *  </optgroup>
 * </select>
 */