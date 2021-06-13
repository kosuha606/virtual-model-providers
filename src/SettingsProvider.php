<?php

namespace app\Provider;

use kosuha606\VirtualAdmin\Domains\Settings\SettingsVm;
use kosuha606\VirtualAdmin\Domains\Settings\SettingsProviderInterface;
use kosuha606\VirtualModel\VirtualModelProvider;

class SettingsProvider extends VirtualModelProvider implements SettingsProviderInterface
{
    /**
     * @var string
     */
    private $settingsPath;

    /**
     * @var string
     */
    private $defaultSettingsPath;

    /**
     * @param string $defaultSettingsPath
     * @param string $customSettingsPath
     */
    public function __construct(string $defaultSettingsPath, string $customSettingsPath)
    {
        $this->settingsPath = $customSettingsPath;
        $this->defaultSettingsPath = $defaultSettingsPath;
    }

    public function type(): string
    {
        return SettingsVm::KEY;
    }

    /**
     * @return array
     */
    public function getDefaultSettings(): array
    {
        $defaultSettings = [];

        if (file_exists($this->defaultSettingsPath)) {
            $defaultSettings = require $this->defaultSettingsPath;
        }

        return $defaultSettings;
    }

    /**
     * @return array|mixed
     */
    public function getSettings()
    {
        $settingsPath = $this->settingsPath;

        if (!is_file($settingsPath)) {
            return [];
        }

        $dataJson = file_get_contents($settingsPath);

        return json_decode($dataJson, true);
    }

    /**
     * @param mixed $settings
     */
    public function saveSettings($settings): void
    {
        $settingsPath = $this->settingsPath;
        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE);

        file_put_contents($settingsPath, $settingsJson);
    }
}
