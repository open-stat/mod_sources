<?php
namespace Core2\Mod\Sources\Sites;

/**
 *
 */
class Model {

    /**
     * @return \Laminas\Config\Config[]
     */
    public function getConfigs(): array {

        $config_dir = __DIR__ . '/../../assets/sources';

        $directory = new \RecursiveDirectoryIterator($config_dir);
        $iterator  = new \RecursiveIteratorIterator($directory);
        $configs   = [];

        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo) {
                if ($item->getExtension() === 'ini') {
                    $name = substr($item->getFilename(), 0, -4);

                    try {
                        $reader = new \Laminas\Config\Reader\Ini();
                        $reader->setProcessSections(true);
                        $reader->setNestSeparator('.');
                        $data = $reader->fromFile($item->getPathname());

                        $config = new \Laminas\Config\Config($data);

                        if (isset($configs[$name])) {
                            $configs[$name . '_' . crc32($item->getPathname())] = $config;
                        } else {
                            $configs[$name] = $config;
                        }

                    } catch (\Exception $e) {
                        echo $e->getMessage() . PHP_EOL;
                    }
                }
            }
        }

        return $configs;
    }
}