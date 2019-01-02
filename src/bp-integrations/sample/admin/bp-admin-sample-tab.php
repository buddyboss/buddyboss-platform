<?php

class BP_Admin_Integration_Sample extends BP_Admin_Integration_tab {

	public function initialize() {
		$this->tab_order = 999;
		$this->tab_label = __( 'Sample', 'buddyboss' );
		$this->tab_name  = 'bp-sample';
		$this->required_plugin = 'PLUGIN_ENTRY_POINT';
		$this->intro_template  = buddypress()->plugin_dir . '/bp-integrations/sample/admin/templates/tab-intro.php';

		$this->add_section( '{{SECTION_NAME}}', __( '{{SECTION_TITLE}}', 'buddyboss' ) );
		$this->add_field('{{FIELD_NAME}}', '{{FIELD_LABEL}}', [$this, '{{FIELD_CALLBACK}}']);
		$this->add_input_field('{{FIELD_NAME_2}}', '{{FIELD_LABEL_2}}', ['input_description' => 'asdfasdf']);
		$this->add_checkbox_field('{{FIELD_NAME_3}}', '{{FIELD_LABEL_3}}', ['input_default' => 1, 'input_value' => get_option('zxcv')]);
	}
}

return new BP_Admin_Integration_Sample;
