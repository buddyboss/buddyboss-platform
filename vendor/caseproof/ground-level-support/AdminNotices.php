<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
/**
 * Manages notices in the WordPress admin area.
 *
 * Provides a standardized way to queue, render, and dismiss admin notices.
 * Notices are stored in options and persist until dismissed via AJAX.
 */
class AdminNotices
{
    use Hookable;
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const INFO = 'info';
    /**
     * The prefix used for option keys and AJAX actions.
     *
     * @var string
     */
    private string $prefix;
    /**
     * The capability required to view and dismiss notices.
     *
     * @var string
     */
    private string $capability;
    /**
     * Constructor.
     *
     * @param string $prefix     The prefix used for option keys and AJAX actions.
     * @param string $capability The capability required to view and dismiss notices.
     */
    public function __construct(string $prefix, string $capability = 'manage_options')
    {
        $this->prefix = $prefix;
        $this->capability = $capability;
    }
    /**
     * Configure WordPress hooks.
     *
     * @return array<Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_ACTION, 'admin_notices', [$this, 'render']), new Hook(Hook::TYPE_ACTION, 'wp_ajax_' . $this->ajaxAction(), [$this, 'ajaxDismiss']), new Hook(Hook::TYPE_ACTION, 'admin_enqueue_scripts', [$this, 'enqueueScripts'])];
    }
    /**
     * Stores a notice in an option for rendering on the next admin page load.
     *
     * If a notice with the same key already exists, it is overwritten.
     *
     * @param string $key     Unique notice identifier.
     * @param string $message The notice message.
     * @param string $type    The notice type (success, warning, error, info).
     * @param array  $actions Action buttons.
     */
    public function notice(string $key, string $message, string $type = self::INFO, array $actions = []) : void
    {
        $this->store($key, $message, $type, $actions, \false);
    }
    /**
     * Stores a flash notice that is automatically removed after being rendered once.
     *
     * @param string $key     Unique notice identifier.
     * @param string $message The notice message.
     * @param string $type    The notice type (success, warning, error, info).
     * @param array  $actions Action buttons.
     */
    public function flash(string $key, string $message, string $type = self::INFO, array $actions = []) : void
    {
        $this->store($key, $message, $type, $actions, \true);
    }
    /**
     * Removes a notice by key.
     *
     * @param string $key The notice key to remove.
     */
    public function remove(string $key) : void
    {
        $notices = $this->getAll();
        if (!isset($notices[$key])) {
            return;
        }
        unset($notices[$key]);
        $this->persist($notices);
    }
    /**
     * Renders all queued notices. Hooked to admin_notices.
     *
     * Persistent notices remain until dismissed via AJAX.
     * Flash notices are removed immediately after rendering.
     */
    public function render() : void
    {
        if (!current_user_can($this->capability)) {
            return;
        }
        $notices = $this->getAll();
        if (empty($notices)) {
            return;
        }
        $nonce = wp_create_nonce($this->ajaxAction());
        $flashKeys = [];
        foreach ($notices as $key => $notice) {
            $this->renderSingleNotice($notice['message'] ?? '', $notice['type'] ?? self::INFO, $notice['actions'] ?? [], (string) $key, $nonce, !empty($notice['flash']));
            if (!empty($notice['flash'])) {
                $flashKeys[] = $key;
            }
        }
        // Remove flash notices after rendering.
        if (!empty($flashKeys)) {
            foreach ($flashKeys as $key) {
                unset($notices[$key]);
            }
            $this->persist($notices);
        }
    }
    /**
     * Stores a notice in the option.
     *
     * @param string  $key     Unique notice identifier.
     * @param string  $message The notice message.
     * @param string  $type    The notice type.
     * @param array   $actions Action buttons.
     * @param boolean $flash   Whether this notice should be removed after rendering.
     */
    private function store(string $key, string $message, string $type, array $actions, bool $flash) : void
    {
        $notices = $this->getAll();
        if (!\in_array($type, [self::SUCCESS, self::WARNING, self::ERROR, self::INFO], \true)) {
            $type = self::INFO;
        }
        $notices[$key] = ['message' => $message, 'type' => $type, 'actions' => $actions, 'flash' => $flash];
        $this->persist($notices);
    }
    /**
     * Retrieves all stored notices.
     *
     * @return array
     */
    private function getAll() : array
    {
        $notices = get_option($this->optionKey(), []);
        return \is_array($notices) ? $notices : [];
    }
    /**
     * Persists notices to the database, or deletes the option if empty.
     *
     * @param array $notices The notices to persist.
     */
    private function persist(array $notices) : void
    {
        if (empty($notices)) {
            delete_option($this->optionKey());
        } else {
            update_option($this->optionKey(), $notices, \false);
        }
    }
    /**
     * AJAX handler: removes a specific notice from the option.
     */
    public function ajaxDismiss() : void
    {
        check_ajax_referer($this->ajaxAction());
        if (!current_user_can($this->capability)) {
            wp_send_json_error(null, 403);
        }
        $key = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));
        if (empty($key)) {
            wp_send_json_error();
        }
        $this->remove($key);
        wp_send_json_success();
    }
    /**
     * Renders a single notice HTML element.
     *
     * @param string  $message The notice message.
     * @param string  $type    The notice type.
     * @param array   $actions Action buttons.
     * @param string  $key     The notice key.
     * @param string  $nonce   The nonce for AJAX dismiss.
     * @param boolean $flash   Whether this is a flash notice.
     */
    private function renderSingleNotice(string $message, string $type, array $actions, string $key, string $nonce, bool $flash = \false) : void
    {
        if (empty($message)) {
            return;
        }
        if ($flash) {
            \printf('<div class="notice notice-%s"><p>', esc_attr($type));
        } else {
            \printf('<div class="notice notice-%s is-dismissible grdlvl-notice"' . ' data-grdlvl-key="%s" data-grdlvl-nonce="%s" data-grdlvl-action="%s"><p>', esc_attr($type), esc_attr($key), esc_attr($nonce), esc_attr($this->ajaxAction()));
        }
        echo wp_kses_post($message);
        foreach ($actions as $action) {
            if (!empty($action['url']) && !empty($action['label'])) {
                \printf(' <a href="%s" class="button button-secondary" target="_blank">%s</a>', esc_url($action['url']), esc_html($action['label']));
            }
        }
        echo '</p></div>';
    }
    /**
     * Enqueues the inline dismiss script on admin pages.
     */
    public function enqueueScripts() : void
    {
        $handle = "{$this->prefix}-grdlvl-admin-notices";
        wp_register_script($handle, \false);
        wp_add_inline_script($handle, <<<JS
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.grdlvl-notice .notice-dismiss');
    if (!btn) return;
    var notice = btn.closest('.grdlvl-notice');
    var data = new FormData();
    data.append('action', notice.dataset.grdlvlAction);
    data.append('key', notice.dataset.grdlvlKey);
    data.append('_wpnonce', notice.dataset.grdlvlNonce);
    fetch(ajaxurl, { method: 'POST', body: data });
});
JS
);
        wp_enqueue_script($handle);
    }
    /**
     * Builds the option key.
     *
     * @return string
     */
    private function optionKey() : string
    {
        return $this->prefix . '_admin_notices';
    }
    /**
     * Returns the AJAX action name.
     *
     * @return string
     */
    private function ajaxAction() : string
    {
        return $this->prefix . '_dismiss_admin_notice';
    }
}
