<?php
/**
 * Sample test case
 *
 * @package AffiliateOrderIntegration\Tests
 */

namespace AffiliateOrderIntegration\Tests;

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
        require_once dirname(dirname(__DIR__)) . '/affiliate-order-integration.php';
        
        $this->assertTrue(defined('AOI_PLUGIN_URL'));
        $this->assertTrue(defined('AOI_PLUGIN_PATH'));
        $this->assertTrue(defined('AOI_PLUGIN_VERSION'));
        $this->assertTrue(defined('AOI_PLUGIN_BASENAME'));
    }

    /**
     * Test plugin class exists
     */
    public function test_plugin_class_exists() {
        require_once dirname(dirname(__DIR__)) . '/affiliate-order-integration.php';
        
        $this->assertTrue(class_exists('AffiliateOrderIntegration'));
    }

    /**
     * Test singleton pattern
     */
    public function test_singleton_pattern() {
        require_once dirname(dirname(__DIR__)) . '/affiliate-order-integration.php';
        
        $instance1 = \AffiliateOrderIntegration::get_instance();
        $instance2 = \AffiliateOrderIntegration::get_instance();
        
        $this->assertSame($instance1, $instance2);
    }
}
