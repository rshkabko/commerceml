<?php

namespace Flamix\CommerceML\Operations;

use ZipArchive;

/** A folder under FLAMIX_LOGS_PATH/commerceml/ that holds the exchanged files. */
class Files
{
    private string $working_dir;

    public function __construct(string $working_dir)
    {
        $this->working_dir = $working_dir;
    }

    /**
     * Creates and returns FLAMIX_LOGS_PATH/commerceml/<dir>/.
     *
     * @throws \Exception
     */
    public static function exchange(string $dir = ''): Files
    {
        if (!defined('FLAMIX_LOGS_PATH'))
            throw new \Exception('Please, define FLAMIX_LOGS_PATH by correct Init Flamix Plugin!');

        return tap(new Files(FLAMIX_LOGS_PATH . '/commerceml/' . (!empty($dir) ? $dir . '/' : '')), function ($instance) {
            $instance->createDirectory();
        });
    }

    public function getPath(string $dir = ''): string
    {
        return $this->working_dir . $dir;
    }

    /** Overwrites the file, so a second mode=file with the same name starts it over. */
    public function create(string $name, string $content = ''): Files
    {
        file_put_contents($this->getPath($name), $content);
        return $this;
    }

    /** @throws \Exception */
    public function content(string $file = ''): string
    {
        if (!file_exists($this->getPath($file)))
            throw new \Exception('File doesnt exist, path: ' . $this->getPath($file));

        return file_get_contents($this->getPath($file));
    }

    public function translatedContent(string $file = '', string $lang = 'en'): string
    {
        $content = $this->content($file);

        return $content;
    }

    public function createDirectory(string $dir = ''): Files
    {
        @mkdir($this->getPath($dir), 0755, true);
        return $this;
    }

    /** Deletes the files in the folder and the folder itself. */
    public function clearDirectory(string $dir = ''): Files
    {
        $dirPath = $this->getPath($dir);
        if (!is_dir($dirPath))
            throw new \InvalidArgumentException($dirPath . ' must be a directory');

        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
            $dirPath .= '/';

        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            is_dir($file) ? $this->deleteDir($file) : unlink($file);
        }
        rmdir($dirPath);
        return $this;
    }

    public function deleteFile(string $filename = ''): Files
    {
        @unlink($this->getPath($filename));
        return $this;
    }

    /** Appends php://input to the file and ends the request with success. */
    public function uploadBinary(string $file = ''): void
    {
        $file_data = file_get_contents("php://input");
        $file_data_length = strlen($file_data);

        if (($file_data ?? false) !== false) {
            if ($fp = fopen($this->getPath($file), "ab")) {
                $result = fwrite($fp, $file_data);
                commerceml_response_by_type(
                    'success',
                    ($result === $file_data_length) ? 'File uploaded!' : 'Error when file upload, file has different space!'
                );
            }
        }
    }

    /** File names in the folder that contain the substring, keyed as scandir() returned them. */
    public function find(string $file_pattern): array
    {
        $files = scandir($this->working_dir);
        foreach ($files as $file_key => $file_value)
            if (!str_contains($file_value, $file_pattern))
                unset($files[$file_key]);

        return $files;
    }

    /** Whether any file name contains the substring: .zip, rests.xml, .xml. */
    public function exist(string $file_pattern): bool
    {
        return (bool)count($this->find($file_pattern));
    }

    /** @throws \Exception */
    public function extract(string $filename): Files
    {
        $zip = new ZipArchive;
        $res = $zip->open($this->getPath($filename));
        if ($res !== true)
            throw new \Exception('Error when extract zip!');

        // Only flat .xml entries: an archive could otherwise drop .php files or ../ paths into the folder.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = basename((string) $zip->getNameIndex($i));
            if (preg_match('/^[\w.-]+\.xml$/i', $name))
                file_put_contents($this->getPath($name), $zip->getFromIndex($i));
        }
        $zip->close();

        return $this;
    }
}