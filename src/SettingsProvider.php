<?php

/** @noinspection PhpUnused */

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
        parent::__construct();
        $this->settingsPath = $customSettingsPath;
        $this->defaultSettingsPath = $defaultSettingsPath;
        $this->specifyActions([
            'type' => function() {
                return self::SETTINGS;
            },

            'getDefaultSettings' => function() {
                $defaultSettings = [];

                if (file_exists($this->defaultSettingsPath)) {
                    /** @noinspection PhpIncludeInspection */
                    $defaultSettings = require $this->defaultSettingsPath;
                }

                return $defaultSettings;
            },

            'getSettings' => function() {
                $settingsPath = $this->settingsPath;

                if (!is_file($settingsPath)) {
                    return [];
                }

                $dataJson = file_get_contents($settingsPath);

                return json_decode($dataJson, true);
            },

            'saveSettings' => function($settings) {
                $settingsPath = $this->settingsPath;
                $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE);
                file_put_contents($settingsPath, $settingsJson);
            },
        ], true);
    }
}
