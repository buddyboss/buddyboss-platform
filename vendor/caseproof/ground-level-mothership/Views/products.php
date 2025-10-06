<?php

declare (strict_types=1);
?>

<div id="mosh-admin-addons" class="wrap">
    <h1>
        <form method="post" action="">
            <?php 
namespace BuddyBossPlatform;

esc_html_e('Available Add-ons', 'caseproof-mothership');
?>
            <input type="submit"
                class="button button-secondary"
                name="submit-button-mosh-refresh-addon"
                value="<?php 
esc_attr_e('Refresh Add-ons', 'caseproof-mothership');
?>"
            >
            <input type="search"
                id="mosh-products-search"
                placeholder="<?php 
esc_attr_e('Search add-ons', 'caseproof-mothership');
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
                                New version available. 
                                <button class="button-link mosh-product-update-button" type="button">Update now</button>
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
            esc_html__('Status: %s', 'caseproof-mothership'),
            \sprintf('<span class="mosh-product-status-label">%s</span>', esc_html($product->statusLabel))
        );
        ?>
                                </strong>
                            </div>
                            <div class="mosh-product-action">
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
    esc_html_e('There were no Add-ons found for your License Key.', 'caseproof-mothership');
    ?></h3>
    <?php 
}
?>
</div>
<?php 
