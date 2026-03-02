<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'single' => array(
		'name'   => 'Default Data',
		'desc'   => 'Sample fields with default data.',
		'fields' => array(
			'textarea'  => array(
				'name'                    => 'Paragraph Text',
				'desc'                    => 'Long text in textarea',
				'required'                => '1',
				'default-visibility'      => 'adminsonly',
				'allow-custom-visibility' => 'disabled',
				'options'                 => array(),
			),
			'number'    => array(
				'name'                    => 'Number',
				'desc'                    => 'Some number only field',
				'required'                => '0',
				'default-visibility'      => 'loggedin',
				'allow-custom-visibility' => 'allowed',
				'options'                 => array(),
			),
			'checkbox'  => array(
				'name'                    => 'Checkboxes',
				'desc'                    => 'Several checkboxes to select',
				'required'                => '1',
				'default-visibility'      => 'adminsonly',
				'allow-custom-visibility' => 'disabled',
				'options'                 => array(
					array(
						'name'              => 'Checkbox 1',
						'is_default_option' => 1,
						'option_order'      => '1',
					),
					array(
						'name'              => 'Checkbox 2',
						'is_default_option' => 1,
						'option_order'      => '2',
					),
					array(
						'name'              => 'Checkbox 3',
						'is_default_option' => false,
						'option_order'      => '3',
					),
					array(
						'name'              => 'Checkbox 4',
						'is_default_option' => false,
						'option_order'      => '4',
					),
				),
			),
			'selectbox' => array(
				'name'                    => 'Drop Down',
				'desc'                    => 'One selected value in selectbox',
				'required'                => '0',
				'default-visibility'      => 'loggedin',
				'allow-custom-visibility' => 'allowed',
				'options'                 => array(
					array(
						'name'              => 'Option 1',
						'is_default_option' => false,
						'option_order'      => '1',
					),
					array(
						'name'              => 'Option 2',
						'is_default_option' => true,
						'option_order'      => '2',
					),
					array(
						'name'              => 'Option 3',
						'is_default_option' => false,
						'option_order'      => '3',
					),
				),
			),
			'radio'     => array(
				'name'                    => 'Radio Buttons',
				'desc'                    => 'One radio button to select',
				'required'                => '0',
				'default-visibility'      => 'public',
				'allow-custom-visibility' => 'allowed',
				'options'                 => array(
					array(
						'name'              => 'Radio 1',
						'is_default_option' => true,
						'option_order'      => '1',
					),
					array(
						'name'              => 'Radio 2',
						'is_default_option' => false,
						'option_order'      => '2',
					),
					array(
						'name'              => 'Radio 3',
						'is_default_option' => false,
						'option_order'      => '3',
					),
				),
			),
		),
	),
);
