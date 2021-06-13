<?php

namespace app\Provider;

use kosuha606\VirtualModel\VirtualModelProvider;

class SettingsProvider extends VirtualModelProvider
{
    public const SETTINGS = 'settings';

    private string $settingsPath;
    private string $defaultSettingsPath;

    /**
     * @param string $defaultSettingsPath
     * @param string $customSettingsPath
     */
    public function __construct(string $defaultSettingsPath, string $customSettingsPath)
    {
        $this->settingsPath = $customSettingsPath;
        $this->defaultSettingsPath = $defaultSettingsPath;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return self::SETTINGS;
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
    public function getSettings(): array
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
