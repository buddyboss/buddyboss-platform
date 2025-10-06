<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Container\Contracts\LoadableDependency;
use BuddyBossPlatform\GroundLevel\Container\Service;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Service as IPNService;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
use BuddyBossPlatform\GroundLevel\Support\Str;
class View extends Service implements LoadableDependency
{
    use Hookable;
    /**
     * Appends the count indicator to the main admin menu item.
     *
     * If the MENU_SLUG is not set, no indicator will be appended.
     */
    public function appendCountToMainMenuItem() : void
    {
        global $menu;
        $slug = $this->container->get(IPNService::MENU_SLUG);
        if ($slug) {
            foreach (\array_reverse($menu, \true) as $index => $menuItem) {
                if (($menuItem[2] ?? '') === $slug) {
                    $menu[$index][0] .= ' ' . \trim($this->getMenuCountIndicatorHtm());
                    break;
                }
            }
        }
    }
    /**
     * Configures the hooks for the service.
     *
     * @return array<int, Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_ACTION, $this->container->get(IPNService::RENDER_HOOK), [$this, 'render']), new Hook(Hook::TYPE_ACTION, 'admin_enqueue_scripts', [$this, 'enqueue']), new Hook(Hook::TYPE_ACTION, 'admin_menu', [$this, 'appendCountToMainMenuItem'], 100)];
    }
    /**
     * Enqueues the service scripts.
     */
    public function enqueue() : void
    {
        $file = $this->container->get(IPNService::FILE);
        $path = 'assets/ipn-inbox.js';
        wp_enqueue_script(Str::toKebabCase($this->container->get(IPNService::class)->prefixId('inbox')), plugin_dir_url($file) . $path, [], \filemtime(plugin_dir_path($file) . $path), \true);
    }
    /**
     * Retrieves the HTML output for the menu count indicator.
     *
     * @return string
     */
    protected function getMenuCountIndicatorHtm() : string
    {
        /** @var Store $store */
        // phpcs:ignore
        $store = $this->container->get(Store::class);
        $count = \count($store->fetch()->notifications(\false, Store::FILTER_UNREAD));
        if (empty($count)) {
            return '';
        }
        $countI18n = number_format_i18n($count);
        $unreadText = \sprintf(_n('%s unread notification', '%s unread notifications', $count), $countI18n);
        \ob_start();
        ?>
            <span class="menu-counter count-<?php 
        echo absint($count);
        ?>">
                <span class="pending-count" aria-hidden="true"><?php 
        echo $countI18n;
        ?></span>
                <span class="screen-reader-text"><?php 
        echo $unreadText;
        ?></span>
            </span>
        <?php 
        return \ob_get_clean();
    }
    /**
     * Retrieves the HTML output to be rendered.
     *
     * @return string
     */
    protected function getHtml() : string
    {
        $html = $this->getRootElementHtml();
        $html .= $this->getScript();
        return $html;
    }
    /**
     * Retrieves the JSON-encoded notifications to be loaded into the inbox.
     *
     * @return string
     */
    protected function getNotifications() : string
    {
        /** @var Store $store */
        // phpcs:ignore
        $store = $this->container->get(Store::class);
        return wp_json_encode($store->fetch()->notifications());
    }
    /**
     * Retrieves the HTML for the root element <div> where the inbox will be rendered.
     *
     * @return string
     */
    protected function getRootElementHtml() : string
    {
        $id = $this->getRootElementId();
        $html = '<div id="' . $id . '"></div>';
        $prefix = $this->container->get(IPNService::class)->prefixId();
        /**
         * Filters the HTML for the root element where the inbox will be rendered.
         *
         * The dynamic portion of this hook, `{$prefix}`, is the PREFIX parameter
         * set in the container with the `ipn` suffix appended to it. The default
         * value is `grdlvl_ipn`.
         *
         * This filter can be used to wrap the HTML element in another HTML element,
         * apply CSS classes to the element, and more.
         *
         * @param string $html The HTML to be rendered.
         * @param string $id   The HTML ID attribute for the root element.
         */
        return apply_filters("{$prefix}_root_element_html", $html, $id);
    }
    /**
     * Retrieves the HTML ID attribute for the root element where the inbox will
     * be rendered.
     *
     * @return string
     */
    protected function getRootElementId() : string
    {
        return $this->container->get(IPNService::class)->prefixId('root');
    }
    /**
     * Retrievs the <script> tag used to create the inbox React component.
     *
     * @return string
     */
    public function getScript() : string
    {
        $namespace = Str::toCamelCase($this->container->get(IPNService::class)->prefixId('inbox'));
        $ajax = $this->container->get(Ajax::class);
        \ob_start();
        ?>
        <script type="text/javascript">
            window.onIpnAppReady = ((createApp) => {
                createApp({
                    el: document.getElementById('<?php 
        echo $this->getRootElementId();
        ?>'),
                    namespace: '<?php 
        echo $namespace;
        ?>',
                    i18n: {
                        'Dismiss': "<?php 
        esc_html_e('Dismiss', 'ground-level');
        ?>",
                        'Dismiss All': "<?php 
        esc_html_e('Dismiss All', 'ground-level');
        ?>",
                        'All': "<?php 
        esc_html_e('All', 'ground-level');
        ?>",
                        'Unread': "<?php 
        esc_html_e('Unread', 'ground-level');
        ?>",
                        'Inbox': "<?php 
        esc_html_e('Inbox', 'ground-level');
        ?>",
                        'Close': "<?php 
        esc_html_e('Close', 'ground-level');
        ?>",
                        'You are all caught up!': "<?php 
        esc_html_e('You are all caught up!', 'ground-level');
        ?>",
                        'ago': "<?php 
        esc_html_e('ago', 'ground-level');
        ?>"
                    },
                    notifications: <?php 
        echo $this->getNotifications();
        ?>,
                    onDismiss: (id) => {
                        window.fetch(
                            '<?php 
        echo admin_url('admin-ajax.php');
        ?>',
                            {
                                method: 'POST',
                                headers: {
                                    'content-type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    id,
                                    action: '<?php 
        echo $ajax->action();
        ?>',
                                    <?php 
        echo $ajax::NONCE_FIELD;
        ?>: '<?php 
        echo $ajax->nonce();
        ?>'
                                }).toString(),
                            }
                        );
                    },
                    theme: JSON.parse('<?php 
        echo wp_json_encode($this->container->get(IPNService::THEME));
        ?>'),
                });
            });
        </script>
        <?php 
        return \ob_get_clean();
    }
    /**
     * Loads the dependencies for the service.
     *
     * @param \GroundLevel\Container\Container $container The container.
     */
    public function load(Container $container) : void
    {
        if ($container->get(IPNService::class)->userHasPermission()) {
            $this->addHooks();
        }
    }
    /**
     * Renders the component.
     */
    public function render() : void
    {
        echo $this->getHtml();
    }
}
