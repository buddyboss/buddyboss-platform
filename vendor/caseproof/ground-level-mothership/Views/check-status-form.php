<?php

declare (strict_types=1);
namespace BuddyBossPlatform;

use BuddyBossPlatform\GroundLevel\Support\Html;
/**
 * License status check form view.
 *
 * @var string $pluginId The plugin ID for form namespacing.
 */
$buttonClass = Html::classes('button', 'button-secondary', $pluginId . '-button-check-status');
?>
<form method="post" action="" name="<?php 
echo esc_attr($pluginId);
?>_check_license_status_form">
    <?php 
wp_nonce_field('mothership_check_license_status', '_wpnonce');
?>
    <input type="hidden" name="<?php 
echo esc_attr($pluginId);
?>_license_button" value="check_status">
    <input
        type="submit"
        value="<?php 
esc_html_e('Check License Status', 'ground-level');
?>"
        class="<?php 
echo esc_attr($buttonClass);
?>"
    >
</form>
<?php 
