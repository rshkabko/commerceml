<?php

namespace Flamix\CommerceML\Operations;

class Files
{
    private string $working_dir;

    /**
     * @param string $working_dir Pass when init plugin
     */
    public function __construct(string $working_dir)
    {
        $this->working_dir = $working_dir;
    }

    /**
     * Init exchange dir and you can work...
     * Files::exchange()->getPath()
     *
     * @param string $dir Can set sub folder, ex: import, upload, etc
     * @return Files
     * @throws \Exception
     */
    public static function exchange(string $dir = ''): Files
    {
        if (!defined('FLAMIX_EXCHANGE_DIR_PATH'))
            throw new \Exception('Please, define plugin path by const FLAMIX_EXCHANGE_DIR_PATH!');

        return tap(new Files(FLAMIX_EXCHANGE_DIR_PATH . '/files/exchange/' . (!empty($dir) ? $dir . '/' : '')), function ($instance) {
            $instance->createDirectory();
        });
    }

    /**
     * Return path
     *
     * @param string $dir
     * @return string
     */
    public function getPath(string $dir = ''): string
    {
        return $this->working_dir . $dir;
    }

    /**
     * @param string $name
     * @param string $content
     * @return $this
     */
    public function create(string $name, string $content = ''): Files
    {
        file_put_contents($this->getPath($name), $content);
        return $this;
    }

    public function content(string $file = ''): string
    {
        // TODO: Check if file exist
        return file_get_contents($this->getPath($file));
    }

    /**
     * Create new directory
     *
     * @param string $dir Sub folder
     * @return $this
     */
    public function createDirectory(string $dir = ''): Files
    {
        @mkdir($this->getPath($dir), 0755, true);
        return $this;
    }

    /**
     * Delete directory with files
     *
     * @param string $dir
     * @return $this
     */
    public function clearDirectory(string $dir = ''): Files
    {
        $dirPath = $this->getPath($dir);
        if (!is_dir($dirPath))
            throw new InvalidArgumentException("$dirPath must be a directory");

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

    /**
     * Upload file when its came from BODY like binary
     *
     * @param string $file
     * @return void
     */
    public function uploadBinary(string $file = '')
    {
        $file_data = file_get_contents("php://input");
        $file_data_length = strlen($file_data);

        if (($file_data ?? false) !== false) {
            if ($fp = fopen($this->getPath($file), "ab")) {
                $result = fwrite($fp, $file_data);

                if ($result === $file_data_length)
                    commerceml_response_by_type('success', 'File uploaded!');
                else
                    commerceml_response_by_type('success', 'Error when file upload, file has different space!');
            }
        }
    }

    public function exist(string $file_pattern): bool
    {
        $files = scandir($this->working_dir);
        foreach ($files as $file)
            if (str_contains($file, $file_pattern))
                return true;

        return false;
    }

    public function extract(string $filename): Files
    {
        $zip = new \ZipArchive;
        $res = $zip->open($this->getPath($filename));
        if ($res !== true)
            throw new \Exception('Error when extract zip!');

        $zip->extractTo($this->getPath());
        $zip->close();

        return $this;
    }
}