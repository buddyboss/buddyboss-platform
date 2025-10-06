/**
 * Mothership Addons Manager
 * @class
 * @classdesc This class is responsible for managing the installation and activation of addons.
 */
class AddonsManager {
    /** 
     * @type {NodeList} List of product elements.
     */
    products = document.querySelectorAll(".mosh-product");

    /** 
     * @type {NodeList} List of action buttons.
     */
    actionButtons = document.querySelectorAll(".mosh-product-action button");

    /** 
     * @type {HTMLInputElement} Search input element.
     */
    searchInput = document.getElementById("mosh-products-search");

    /** 
     * @type {Object} Icons object.
     */
    icons = Object.freeze({
        activate: '<i class="dashicons dashicons-yes-alt" aria-hidden="true"></i>',
        deactivate: '<i class="dashicons dashicons-no-alt" aria-hidden="true"></i>',
        install: '<i class="dashicons dashicons-download" aria-hidden="true"></i>',
        switch_themes: '<i class="dashicons dashicons-admin-appearance" aria-hidden="true"></i>',
    });

    /**
     * Constructor ensures init() is called when the DOM is ready.
     * @constructor
     */
    constructor() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    /**
     * Initializes the addons manager by adding event listeners.
     */
    init() {
        this.actionButtons.forEach(button => {
            button.addEventListener('click', this.handleActionButtonClick.bind(this));
        });

        this.searchInput.addEventListener('keyup', this.filterVisibleProducts.bind(this));
        this.searchInput.addEventListener('input', this.resetProductDisplay.bind(this));
    }

    /**
     * Filters the visible products based on the search input.
     */
    filterVisibleProducts() {
        var searchValue = this.searchInput.value.toLowerCase();
        this.products.forEach(function (product) {
            var productName = product
                .querySelector(".mosh-product-name")
                .textContent.toLowerCase();
            if (productName.includes(searchValue)) {
                product.style.display = "block";
            } else {
                product.style.display = "none";
            }
        });
    }

    /**
     * Resets the product display when the search input is empty.
     */
    resetProductDisplay() {
        if (this.searchInput.value === "") {
            this.products.forEach(function (product) {
                product.style.display = "block";
            });
        }
    }

    /**
     * Handles the click event for the action buttons.
     * @param {Event} event - The click event.
     */
    handleActionButtonClick(event) {
        var $button = event.target,
            $addon = $button.closest(".mosh-product"),
            originalButtonHtml = $button.innerHTML,
            originalButtonWidth = $button.offsetWidth,
            type = $button.dataset.extensionType,
            action,
            statusClass,
            statusText,
            buttonHtml,
            successText;

        if ($addon.classList.contains("mosh-product-status-active")) {
            if (type === "theme") {
                window.location.href = MoshAddons.themes_url;
                return;
            }
            action = "mosh_addon_deactivate";
            statusClass = "mosh-product-status-inactive";
            statusText = MoshAddons.inactive;
            buttonHtml = this.icons.activate + MoshAddons.activate;
        } else if (
            $addon.classList.contains("mosh-product-status-inactive")
        ) {
            action = "mosh_addon_activate";
            statusClass = "mosh-product-status-active";
            statusText = MoshAddons.active;
            if (type === "plugin") {
                buttonHtml = this.icons.deactivate + MoshAddons.deactivate;
            } else {
                buttonHtml = this.icons.switch_themes + MoshAddons.switch_themes;
            }
        } else if (
            $addon.classList.contains("mosh-product-status-not-installed")
        ) {
            action = "mosh_addon_install";
            statusClass = "mosh-product-status-active";
            statusText = MoshAddons.active;
            if (type === "plugin") {
                buttonHtml = this.icons.deactivate + MoshAddons.deactivate;
            } else {
                buttonHtml = this.icons.switch_themes + MoshAddons.switch_themes;
            }
        } else {
            return;
        }

        $button.disabled = true;
        $button.innerHTML = '<i aria-hidden="true">' + MoshAddons.processing + '</i>';
        $button.classList.add("mosh-loading");
        $button.style.width = originalButtonWidth + "px";

        var data = {
            action: action,
            _ajax_nonce: MoshAddons.nonce,
            slug: $button.dataset.slug,
            extension_type: $button.dataset.extensionType,
        };

        var handleError = function (message) {
            var messageDiv = document.createElement("div");
            messageDiv.className =
                "mosh-product-message mosh-product-message-error";
            messageDiv.textContent = message;
            $addon
                .querySelector(".mosh-product-actions")
                .appendChild(messageDiv);
            $button.innerHTML = originalButtonHtml;
        };

        var formData = new FormData();
        formData.append("action", data.action);
        formData.append("_ajax_nonce", data._ajax_nonce);
        formData.append("slug", data.slug);
        formData.append("extension_type", data.extension_type);
        fetch(MoshAddons.ajax_url, {
            method: "POST",
            body: formData,
        })
            .then((response) => response.json())
            .then((response) => {
                if (
                    !response ||
                    typeof response !== "object" ||
                    typeof response.success !== "boolean"
                ) {
                    handleError(
                        type === "plugin"
                            ? MoshAddons.plugin_install_failed
                            : MoshAddons.install_failed
                    );
                } else if (!response.success) {
                    if (
                        typeof response.data === "object" &&
                        response.data[0] &&
                        response.data[0].message
                    ) {
                        handleError(
                            response.data[0].message
                        );
                    } else {
                        handleError(response.data);
                    }
                } else {
                    if (action === "mosh_addon_install") {
                        successText = response.data.message;

                        if (!response.data.activated) {
                            statusClass = "mosh-product-status-inactive";
                            statusText = MoshAddons.inactive;
                            buttonHtml =
                                this.icons.activate + MoshAddons.activate;
                        }
                    } else {
                        successText = response.data;
                    }

                    var successDiv = document.createElement("div");
                    successDiv.className =
                        "mosh-product-message mosh-product-message-success";
                    successDiv.textContent = successText;
                    $addon
                        .querySelector(".mosh-product-actions")
                        .appendChild(successDiv);

                    $addon.classList.remove(
                        "mosh-product-status-active",
                        "mosh-product-status-inactive",
                        "mosh-product-status-not-installed"
                    );
                    $addon.classList.add(statusClass);
                    $addon.querySelector(
                        ".mosh-product-status-label"
                    ).textContent = statusText;
                    $button.innerHTML = buttonHtml;
                }
            })
            .catch(() => {
                handleError(
                    type === "plugin"
                        ? MoshAddons.plugin_install_failed
                        : MoshAddons.install_failed
                );
            })
            .finally(() => {
                $button.disabled = false;
                $button.classList.remove("mosh-loading");
                $button.style.width = "auto";

                // Automatically clear add-on messages after 3 seconds
                setTimeout(function () {
                    $addon
                        .querySelectorAll(".mosh-product-message")
                        .forEach(function (msg) {
                            msg.remove();
                        });
                }, 3000);
            });
    }
}

new AddonsManager();
