<?php

/**
 * License deactivation form view.
 *
 * @var string                                                $pluginId
 * @var string                                                $activationDomain
 * @var string                                                $licenseKeyMasked
 * @var bool                                                  $lockLicenseKey
 * @var GroundLevel\Mothership\Transients\ActivationTransient $activation
 */
declare (strict_types=1);
namespace BuddyBossPlatform;

use BuddyBossPlatform\GroundLevel\Support\Html;
$buttonClass = Html::classes('button', 'button-secondary', $pluginId . '-button-deactivate');
?>

<?php 
if ($activation->licenseKey) {
    ?>
<div class="card gl-mosh-license-info <?php 
    echo esc_attr($pluginId);
    ?>-license-info">
    <h2 class="title"><?php 
    esc_html_e('Active License Key Information', 'ground-level');
    ?></h2>
    <table class="widefat striped">
        <tbody>
            <tr>
                <th scope="row"><?php 
    esc_html_e('License Key', 'ground-level');
    ?></th>
                <td>
                    <code><?php 
    echo esc_html($licenseKeyMasked);
    ?></code>
                    <?php 
    if ($lockLicenseKey) {
        ?>
                        <?php 
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $lockMessage = __('This license key is defined in an environment variable or constant and cannot be changed from here.', 'ground-level');
        ?>
                        <span
                            class="dashicons dashicons-lock"
                            role="img"
                            aria-label="<?php 
        echo esc_attr($lockMessage);
        ?>"
                            title="<?php 
        echo esc_attr($lockMessage);
        ?>"
                        ></span>
                    <?php 
    }
    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php 
    esc_html_e('Status', 'ground-level');
    ?></th>
                <td>
                    <strong>
                        <?php 
    echo esc_html(\sprintf(
        // Translators: %s: site domain.
        __('Active on %s', 'ground-level'),
        $activationDomain
    ));
    ?>
                    </strong>
                </td>
            </tr>
            <?php 
    if ('' !== $activation->productName) {
        ?>
                <tr>
                    <th scope="row"><?php 
        esc_html_e('Product', 'ground-level');
        ?></th>
                    <td><?php 
        echo esc_html($activation->productName);
        ?></td>
                </tr>
            <?php 
    }
    ?>
            <?php 
    if (0 < $activation->prodActivationsAllowed) {
        ?>
                <tr>
                    <th scope="row"><?php 
        esc_html_e('Production Activations', 'ground-level');
        ?></th>
                    <td>
                        <?php 
        echo wp_kses(\sprintf(
            // Translators: 1: used activations, 2: allowed activations.
            __('<strong>%1$d of %2$d</strong> sites activated', 'ground-level'),
            $activation->prodActivationsUsed,
            $activation->prodActivationsAllowed
        ), ['strong' => []]);
        ?>
                    </td>
                </tr>
            <?php 
    }
    ?>
            <?php 
    if (0 < $activation->testActivationsAllowed) {
        ?>
                <tr>
                    <th scope="row"><?php 
        esc_html_e('Test Activations', 'ground-level');
        ?></th>
                    <td>
                        <?php 
        echo wp_kses(\sprintf(
            // Translators: 1: used activations, 2: allowed activations.
            __('<strong>%1$d of %2$d</strong> sites activated', 'ground-level'),
            $activation->testActivationsUsed,
            $activation->testActivationsAllowed
        ), ['strong' => []]);
        ?>
                    </td>
                </tr>
            <?php 
    }
    ?>
            <?php 
    if ('' !== $activation->licenseExpiresAt) {
        ?>
                <tr>
                    <th scope="row"><?php 
        esc_html_e('Expires', 'ground-level');
        ?></th>
                    <td>
                        <?php 
        echo esc_html(date_i18n(get_option('date_format'), \strtotime($activation->licenseExpiresAt)));
        ?>
                    </td>
                </tr>
            <?php 
    }
    ?>
        </tbody>
    </table>
</div>
<?php 
}
?>

<form
    method="post"
    action=""
    name="<?php 
echo esc_attr($pluginId);
?>_deactivate_license_form"
>
    <?php 
wp_nonce_field('mothership_deactivate_license', '_wpnonce');
?>
    <input type="hidden" name="<?php 
echo esc_attr($pluginId);
?>_license_button" value="deactivate">
    <p class="submit">
        <input
            type="submit"
            class="<?php 
echo esc_attr($buttonClass);
?>"
            value="<?php 
echo esc_attr(\sprintf(
    // Translators: %s: site domain.
    __('Deactivate License Key on %s', 'ground-level'),
    $activationDomain
));
?>"
        >
    </p>
</form>
<?php 
