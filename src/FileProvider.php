<?php

namespace kosuha606\VirtualModelProviders;

use kosuha606\VirtualModel\VirtualModelProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class FileProvider extends VirtualModelProvider
{
    public const FILES = 'files';

    public function __construct()
    {
        parent::__construct();
        $this->specifyActions([
            'type' => function () {
                return self::FILES;
            },

            'directoryList' => function (
                string $modelClass,
                string $path,
                ?string $regexp = null
            ): array {
                $dirList = [];

                $directory = new RecursiveDirectoryIterator($path);
                $iterator = new RecursiveIteratorIterator($directory);

                foreach ($iterator as $info) {
                    if ($regexp && !preg_match($regexp, $info->getPathname())) {
                        continue;
                    }

                    $dirList[] = $info->getPathname();
                }

                return $dirList;
            },

            'uploadTo' => function (
                string $modelClass,
                string $fileName,
                string $toSavePath,
                array $availableExtensions = []
            ): ?string {
                if (!isset($_FILES[$fileName])) {
                    throw new RuntimeException("File with name $fileName was not found");
                }

                $tmpFile = $_FILES[$fileName];
                $fileName = $tmpFile['name'];
                $fileInfo = pathinfo($fileName);
                $fileExtension = $fileInfo['extension'];

                if (!in_array($fileExtension, $availableExtensions, true)) {
                    return null;
                }

                $toSavePath .= '.' . $fileExtension;

                if (!move_uploaded_file($tmpFile['tmp_name'], $toSavePath)) {
                    throw new RuntimeException('File was not uploaded');
                }

                return $toSavePath;
            },
        ], true);
    }
}
