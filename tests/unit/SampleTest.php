<?php
/**
 * Sample test case
 *
 * @package MySamplePlugin\Tests
 */

namespace MySamplePlugin\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Sample test class
 */
class SampleTest extends TestCase {

    /**
     * Test that true is true
     */
    public function test_true_is_true() {
        $this->assertTrue(true);
    }

    /**
     * Test plugin constants are defined
     */
    public function test_plugin_constants() {
        // Load the main plugin file
        require_once dirname(dirname(__DIR__)) . '/my-sample-plugin.php';
        
        $this->assertTrue(defined('MSP_PLUGIN_URL'));
        $this->assertTrue(defined('MSP_PLUGIN_PATH'));
        $this->assertTrue(defined('MSP_PLUGIN_VERSION'));
        $this->assertTrue(defined('MSP_PLUGIN_BASENAME'));
    }

    /**
     * Test plugin class exists
     */
    public function test_plugin_class_exists() {
        require_once dirname(dirname(__DIR__)) . '/my-sample-plugin.php';
        
        $this->assertTrue(class_exists('MySamplePlugin'));
    }

    /**
     * Test singleton pattern
     */
    public function test_singleton_pattern() {
        require_once dirname(dirname(__DIR__)) . '/my-sample-plugin.php';
        
        $instance1 = \MySamplePlugin::get_instance();
        $instance2 = \MySamplePlugin::get_instance();
        
        $this->assertSame($instance1, $instance2);
    }
}
