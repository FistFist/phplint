<?php
declare(strict_types=1);

namespace PhpLint\Configuration;

use PhpLint\Rules\RuleSeverity;

class Configuration
{
    /**
     * Valid config keys as supported by this class.
     */
    const KEY_EXTENDS = 'extends';
    const KEY_PLUGINS = 'plugins';
    const KEY_ROOT = 'root';
    const KEY_RULES = 'rules';
    const KEY_SETTINGS = 'settings';

    /**
     * @var array
     */
    private $values;

    /**
     * @var Configuration|null
     */
    private $parentConfig = null;

    /**
     * Warning: Never create 'real' Configuration instances directly! Always use a ConfigurationLoader.
     *
     * @param array $values
     * @param Configuration|null $parentConfig
     */
    public function __construct(array $values, Configuration $parentConfig = null)
    {
        $this->values = $values;
        $this->parentConfig = $parentConfig;
    }

    /**
     * @return Configuration|null
     */
    public function getParentConfig()
    {
        return $this->parentConfig;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return (isset($this->values[$key])) ? $this->values[$key] : null;
    }

    /**
     * @return array
     */
    public function getExtends(): array
    {
        return $this->get(self::KEY_EXTENDS) ?: [];
    }

    /**
     * @return array
     */
    public function getPlugins(): array
    {
        return $this->get(self::KEY_PLUGINS) ?: [];
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->get(self::KEY_ROOT) === true;
    }

    /**
     * By default this method only returns the rules whose severity is not 'off'. Pass true as first argument to get
     * all rules.
     *
     * @param bool $allRules
     * @return array
     */
    public function getRules(bool $allRules = false): array
    {
        $rules = $this->get(self::KEY_RULES) ?: [];
        if (!$allRules) {
            $rules = array_filter(
                $rules,
                function ($ruleConfig) {
                    return RuleSeverity::getRuleSeverity($ruleConfig, true) !== RuleSeverity::SEVERITY_OFF;
                }
            );
        }

        return $rules;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->get(self::KEY_SETTINGS) ?: [];
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !isset($this->values[self::KEY_ROOT])
            && count($this->getExtends()) === 0
            && count($this->getPlugins()) === 0
            && count($this->getRules()) === 0
            && count($this->getSettings()) === 0;
    }

    /**
     * Merges the values of this config as well as of the given $baseConfig and uses the resulting data to create a
     * new config instance, which is returned. The values of this config always always take precendence over the
     * $baseConfig's values.
     *
     * @param Configuration $baseConfig
     * @return Configuration
     */
    public function mergeOntoConfig(Configuration $baseConfig): Configuration
    {
        $mergedConfigData = [];

        // Merge the extended configs
        $mergedConfigData[self::KEY_EXTENDS] = array_merge(
            $baseConfig->getExtends(),
            $this->getExtends()
        );

        // Merge the required plugins
        $mergedConfigData[self::KEY_PLUGINS] = array_merge(
            $baseConfig->getPlugins(),
            $this->getPlugins()
        );

        // Set 'root' if at least of the configs is root
        $mergedConfigData[self::KEY_ROOT] = $this->isRoot() || $baseConfig->isRoot();

        // Merge the rules by using the base config's rules as base. Any rules only required by this config are added.
        // If a rule is required in both configs, the severity and rule config found in this config are used. That is,
        // if the base config specifies a rule config while this config only sets the severtiy of the same rule, the
        // original config is preserved and only its severity is changed.
        $mergedRules = $baseConfig->getRules(true);
        foreach ($this->getRules(true) as $ruleId => $ruleConfig) {
            if (!isset($mergedRules[$ruleId])) {
                // Add new rule
                $mergedRules[$ruleId] = $ruleConfig;
            } elseif (is_array($mergedRules[$ruleId]) && is_string($ruleConfig)) {
                // Only change the severity
                $mergedRules[$ruleId][0] = $ruleConfig;
            } else {
                // Overwrite the whole rule config
                $mergedRules[$ruleId] = $ruleConfig;
            }
        }
        $mergedConfigData[self::KEY_RULES] = $mergedRules;

        // Merge the settings of both configs, using their keys as the only merge level. That is, we don't use a
        // recursive merge here even though the settings could be many levels deep.
        $mergedConfigData[self::KEY_SETTINGS] = array_merge(
            $baseConfig->getSettings(),
            $this->getSettings()
        );

        return new self($mergedConfigData, $baseConfig);
    }
}
