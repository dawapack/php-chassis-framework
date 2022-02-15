<?php

declare(strict_types=1);

namespace Chassis\Framework\Configuration;

use Chassis\Framework\Configuration\Exceptions\ConfigurationException;
use League\Config\Configuration as LeagueConfiguration;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;

class Configuration implements ConfigurationInterface
{
    private const LOGGER_COMPONENT_PREFIX = 'application_configuration';
    private const CONFIG_PATH = 'config';
    private const SCHEMAS_CONFIG_PATH = 'config/Schemas';

    private LeagueConfiguration $configuration;
    private LoggerInterface $logger;
    private string $basePath;

    /**
     * Configuration constructor.
     *
     * @param LeagueConfiguration $configuration
     * @param LoggerInterface $logger
     * @param string $basePath
     * @param array $aliases
     */
    public function __construct(
        LeagueConfiguration $configuration,
        LoggerInterface $logger,
        string $basePath,
        array $aliases = []
    ) {
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->basePath = $basePath;
        // autoload aliases
        if (!empty($aliases)) {
            $this->load($aliases);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        // try to autoload alias
        $alias = $this->getAliasFromKey($key);
        if (!$this->configuration->exists($alias)) {
            $this->load($alias);
        }
        // return key if exists
        return $this->configuration->exists($key)
            ? $this->configuration->get($key)
            : $default;
    }

    /**
     * @param array|string $alias
     */
    public function load($alias): void
    {
        if (!is_array($alias)) {
            $this->loadConfiguration($alias);
            return;
        }
        foreach ($alias as $fileAlias) {
            $this->loadConfiguration($fileAlias);
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getAliasFromKey(string $key): string
    {
        return explode(".", $key)[0];
    }

    /**
     * @param string $fileName
     *
     * @return void
     */
    private function loadConfiguration(string $fileName): void
    {
        if ($this->configuration->exists($fileName)) {
            return;
        }
        $reason = null;
        try {
            $schemas = $this->getSchema($fileName)->getMethod('getSchema')->invoke(null);
            $definitions = $this->getDefinitions($fileName);
            // Add schema
            $this->configuration->addSchema($fileName, $schemas);
            // Add definitions
            $this->configuration->merge([$fileName => $definitions]);
        } catch (ReflectionException $reason) {
            // fault tolerant
        } catch (ConfigurationException $reason) {
            // fault tolerant
        }
        if (!is_null($reason)) {
            $this->logger->warning(
                $reason->getMessage(),
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "_exception",
                    "error" => $reason
                ]
            );
        }
    }

    /**
     * @param string $fileName
     *
     * @return ReflectionClass
     * @throws ReflectionException
     * @throws ConfigurationException
     */
    private function getSchema(string $fileName): ReflectionClass
    {
        // check schema file
        $shortClassName = ucfirst($fileName);
        $schemaFilePath = $this->getFilePath($shortClassName, self::SCHEMAS_CONFIG_PATH);
        if (!file_exists($schemaFilePath)) {
            throw new ConfigurationException("schema for alias '$shortClassName' not found");
        }

        return new ReflectionClass($this->getClassWithNamespace($schemaFilePath));
    }

    /**
     * @param string $filePath
     *
     * @return string
     * @throws ConfigurationException
     */
    private function getClassWithNamespace(string $filePath): string
    {
        $content = file_get_contents($filePath);
        // get namespace
        if (preg_match('/(namespace)(\\s+)([a-z0-9\\\]+?)(\\s*);/iu', $content, $namespace) <= 0) {
            throw new ConfigurationException("namespace not found");
        }
        // Get class name (\\s+)?(class|interface|trait)(\\s+)(\\w+)(\\s+)?
        if (preg_match('/(\\s+)?(class|interface|trait)(\\s+)(\\w+)(\\s+)?/iu', $content, $shortClassName) <= 0) {
            throw new ConfigurationException("short class name not found");
        }

        return $namespace[3] . '\\' . $shortClassName[4];
    }

    /**
     * @param string $fileName
     *
     * @return array
     * @throws ConfigurationException
     */
    private function getDefinitions(string $fileName): array
    {
        $definitionsFilePath = $this->getFilePath($fileName, self::CONFIG_PATH);
        // check definitions file
        if (!file_exists($definitionsFilePath)) {
            throw new ConfigurationException("definitions file for alias '$fileName' not found");
        }

        return require $definitionsFilePath;
    }

    /**
     * @param string $fileName
     * @param string $path
     *
     * @return string
     */
    private function getFilePath(string $fileName, string $path): string
    {
        return sprintf("%s/%s/%s.php", $this->basePath, $path, $fileName);
    }
}
