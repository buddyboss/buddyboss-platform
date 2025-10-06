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
use WP_Error;
class Ajax extends Service implements LoadableDependency
{
    use Hookable;
    /**
     * Error code: nonce errors.
     */
    public const E_NONCE = 'nonce';
    /**
     * Error code: authorization errors.
     */
    public const E_UNAUTHORIZED = 'unauthorized';
    /**
     * Error code: forbidden errors.
     */
    public const E_FORBIDDEN = 'forbidden';
    /**
     * Error code: invalid id supplied.
     */
    public const E_INVALID_ID = 'invalid-id';
    /**
     * The key of the nonce field.
     */
    public const NONCE_FIELD = 'nonce';
    /**
     * Returns the action name used for the AJAX endpoint.
     *
     * @return string The action name, eg: mepr_ipn_dismiss.
     */
    public function action() : string
    {
        return Str::toSnakeCase($this->container->get(IPNService::class)->prefixId('dismiss'));
    }
    /**
     * Configures the hooks for the service.
     *
     * @return array<int, Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_ACTION, 'wp_ajax_' . $this->action(), [$this, 'handleDismiss'])];
    }
    /**
     * Retrieves error data for the given error code.
     *
     * @param  string $code Error code.
     * @return array<WP_Error, int> An array containing the error object and the
     *                              HTTP status code.
     */
    protected function errorData(string $code) : array
    {
        switch ($code) {
            case self::E_NONCE:
                $msg = __('Invalid nonce.', 'ground-level');
                $status = 401;
                break;
            case self::E_UNAUTHORIZED:
                $msg = __('You must log in to perform this action.', 'ground-level');
                $status = 401;
                break;
            case self::E_FORBIDDEN:
                $msg = __('You are not allowed to perform this action.', 'ground-level');
                $status = 403;
                break;
            case self::E_INVALID_ID:
                $msg = __('Missing required parameter: id.', 'ground-level');
                $status = 422;
                break;
            default:
                $msg = __('Unknown error.', 'ground-level');
                $status = 400;
        }
        return [new WP_Error($code, $msg), $status];
    }
    /**
     * Handles the AJAX dismiss action.
     */
    public function handleDismiss() : void
    {
        $verify = wp_verify_nonce(\filter_input(\INPUT_POST, self::NONCE_FIELD, \FILTER_SANITIZE_FULL_SPECIAL_CHARS), $this->nonceAction());
        if (\false === $verify) {
            wp_send_json_error(...$this->errorData(self::E_NONCE));
        }
        if (!\get_current_user()) {
            wp_send_json_error(...$this->errorData(self::E_UNAUTHORIZED));
        }
        if (!current_user_can($this->container->get(IPNService::USER_CAPABILITY))) {
            wp_send_json_error(...$this->errorData(self::E_FORBIDDEN));
        }
        $id = \filter_input(\INPUT_POST, 'id', \FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!$id) {
            wp_send_json_error(...$this->errorData(self::E_INVALID_ID));
        }
        $this->container->get(Store::class)->fetch()->markRead($id)->persist();
        wp_send_json_success(null, 200);
    }
    /**
     * Service load method.
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
     * Returns a nonce string for the AJAX endpoint.
     *
     * @return string
     */
    public function nonce() : string
    {
        return wp_create_nonce($this->nonceAction());
    }
    /**
     * Returns the nonce action.
     *
     * @return string  The nonce action, eg "mepr_ipn_ajax_dismiss".
     */
    public function nonceAction() : string
    {
        return $this->container->get(IPNService::class)->prefixId('ajax_dismiss');
    }
}
