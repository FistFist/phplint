<?php
declare(strict_types=1);

namespace PhpLint\Linter;

use ArrayIterator;
use PhpLint\Configuration\Configuration;
use PhpLint\Configuration\ConfigurationFileReader;
use PhpLint\Configuration\ConfigurationLoader;

class FileTraverser extends ArrayIterator
{
    /**
     * @var Configuration|null
     */
    private $extraConfig;

    /**
     * @var ConfigurationLoader
     */
    private $configLoader;

    /**
     * @var ConfigurationFileReader
     */
    private $configFileReader;

    /**
     * @var array[]
     */
    private $directoryConfigs = [];

    /**
     * @param string[] $filePaths
     * @param ConfigurationLoader $configLoader
     * @param Configuration|null $extraConfig
     */
    public function __construct(array $filePaths, ConfigurationLoader $configLoader, Configuration $extraConfig = null)
    {
        parent::__construct($filePaths);

        if ($extraConfig && !$extraConfig->isEmpty()) {
            $this->extraConfig = $extraConfig;
        }
        $this->configLoader = $configLoader;
        $this->configFileReader = new ConfigurationFileReader();

        // Determine the config of the first element of the iterator
        $this->findPathRootConfig($this->current());
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        parent::next();

        if ($this->valid()) {
            // Find any new configs for the new item
            $this->findPathRootConfig($this->current());
        }
    }

    /**
     * Returns the effective configuration for the current file path of this iterator or null, if the iterator
     * is invalid.
     *
     * @return Configuration|null
     */
    public function getCurrentFileConfig()
    {
        if (!$this->valid()) {
            return null;
        }

        // Use the configuration for the current file's directory and add the extra config to it
        $filePath = $this->current();
        $directoryPath = dirname($filePath);
        $fileConfig = $this->directoryConfigs[$directoryPath];
        if ($this->extraConfig) {
            $fileConfig = $this->extraConfig->mergeOntoConfig($fileConfig);
        }

        return $fileConfig;
    }

    /**
     * @param string $filePath
     * @return Configuration
     */
    protected function findPathRootConfig(string $filePath): Configuration
    {
        // Make sure to start in a directory
        $directoryPath = $filePath;
        if (is_file($directoryPath)) {
            $directoryPath = dirname($directoryPath);
        }

        // Find all new configurations while traversing the file system up to the root or the first known config
        $cachedRootConfig = null;
        $directoryConfigData = [];
        while (mb_strlen($directoryPath) > 1) {
            // Check for cached directory config
            if (isset($this->directoryConfigs[$directoryPath])) {
                $cachedRootConfig = $this->directoryConfigs[$directoryPath];

                break;
            }

            // Check the directory for a phplint config
            $configValues = $this->configFileReader->readDirectoryConfig($directoryPath);
            $directoryConfigData[]  = [
                $directoryPath,
                $configValues,
            ];
            if (isset($configValues[Configuration::KEY_ROOT]) && $configValues[Configuration::KEY_ROOT] === true) {
                break;
            }

            // Move up on directory
            $directoryPath = dirname($directoryPath);
        }

        // Build a configuration tree, starting with the root configuration, since 'closer' configurations must always
        // be applied last
        $pathConfig = $cachedRootConfig ?: new Configuration([]);
        foreach (array_reverse($directoryConfigData) as list($configPath, $configValues)) {
            if (count($configValues) > 0) {
                $directoryConfig = $this->configLoader->loadData($configValues)->mergeOntoConfig($pathConfig);
            } else {
                // No config changes, hence just reuse the previous config (or an empty config as fallback)
                $directoryConfig = $pathConfig;
            }
            $this->directoryConfigs[$configPath] = $directoryConfig;
            $pathConfig = $directoryConfig;
        }

        return $pathConfig;
    }
}
