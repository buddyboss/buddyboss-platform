<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Insights\Services;

use Closure;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\View as IPNView;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\ProductInsights;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
use BuddyBossPlatform\GroundLevel\Support\Str;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
class RestApi
{
    use Hookable;
    /**
     * The base file path.
     *
     * @inject \GroundLevel\Insights\InsightsServiceProvider::PARAM_FILE
     * @var    string
     */
    protected string $baseFilePath;
    /**
     * The callback to check if the IPN view has rendered.
     *
     * @var \Closure(): \GroundLevel\InProductNotifications\Services\View
     */
    protected Closure $ipnViewInstance;
    /**
     * The REST API namespace.
     *
     * @inject \GroundLevel\Insights\InsightsServiceProvider::PARAM_REST_NAMESPACE
     * @var    string
     */
    protected string $namespace;
    /**
     * The Net Promoter Score service instance.
     *
     * @var \GroundLevel\Insights\Services\NetPromoterScore
     */
    protected NetPromoterScore $netPromoterScore;
    /**
     * The insights service prefix.
     *
     * @inject \GroundLevel\Insights\InsightsServiceProvider::PARAM_PREFIX
     * @var    string
     */
    protected string $prefix;
    /**
     * The ProductInsights API instance.
     *
     * @var \GroundLevel\Mothership\Api\Request\ProductInsights
     */
    protected ProductInsights $productInsights;
    /**
     * The product slug.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_PRODUCT_SLUG
     * @var    string
     */
    protected string $productSlug;
    /**
     * The user capability to check for.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_USER_CAPABILITY
     * @var    string
     */
    protected string $userCapability;
    /**
     * Constructor.
     *
     * @param \GroundLevel\Insights\Services\NetPromoterScore     $netPromoterScore The NPS service.
     * @param \GroundLevel\Mothership\Api\Request\ProductInsights $productInsights  The ProductInsights API instance.
     * @param \Closure                                            $ipnViewInstance  The IPN view instance callback.
     * @param string                                              $productSlug      The product slug.
     * @param string                                              $namespace        The REST API namespace.
     * @param string                                              $userCapability   The user capability.
     * @param string                                              $prefix           The prefix.
     * @param string                                              $baseFilePath     The base file path.
     */
    public function __construct(NetPromoterScore $netPromoterScore, ProductInsights $productInsights, Closure $ipnViewInstance, string $productSlug, string $namespace, string $userCapability, string $prefix, string $baseFilePath)
    {
        $this->netPromoterScore = $netPromoterScore;
        $this->productInsights = $productInsights;
        $this->ipnViewInstance = $ipnViewInstance;
        $this->productSlug = $productSlug;
        $this->namespace = $namespace;
        $this->userCapability = $userCapability;
        $this->prefix = $prefix;
        $this->baseFilePath = $baseFilePath;
    }
    /**
     * Configures the hooks for the service.
     *
     * @return array<int, Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_ACTION, 'in_admin_footer', [$this, 'enqueue']), new Hook(Hook::TYPE_ACTION, 'rest_api_init', [$this, 'registerRoutes'])];
    }
    /**
     * Registers the REST API routes.
     */
    public function registerRoutes() : void
    {
        // Submit/update NPS (handles both initial submission and feedback update).
        register_rest_route($this->namespace, '/nps/submit', ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'handleSubmission'], 'permission_callback' => [$this, 'checkPermissions'], 'args' => ['score' => ['required' => \true, 'validate_callback' => function ($param) {
            return \is_numeric($param) && $param >= 0 && $param <= 10;
        }], 'feedback' => ['required' => \false, 'type' => 'string'], 'follow_up' => ['required' => \false, 'type' => 'boolean']]]);
    }
    /**
     * Checks if the current user has permission to submit NPS.
     *
     * @return boolean
     */
    public function checkPermissions() : bool
    {
        return current_user_can($this->userCapability);
    }
    /**
     * Gets the IPN view instance.
     *
     * @return \GroundLevel\InProductNotifications\Services\View
     */
    protected function getIpnView() : IPNView
    {
        $get = $this->ipnViewInstance;
        return $get();
    }
    /**
     * Gets the i18n translations for JavaScript.
     *
     * @return array<string, string>
     */
    protected function getI18n() : array
    {
        $i18n = ['promoterQuestion' => __('What is the main reason for your score?', 'ground-level'), 'detractorQuestion' => __('What could we do to improve your score?', 'ground-level'), 'feedbackLabel' => __('Feedback', 'ground-level'), 'feedbackHelp' => __('Please share your thoughts (optional)', 'ground-level'), 'followUpLabel' => __('Would you be interested in having someone from the team reach out for a follow up?', 'ground-level'), 'cancelButton' => __('Cancel', 'ground-level'), 'submitButton' => __('Submit', 'ground-level'), 'submitting' => __('Submitting...', 'ground-level'), 'errorTitle' => __('Error', 'ground-level'), 'configError' => __('Configuration error: REST API URL or nonce not available.', 'ground-level'), 'submitError' => __('An error occurred while submitting your score. Please try again.', 'ground-level'), 'feedbackError' => __('An error occurred while submitting your feedback. Please try again.', 'ground-level'), 'networkError' => __('Network error: Unable to submit. Please check your connection and try again.', 'ground-level')];
        return \array_map('esc_js', $i18n);
    }
    /**
     * Enqueues the frontend script.
     */
    public function enqueue() : void
    {
        if ($this->getIpnView()->didRender()) {
            $path = 'assets/insights.js';
            $handle = Str::toKebabCase($this->prefix . '_insights');
            $scriptUrl = plugin_dir_url($this->baseFilePath) . $path;
            $dependencies = ['wp-element', 'wp-components', 'wp-api-fetch'];
            wp_enqueue_script($handle, $scriptUrl, $dependencies, \filemtime(plugin_dir_path($this->baseFilePath) . $path), \true);
            $i18nJson = wp_json_encode($this->getI18n());
            wp_add_inline_script($handle, 'window.createGrdLvlInsights(
                    {
                        notificationId: "' . esc_js($this->netPromoterScore::NOTIFICATION_ID) . '",
                        rootElementId: "' . esc_js($this->getIpnView()->getRootElementId()) . '",
                        endpointUrl: "' . esc_url_raw(rest_url($this->namespace . '/nps/submit')) . '",
                        restNonce: "' . wp_create_nonce('wp_rest') . '",
                        i18n: ' . $i18nJson . '
                    }
                );');
        }
    }
    /**
     * Handles NPS submission (both initial score and feedback update).
     *
     * @param  WP_REST_Request $request The REST request.
     * @return WP_REST_Response
     */
    public function handleSubmission(WP_REST_Request $request) : WP_REST_Response
    {
        $score = $request->get_param('score');
        $feedback = $request->get_param('feedback');
        $followUp = $request->get_param('follow_up');
        if (\is_null($score)) {
            return new WP_REST_Response(['message' => __('Invalid score', 'ground-level')], 422);
        }
        $user = wp_get_current_user();
        if (!$user || !$user->ID) {
            return new WP_REST_Response(['message' => __('Invalid user', 'ground-level')], 401);
        }
        $data = [
            'email' => $user->user_email,
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            'score' => (int) $score,
        ];
        // Add feedback and follow_up if provided (feedback update).
        if (!\is_null($feedback)) {
            $data['feedback'] = sanitize_textarea_field(\substr((string) $feedback, 0, 3072));
        }
        if (!\is_null($followUp)) {
            $data['follow_up'] = (bool) $followUp;
        }
        $response = $this->productInsights->nps($this->productSlug, $data);
        if ($response->isError()) {
            return new WP_REST_Response(['message' => $response->getMessage(), 'errors' => $response->getErrors()], $response->statusCode);
        }
        $this->netPromoterScore->recordSubmission(!\is_null($feedback) || !\is_null($followUp));
        return new WP_REST_Response(['success' => \true], 200);
    }
}
