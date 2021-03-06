<?php
declare(strict_types=1);

namespace PhpLint\Test\Configuration;

use PhpLint\Configuration\Configuration;
use PhpLint\Rules\RuleSeverity;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    /**
     * Tests the merging of 'extends' data when merging a config onto another one.
     */
    public function testMergeOntoConfigMergesExtendsCorrectly()
    {
        $targetConfig = new Configuration([
            Configuration::KEY_EXTENDS => [
                'VendorA/ConfigA' => 1,
            ],
        ]);
        $overridingConfig = new Configuration([
            Configuration::KEY_EXTENDS => [
                'VendorA/ConfigA' => 2,
                'VendorB/ConfigA' => 3,
                'VendorB/ConfigB' => 4,
            ],
        ]);
        $mergedConfig = $overridingConfig->mergeOntoConfig($targetConfig);
        self::assertEquals($targetConfig, $mergedConfig->getParentConfig());
        self::assertEquals(
            [
                'VendorA/ConfigA' => 2,
                'VendorB/ConfigA' => 3,
                'VendorB/ConfigB' => 4,
            ],
            $mergedConfig->getExtends()
        );
    }

    /**
     * Tests the merging of 'plugins' data when merging a config onto another one.
     */
    public function testMergeOntoConfigMergesPluginsCorrectly()
    {
        $targetConfig = new Configuration([
            Configuration::KEY_PLUGINS => [
                'VendorA/PluginA' => 1,
            ],
        ]);
        $overridingConfig = new Configuration([
            Configuration::KEY_PLUGINS => [
                'VendorA/PluginA' => 2,
                'VendorB/PluginA' => 3,
                'VendorB/PluginB' => 4,
            ],
        ]);
        $mergedConfig = $overridingConfig->mergeOntoConfig($targetConfig);
        self::assertEquals($targetConfig, $mergedConfig->getParentConfig());
        self::assertEquals(
            [
                'VendorA/PluginA' => 2,
                'VendorB/PluginA' => 3,
                'VendorB/PluginB' => 4,
            ],
            $mergedConfig->getPlugins()
        );
    }

    /**
     * Tests the merging of the 'root' flag when merging a config onto another one.
     */
    public function testMergeOntoConfigMergesRootFlagCorrectly()
    {
        $targetConfig = new Configuration([
            Configuration::KEY_ROOT => true,
        ]);
        $overridingConfig = new Configuration([
            Configuration::KEY_ROOT => true,
        ]);
        $mergedConfig = $overridingConfig->mergeOntoConfig($targetConfig);
        self::assertEquals($targetConfig, $mergedConfig->getParentConfig());
        self::assertTrue($mergedConfig->isRoot());

        $targetConfig = new Configuration([
            Configuration::KEY_ROOT => true,
        ]);
        $overridingConfig = new Configuration([
            Configuration::KEY_ROOT => false,
        ]);
        $mergedConfig = $overridingConfig->mergeOntoConfig($targetConfig);
        self::assertEquals($targetConfig, $mergedConfig->getParentConfig());
        self::assertTrue($mergedConfig->isRoot());

        $targetConfig = new Configuration([
            Configuration::KEY_ROOT => false,
        ]);
        $overridingConfig = new Configuration([
            Configuration::KEY_ROOT => true,
        ]);
        $mergedConfig = $overridingConfig->mergeOntoConfig($targetConfig);
        self::assertEquals($targetConfig, $mergedConfig->getParentConfig());
        self::assertTrue($mergedConfig->isRoot());

        $targetConfig = new Configuration([
            Configuration::KEY_ROOT => false,
        ]);
        $overridingConfig = new Configuration([
            Configuration::KEY_ROOT => false,
        ]);
        $mergedConfig = $overridingConfig->mergeOntoConfig($targetConfig);
        self::assertEquals($targetConfig, $mergedConfig->getParentConfig());
        self::assertFalse($mergedConfig->isRoot());
    }

    /**
     * Tests the merging of 'rules' data when merging a config onto another one.
     */
    public function testMergeOntoConfigMergesRulesCorrectly()
    {
        $targetConfig = new Configuration([
            Configuration::KEY_RULES => [
                'rule-off' => RuleSeverity::SEVERITY_OFF,
                'rule-warning' => array_search(RuleSeverity::SEVERITY_WARNING, RuleSeverity::ALL_SEVERITIES),
                'rule-error' => [
                    RuleSeverity::SEVERITY_OFF,
                    'some',
                    'rule',
                    'config',
                ],
                'unchanged-rule' => RuleSeverity::SEVERITY_OFF,
            ],
        ]);
        $overridingConfig = new Configuration([
            Configuration::KEY_RULES => [
                'rule-off' => RuleSeverity::SEVERITY_ERROR,
                'rule-warning' => [
                    RuleSeverity::SEVERITY_ERROR,
                    1,
                    2,
                ],
                'rule-error' => RuleSeverity::SEVERITY_WARNING,
                'new-rule' => RuleSeverity::SEVERITY_ERROR,
            ],
        ]);
        $mergedConfig = $overridingConfig->mergeOntoConfig($targetConfig);
        self::assertEquals($targetConfig, $mergedConfig->getParentConfig());
        self::assertEquals(
            [
                'rule-off' => RuleSeverity::SEVERITY_ERROR,
                'rule-warning' => [
                    RuleSeverity::SEVERITY_ERROR,
                    1,
                    2,
                ],
                'rule-error' => [
                    RuleSeverity::SEVERITY_WARNING,
                    'some',
                    'rule',
                    'config',
                ],
                'unchanged-rule' => RuleSeverity::SEVERITY_OFF,
                'new-rule' => RuleSeverity::SEVERITY_ERROR,
            ],
            $mergedConfig->getRules(true)
        );
    }

    /**
     * Tests the merging of 'settings' data when merging a config onto another one.
     */
    public function testMergeOntoConfigMergesSettingsCorrectly()
    {
        $targetConfig = new Configuration([
            Configuration::KEY_SETTINGS => [
                'keyA' => [
                    'keyA/subkeyA' => 1,
                ],
                'keyB' => [
                    'keyB/subkeyA' => 1,
                ],
                'keyC' => 1,
            ],
        ]);
        $overridingConfig = new Configuration([
            Configuration::KEY_SETTINGS => [
                'keyB' => 2,
                'keyC' => [
                    'keyC/subkeyB' => 2,
                ],
            ],
        ]);
        $mergedConfig = $overridingConfig->mergeOntoConfig($targetConfig);
        self::assertEquals($targetConfig, $mergedConfig->getParentConfig());
        self::assertEquals(
            [
                'keyA' => [
                    'keyA/subkeyA' => 1,
                ],
                'keyB' => 2,
                'keyC' => [
                    'keyC/subkeyB' => 2,
                ],
            ],
            $mergedConfig->getSettings()
        );
    }
}
