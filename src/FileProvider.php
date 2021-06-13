<?php

namespace kosuha606\VirtualModelProviders;

use kosuha606\VirtualModel\VirtualModelProvider;
use RuntimeException;

class FileProvider extends VirtualModelProvider
{
    public const FILES = 'files';

    /**
     * @return string
     */
    public function type(): string
    {
        return self::FILES;
    }

    /**
     * @param string $modelClass
     * @param string $path
     * @param string|null $regexp
     * @return array
     */
    public function directoryList(string $modelClass, string $path, ?string $regexp = null): array
    {
        $dirList = [];

        $directory = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $info) {
            if ($regexp && !preg_match($regexp, $info->getPathname())) {
                continue;
            }

            $dirList[] = $info->getPathname();
        }

        return $dirList;
    }

    /**
     * @param $modelClass
     * @param $fileName
     * @param $toSavePath
     * @param array $availableExtensions
     * @return string|null
     */
    public function uploadTo(
        string $modelClass,
        string $fileName,
        string $toSavePath,
        array $availableExtensions = []
    ): ?string
    {
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
    }
}
