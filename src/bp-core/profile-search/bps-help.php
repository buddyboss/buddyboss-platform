<?php

function bps_help ()
{
    $screen = get_current_screen ();

	$title_00 = __('Display a Form', 'buddyboss');
	$content_00 = '
<p>'. __('After you create and configure your search form, you can display it:', 'buddyboss'). '</p>
<ul>
<li>'. sprintf (__('In its Members Directory page, using the option %s', 'buddyboss'), '<em>'. __('Add to Directory', 'buddyboss'). '</em>'). '</li>
<li>'. sprintf (__('In a sidebar or widget area, using the widget %s', 'buddyboss'), '<em>'. __('Profile Search', 'buddyboss'). '</em>'). '</li>
<li>'. sprintf (__('In a post or page, using the shortcode: %s (*)', 'buddyboss'), "<br><strong>[bps_form id=form_id]</strong>"). '</li>
<li>'. sprintf (__('Anywhere in your theme, using the PHP code: %s (*)', 'buddyboss'), "<br><strong>&lt;?php do_action ('bps_display_form', form_id); ?&gt;</strong>"). '</li>
</ul>
<p>'. sprintf (__('(*) Replace %s with the actual ID of your form.', 'buddyboss'), "<em>form_id</em>"). '</p>';

	$title_01 = __('Form Fields', 'buddyboss');
	$content_01 = '
<p>'. __('Select the profile fields to show in your search form.', 'buddyboss'). '</p>
<ul>
<li>'. __('Customize the field label and description, or leave them empty to use the default', 'buddyboss'). '</li>
<li>'. __('Select the field search mode from the <em>Search Mode</em> drop-down list', 'buddyboss'). '</li>
<li>'. __('To reorder the fields in the form, drag them up or down by the handle on the left', 'buddyboss'). '</li>
<li>'. __('To remove a field from the form, click <em>Remove</em> on the right', 'buddyboss'). '</li>
<li>'. __('To leave a field description blank, enter a single dash (-) character', 'buddyboss'). '</li>
</ul>';

	$title_02 = __('Form Template', 'buddyboss');
	$content_02 = '
<p>'. __('Select how to display your search form.', 'buddyboss'). '</p>
<ul>
<li>'. __('Select the form template', 'buddyboss'). '</li>
<li>'. __('Specify the template options, if any', 'buddyboss'). '</li>
</ul>';

	$title_03 = __('Form Settings', 'buddyboss');
	$content_03 = '
<p>
<strong>'. __('Form Method', 'buddyboss'). '</strong><br>'.
__('Select your form’s <em>method</em> attribute.', 'buddyboss'). '
</p>
<ul>
<li>'. __('POST: the form data are not visible in the URL and it’s not possible to bookmark the results page', 'buddyboss'). '</li>
<li>'. __('GET: the form data are sent as URL variables and it’s possible to bookmark the results page', 'buddyboss'). '</li>
</ul>
<p>
<strong>'. __('Directory (Results Page)', 'buddyboss'). '</strong><br>'.
__('Select the Members Directory page to be used as your form’s results page. You can choose:', 'buddyboss'). '
</p>
<ul>
<li>'. __('The BuddyPress Members Directory page', 'buddyboss'). '</li>
<li>'. __('A custom Members Directory page', 'buddyboss'). '</li>
</ul>
<p>'. sprintf (__('You can create a custom Members Directory page using the shortcode %1$s. To learn more, see the %2$s tutorial.', 'buddyboss'), '<strong>[bps_directory]</strong>', '<a href="http://dontdream.it/bp-profile-search/custom-directories/" target="_blank">Custom Directories</a>'). '</p>
<p>
<strong>'. __('Add to Directory', 'buddyboss'). '</strong><br>'.
__('Choose whether to add your form to the above Members Directory page.', 'buddyboss'). '
</p>';

	$title_04 = __('Persistent Search', 'buddyboss');
	$content_04 = '
<p>'. __('Enable or disable the <em>persistent search</em> feature.', 'buddyboss'). '</p>
<ul>
<li>'. __('If enabled, a search is cleared when the user hits the <em>Clear</em> button', 'buddyboss'). '</li>
<li>'. __('If disabled, a search is cleared when the user hits the <em>Clear</em> button, or navigates away from the results page', 'buddyboss'). '</li>
</ul>
<p>'. __('This selection applies to all your forms at once.', 'buddyboss'). '</p>';

	$title_05 = __('Create a form', 'buddyboss');
	$content_05 = '
<p>'. sprintf (__('To create a form, use the button %s.', 'buddyboss'), '<em>'. __('Add New'). '</em>'). '</p>
<p>'. __('You can then add the form fields, specify the form settings and select the form template.', 'buddyboss'). '</p>';

	$sidebar = '
<p><strong>'. __('For more information:', 'buddyboss'). '</strong></p>
<p><a href="http://dontdream.it/bp-profile-search/" target="_blank">'. __('Documentation', 'buddyboss'). '</a></p>
<p><a href="http://dontdream.it/bp-profile-search/search-modes/" target="_blank">'. __('Search Modes', 'buddyboss'). '</a></p>
<p><a href="http://dontdream.it/bp-profile-search/troubleshooting/" target="_blank">'. __('Troubleshooting', 'buddyboss'). '</a></p>
<p><a href="http://dontdream.it/bp-profile-search/incompatible-plugins/" target="_blank">'. __('Incompatible plugins', 'buddyboss'). '</a></p>
<p><a href="http://dontdream.it/support/forum/bp-profile-search-forum/" target="_blank">'. __('Support Forum', 'buddyboss'). '</a></p>
<br><br>';

	$screen->add_help_tab (array ('id' => 'bps_05', 'title' => $title_05, 'content' => $content_05));
	$screen->add_help_tab (array ('id' => 'bps_01', 'title' => $title_01, 'content' => $content_01));
	$screen->add_help_tab (array ('id' => 'bps_03', 'title' => $title_03, 'content' => $content_03));
	$screen->add_help_tab (array ('id' => 'bps_02', 'title' => $title_02, 'content' => $content_02));
	$screen->add_help_tab (array ('id' => 'bps_04', 'title' => $title_04, 'content' => $content_04));
	$screen->add_help_tab (array ('id' => 'bps_00', 'title' => $title_00, 'content' => $content_00));

	$screen->set_help_sidebar ($sidebar);

	return true;
}
