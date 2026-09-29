<?php

/**
 * License activation form view.
 *
 * @var string $pluginId
 * @var string $licenseKey
 * @var string $activationDomain
 * @var bool   $lockLicenseKey
 */
declare (strict_types=1);
namespace BuddyBossPlatform;

use BuddyBossPlatform\GroundLevel\Support\Html;
$buttonClass = Html::classes('button', 'button-primary', $pluginId . '-button-activate');
// phpcs:ignore Generic.Files.LineLength.TooLong
$lockMessage = __('This license key is defined in an environment variable or constant and cannot be changed here.', 'ground-level');
?>

<form method="post" action="" name="<?php 
echo esc_attr($pluginId);
?>_activate_license_form">
    <div class="<?php 
echo esc_attr($pluginId);
?>-licence-div-form">
        <label for="license_key">
            <?php 
esc_html_e('License Key:', 'ground-level');
?>
        </label>
        <input name="license_key"
            type="text"
            id="license_key"
            value="<?php 
echo esc_attr($licenseKey);
?>"
            class="regular-text"
            <?php 
if ($lockLicenseKey) {
    ?>readonly <?php 
}
?>
        >
        <input type="hidden"
            name="activation_domain"
            value="<?php 
echo esc_attr($activationDomain);
?>"
        >
        <?php 
wp_nonce_field('mothership_activate_license', '_wpnonce');
?>
        <input
            type="hidden"
            name="<?php 
echo esc_attr($pluginId);
?>_license_button"
            value="activate"
        >
        <input
            type="submit"
            value="<?php 
esc_attr_e('Activate License', 'ground-level');
?>"
            class="<?php 
echo esc_attr($buttonClass);
?>"
        >
        <?php 
if ($lockLicenseKey) {
    ?>
            <p class="description">
                <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                <?php 
    echo esc_html($lockMessage);
    ?>
            </p>
        <?php 
}
?>
    </div>
</form>
<?php 
