<?php
$lib_path = buddypress()->plugin_dir . 'bp-core/libraries/WPPerformanceTester/';

require_once( $lib_path . 'WPPerformanceTester_Plugin.php' );
$aPlugin = new WPPerformanceTester_Plugin();
$aPlugin->activate();
$aPlugin->addActionsAndFilters();
?>



<div class="wrap">
	<div id="bb-upgrade">
	<?php $aPlugin->settingsPage(); ?>
	</div>
</div>
