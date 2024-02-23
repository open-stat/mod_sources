<?php
namespace Core2\Mod\Sources;


/**
 *
 */
class Model extends \Common {

    private static string $sources_dir = '';


    /**
     * @param string    $source_name
     * @param \DateTime $date
     * @param string    $file_name
     * @param string    $contents
     * @return string
     * @throws \Zend_Config_Exception
     */
    public function saveSourceFile(string $source_name, \DateTime $date, string $file_name, string $contents): string {

        $source_folder = $this->createSourceFolder($source_name, $date);
        $file_path     = "{$source_folder}/{$file_name}";

        if ( ! file_exists($file_path)) {
            file_put_contents($file_path, $contents);
        }

        return $file_path;
    }


    /**
     * @param string    $source_name
     * @param \DateTime $date
     * @param string    $file_name
     * @param string    $type
     * @return string|array
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    public function getSourceFile(string $source_name, \DateTime $date, string $file_name, string $type = 'json'): string|array {

        $source_folder = $this->getSourceFolder($source_name, $date);
        $file_path     = "{$source_folder}/{$file_name}";

        if ( ! file_exists($file_path)) {
            throw new \Exception("Указанный файл не найден: {$file_path}");
        }

        $content = file_get_contents($file_path);

        if ($type == 'json') {
            $content = @json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Ошибка файл содержит некорректный JSON формат: {$file_path}");
            }
        }


        return $content;
    }


    /**
     * @param string         $source_name
     * @param \DateTime|null $date
     * @return string
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    private function createSourceFolder(string $source_name, \DateTime $date = null): string {

        $sources_dir = $this->getSourceDir();
        $sources_dir = mb_substr((string)$sources_dir, 0, 1) != '/'
            ? realpath(DOC_ROOT . $sources_dir)
            : $sources_dir;

        if ( ! is_dir($sources_dir)) {
            throw new \Exception("Указанной директории не существует: {$sources_dir}");
        }

        $sources_dir_path = "{$sources_dir}/{$source_name}";


        $this->createCheckDir($sources_dir_path);


        if (empty($date)) {
            $date = new \DateTime();
        }

        $year             = $date->format('Y');
        $date             = $date->format('Y-m-d');
        $sources_dir_year = "{$sources_dir_path}/{$year}";
        $sources_dir_date = "{$sources_dir_path}/{$year}/$date";


        $this->createCheckDir($sources_dir_year);
        $this->createCheckDir($sources_dir_date);


        return $sources_dir_date;
    }


    /**
     * @param string         $source_name
     * @param \DateTime|null $date
     * @return string
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    private function getSourceFolder(string $source_name, \DateTime $date = null): string {

        $sources_dir = $this->getSourceDir();
        $sources_dir = mb_substr((string)$sources_dir, 0, 1) != '/'
            ? realpath(DOC_ROOT . $sources_dir)
            : $sources_dir;

        if (empty($date)) {
            $date = new \DateTime();
        }

        $year              = $date->format('Y');
        $date_day          = $date->format('Y-m-d');
        $sources_name_path = "{$sources_dir}/{$source_name}";


        return "{$sources_name_path}/{$year}/{$date_day}";
    }


    /**
     * @return string
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    private function getSourceDir(): string {

        if ( ! self::$sources_dir) {
            self::$sources_dir = $this->getModuleConfig('sources')?->sources_dir;

            if (empty(self::$sources_dir)) {
                throw new \Exception('Не указан sources_dir');
            }
        }

        return self::$sources_dir;
    }


    /**
     * @param string $dir_path
     * @return void
     * @throws \Exception
     */
    private function createCheckDir(string $dir_path): void {

        if ( ! is_dir($dir_path)) {
            mkdir($dir_path);

            if ( ! is_dir($dir_path)) {
                throw new \Exception("Не удалось создать директорию: {$dir_path}");
            }
        }

        if ( ! is_writable($dir_path)) {
            throw new \Exception("Директория не доступна для записи: {$dir_path}");
        }
    }
}