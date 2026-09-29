<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

use BuddyBossPlatform\GroundLevel\InProductNotifications\Util as IPNUtil;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
use BuddyBossPlatform\GroundLevel\Support\Str;
/**
 * View service for rendering the IPN inbox.
 */
class View
{
    use Hookable;
    /**
     * The IPN utility service.
     *
     * @var IPNUtil
     */
    protected IPNUtil $util;
    /**
     * The store service.
     *
     * @var Store
     */
    protected Store $store;
    /**
     * The ajax service.
     *
     * @var Ajax
     */
    protected Ajax $ajax;
    /**
     * The menu slug for the main admin menu item.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_MENU_SLUG
     * @var    string
     */
    protected string $menuSlug;
    /**
     * The hook name for rendering the inbox.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_RENDER_HOOK
     * @var    string
     */
    protected string $renderHook;
    /**
     * The file path for assets.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_FILE
     * @var    string
     */
    protected string $file;
    /**
     * The theme configuration array.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_THEME
     * @var    array
     */
    protected array $theme;
    /**
     * Constructor.
     *
     * @param IPNUtil $util       The IPN utility service.
     * @param Store   $store      The store service.
     * @param Ajax    $ajax       The ajax service.
     * @param string  $menuSlug   The menu slug for the main admin menu item.
     * @param string  $renderHook The hook name for rendering the inbox.
     * @param string  $file       The file path for assets.
     * @param array   $theme      The theme configuration array.
     */
    public function __construct(IPNUtil $util, Store $store, Ajax $ajax, string $menuSlug, string $renderHook, string $file, array $theme)
    {
        $this->util = $util;
        $this->store = $store;
        $this->ajax = $ajax;
        $this->menuSlug = $menuSlug;
        $this->renderHook = $renderHook;
        $this->file = $file;
        $this->theme = $theme;
        if ($this->util->userHasPermission()) {
            $this->addHooks();
        }
    }
    /**
     * Appends the count indicator to the main admin menu item.
     *
     * If the MENU_SLUG is not set, no indicator will be appended.
     */
    public function appendCountToMainMenuItem() : void
    {
        global $menu;
        if ($this->menuSlug) {
            foreach (\array_reverse($menu, \true) as $index => $menuItem) {
                if (($menuItem[2] ?? '') === $this->menuSlug) {
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
        return [new Hook(Hook::TYPE_ACTION, $this->renderHook, [$this, 'render']), new Hook(Hook::TYPE_ACTION, 'admin_enqueue_scripts', [$this, 'enqueue']), new Hook(Hook::TYPE_ACTION, 'admin_menu', [$this, 'appendCountToMainMenuItem'], 100), new Hook(Hook::TYPE_ACTION, 'admin_print_footer_scripts', [$this, 'maybeDequeueScript'], 5)];
    }
    /**
     * Checks if the inbox view has been rendered.
     *
     * @return boolean
     */
    public function didRender() : bool
    {
        return did_action($this->renderHook) > 0;
    }
    /**
     * Enqueues the service scripts.
     */
    public function enqueue() : void
    {
        $path = 'assets/ipn-inbox.js';
        wp_enqueue_script($this->getScriptHandle(), plugin_dir_url($this->file) . $path, [], \filemtime(\dirname(__FILE__, 2) . "/{$path}"), \true);
    }
    /**
     * Dequeues the service script if the inbox view has not been rendered.
     *
     * This callback is run immediately prior to the output of the footer scripts,
     * if the view has not been rendered the script is dequeued (saving an HTTP request).
     */
    public function maybeDequeueScript() : void
    {
        if (!$this->didRender()) {
            wp_dequeue_script($this->getScriptHandle());
        }
    }
    /**
     * Retrieves the HTML output for the menu count indicator.
     *
     * @return string
     */
    protected function getMenuCountIndicatorHtm() : string
    {
        $count = \count($this->store->fetch()->notifications(\false, Store::FILTER_UNREAD));
        if (empty($count)) {
            return '';
        }
        $countI18n = number_format_i18n($count);
        $unreadText = \sprintf(
            // Translators: %s unread notification count.
            _n('%s unread notification', '%s unread notifications', $count, 'ground-level'),
            $countI18n
        );
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
        return wp_json_encode($this->store->fetch()->notifications());
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
        $prefix = $this->util->prefixId();
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
    public function getRootElementId() : string
    {
        return $this->util->prefixId('root');
    }
    /**
     * Retrievs the <script> tag used to create the inbox React component.
     *
     * @return string
     */
    public function getScript() : string
    {
        $namespace = Str::toCamelCase($this->util->prefixId('inbox'));
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
        echo $this->ajax->action();
        ?>',
                                    <?php 
        echo Ajax::NONCE_FIELD;
        ?>: '<?php 
        echo $this->ajax->nonce();
        ?>'
                                }).toString(),
                            }
                        );
                    },
                    theme: JSON.parse('<?php 
        echo wp_json_encode($this->theme);
        ?>'),
                });
            });
        </script>
        <?php 
        return \ob_get_clean();
    }
    /**
     * Retrieves the handle for the service script.
     *
     * @return string
     */
    public function getScriptHandle() : string
    {
        return Str::toKebabCase($this->util->prefixId('inbox'));
    }
    /**
     * Renders the component.
     */
    public function render() : void
    {
        echo $this->getHtml();
    }
}
