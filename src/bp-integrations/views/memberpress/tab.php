<?php
error_log("tab.php");
if (!defined('ABSPATH')) {
	echo die("Sorry, you can't access this directly - Security established");
}
?>

<a class="nav-tab main-nav-tab" href="#" id="learndash">
<?php
echo _e('LearnDash', 'learndash-memberpress')
?>
</a>