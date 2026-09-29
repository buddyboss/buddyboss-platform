<?php

/**
 * Add-ons products grid view.
 *
 * @var array<object> $products The prepared product objects for display.
 */
declare (strict_types=1);
?>

<div id="mosh-admin-addons" class="wrap">
    <h1>
        <form method="post" action="">
            <?php 
namespace BuddyBossPlatform;

wp_nonce_field('grdlvl_mosh_refresh_addons', 'grdlvl_mosh_refresh_addons_nonce');
?>
            <?php 
esc_html_e('Available Add-ons', 'ground-level');
?>
            <input type="submit"
                class="button button-secondary"
                name="submit-button-mosh-refresh-addon"
                value="<?php 
esc_attr_e('Refresh Add-ons', 'ground-level');
?>"
            >
            <input type="search"
                id="mosh-products-search"
                placeholder="<?php 
esc_attr_e('Search add-ons', 'ground-level');
?>"
            >
        </form>
    </h1>
    <?php 
if (!empty($products)) {
    ?>
        <div id="mosh-products-container">
            <div class="mosh-products">
                <?php 
    foreach ($products as $product) {
        ?>
                <div class="mosh-product mosh-product-status-<?php 
        echo esc_attr($product->status);
        ?>">
                    <div class="mosh-product-inner">
                        <?php 
        if ($product->updateAvailable) {
            ?>
                        <div class="update-message notice inline notice-warning notice-alt mosh-product-update-message">
                            <p>
                                <?php 
            esc_html_e('New version available.', 'ground-level');
            ?>
                                <button class="button-link mosh-product-update-button" type="button">
                                    <?php 
            esc_html_e('Update now', 'ground-level');
            ?>
                                </button>
                            </p>
                        </div>
                        <?php 
        }
        ?>
                        <div class="mosh-product-details">
                            <div class="mosh-product-image">
                                <img src="<?php 
        echo esc_url($product->image);
        ?>"
                                    alt="<?php 
        echo esc_attr($product->list_name);
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
        ?>"
                                >
                            </div>
                            <div class="mosh-product-info">
                                <h2 class="mosh-product-name">
                                        <?php 
        echo esc_html($product->name);
        ?>
                                </h2>
                                <p><?php 
        echo esc_html($product->description);
        ?></p>
                            </div>
                        </div>
                        <div class="mosh-product-actions mosh-clearfix">
                            <div class="mosh-product-status">
                                <strong>
                                <?php 
        \printf(
            // Translators: %s: add-on status label.
            esc_html__('Status: %s', 'ground-level'),
            \sprintf('<span class="mosh-product-status-label">%s</span>', esc_html($product->statusLabel))
        );
        ?>
                                </strong>
                            </div>
                            <div class="mosh-product-action">
                                <?php 
        if ('upgrade' === $product->status) {
            ?>
                                <a class="button button-primary mosh-product-upgrade"
                                    href="<?php 
            echo esc_url($product->upgradeUrl);
            ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <i class="<?php 
            echo esc_attr($product->iconClass);
            ?>"></i>
                                    <?php 
            echo esc_html($product->buttonLabel);
            ?>
                                </a>
                                <?php 
        } else {
            ?>
                                <button type="button"
                                    data-slug="<?php 
            echo esc_attr($product->slug);
            ?>"
                                    data-extension-type="<?php 
            echo esc_attr($product->extension_type);
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
            ?>"
                                >
                                    <i class="<?php 
            echo esc_attr($product->iconClass);
            ?>"></i>
                                    <?php 
            echo esc_html($product->buttonLabel);
            ?>
                                </button>
                                <?php 
        }
        ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
    }
    ?>
            </div>
        </div>
    <?php 
} else {
    ?>
        <h3><?php 
    esc_html_e('No Add-ons found for your License Key.', 'ground-level');
    ?></h3>
        <p>
            <?php 
    esc_html_e('If you were expecting add-ons here, use the "Refresh Add-ons" button above to try again.', 'ground-level');
    ?>
        </p>
    <?php 
}
?>
</div>
<?php 
