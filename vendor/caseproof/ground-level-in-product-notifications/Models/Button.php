<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Models;

use InvalidArgumentException;
use Stringable;
class Button implements Stringable
{
    /**
     * Button type: Primary.
     */
    public const TYPE_PRIMARY = 'primary';
    /**
     * Button type: Secondary.
     */
    public const TYPE_SECONDARY = 'secondary';
    /**
     * Button type: Link.
     */
    public const TYPE_LINK = 'link';
    /**
     * List of valid button types.
     */
    public const TYPES = [self::TYPE_PRIMARY, self::TYPE_SECONDARY, self::TYPE_LINK];
    /**
     * Button preset: Dismiss.
     */
    public const PRESET_DISMISS = 'dismiss';
    /**
     * List of valid button presets.
     */
    public const PRESETS = [self::PRESET_DISMISS];
    /**
     * The button label.
     *
     * @var string
     */
    public string $label;
    /**
     * The button URL.
     *
     * @var string
     */
    public string $url;
    /**
     * The button type.
     *
     * @var string
     */
    public string $type;
    /**
     * The button target.
     *
     * @var string|null
     */
    public ?string $target;
    /**
     * Button constructor.
     *
     * @param array $rawData The button data.
     *
     * @throws \InvalidArgumentException When an invalid type or preset is provided or when required data is missing.
     */
    public function __construct(array $rawData)
    {
        if (isset($rawData['preset'])) {
            $rawData = \array_merge($this->presetData($rawData['preset']), $rawData);
        }
        $this->type = $rawData['type'] ?? self::TYPE_PRIMARY;
        if (!\in_array($this->type, self::TYPES, \true)) {
            throw new InvalidArgumentException("Invalid type: {$this->type}. Must be one of: " . \implode('|', self::TYPES));
        }
        $this->label = $rawData['label'] ?? '';
        if (empty($this->label)) {
            throw new InvalidArgumentException('Invalid button data: label is required');
        }
        $this->url = $rawData['url'] ?? '';
        if (empty($this->url)) {
            throw new InvalidArgumentException('Invalid button data: url is required');
        }
        $this->target = $rawData['target'] ?? null;
    }
    /**
     * Retrieves the data for a button preset.
     *
     * @param string $preset The preset name.
     *
     * @return array The button data.
     * @throws \InvalidArgumentException When an invalid preset is provided.
     */
    private function presetData(string $preset) : array
    {
        switch ($preset) {
            case self::PRESET_DISMISS:
                return ['type' => self::TYPE_LINK, 'url' => '#notification-dismiss', 'label' => __('Dismiss', 'ground-level')];
            default:
                throw new InvalidArgumentException("Invalid preset: {$preset}. Must be one of: " . \implode('|', self::PRESETS));
        }
    }
    /**
     * Retrieves the HTML for the button.
     *
     * @return string The HTML for the button.
     */
    public function toHtml() : string
    {
        $attrs = ['class' => 'btn btn--' . $this->type, 'href' => $this->url];
        if ($this->target) {
            $attrs['target'] = $this->target;
        }
        $attrs = \array_map(function (string $key, string $value) : string {
            return $key . '="' . esc_attr($value) . '"';
        }, \array_keys($attrs), $attrs);
        return '<a ' . \implode(' ', $attrs) . '>' . esc_html($this->label) . '</a>';
    }
    /**
     * Retrieves the string representation of the button.
     *
     * @return string The string representation of the button.
     */
    public function __toString() : string
    {
        return $this->toHtml();
    }
}
