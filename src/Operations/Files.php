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
}