<?php
/**
 * Xử lý hiển thị discount trên frontend - Version Clean
 *
 * @package AffiliateOrderIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AOI_Discount_Display
 * Xử lý việc inject JavaScript và discount data vào frontend
 */
class AOI_Discount_Display {

    /**
     * Instance duy nhất của class
     *
     * @var AOI_Discount_Display|null
     */
    private static $instance = null;

    /**
     * Lấy instance của class (Singleton pattern)
     *
     * @return AOI_Discount_Display
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - khởi tạo hooks
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Khởi tạo các hooks
     */
    private function init_hooks() {
        // Kiểm tra enable custom JS display
        $options = get_option( 'aoi_options', array() );
        $enabled = isset( $options['enable_custom_js_display'] ) ? $options['enable_custom_js_display'] : '0';
        
        if ( '1' !== $enabled ) {
            return;
        }
        
        // Hook vào wp_footer để inject JS và data
        add_action( 'wp_footer', array( $this, 'inject_discount_script' ) );
    }

    /**
     * Inject discount script và data vào frontend
     */
    public function inject_discount_script() {
        // Chỉ chạy trên frontend
        if ( is_admin() ) {
            return;
        }

        // Kiểm tra page được chọn
        if ( ! $this->should_load_on_current_page() ) {
            return;
        }

        // Lấy discount data
        $discount_data = $this->get_discount_data();
        
        // Clear cache trước khi lấy options để đảm bảo data fresh
        wp_cache_delete( 'aoi_options', 'options' );
        
        // FORCE FRESH DATA - Clear all possible caches
        wp_cache_flush();
        delete_transient( 'aoi_options' );
        
        // Lấy custom JS code từ admin giống logic trong custom_js_code_callback
        $options = get_option( 'aoi_options', array() );
        
        // Default code giống hệt trong admin (NO DOMContentLoaded wrapper)
        $default_code = '
        // 🎨 Modern Discount Display Script with Cart Update Detection

        // Validate và display initial discount data
        if (window.aoiDiscountData) {
            // Validate data structure
            const requiredFields = ["hasDiscount", "formattedAmount", "discountPercent"];
            let isValidData = true;
            
            for (let field of requiredFields) {
                if (!(field in window.aoiDiscountData)) {
                    isValidData = false;
                }
            }
            
            if (window.aoiDiscountData.hasDiscount && isValidData) {                
                // Create modern floating discount badge
                const discountBadge = document.createElement("div");
                discountBadge.className = "aoi-modern-discount-badge";
                discountBadge.style.cssText = `
                    position: fixed;
                    top: 80px;
                    right: 30px;
                    background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%);
                    color: white;
                    padding: 20px 25px;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
                    z-index: 9998;
                    max-width: 320px;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    transform: translateX(400px);
                    transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                    cursor: pointer;
                    border: 2px solid rgba(255,255,255,0.2);
                `;
                
                discountBadge.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 32px;">💰</div>
                        <div>
                            <h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600;">
                                Affiliate Savings!
                            </h4>
                            <p style="margin: 0; font-size: 14px; opacity: 0.95;">
                                Save <strong>${window.aoiDiscountData.formattedAmount}</strong><br>
                                <span style="font-size: 12px;">(${window.aoiDiscountData.discountPercent}% discount applied)</span>
                            </p>
                        </div>
                        <div style="margin-left: auto; font-size: 20px; opacity: 0.7;">×</div>
                    </div>
                `;
                
                // Add hover effects
                discountBadge.addEventListener("mouseenter", function() {
                    this.style.transform = "translateX(0) scale(1.05)";
                    this.style.boxShadow = "0 15px 40px rgba(255, 107, 107, 0.4)";
                });
                
                discountBadge.addEventListener("mouseleave", function() {
                    this.style.transform = "translateX(0) scale(1)";
                    this.style.boxShadow = "0 10px 30px rgba(255, 107, 107, 0.3)";
                });
                
                // Click to close
                discountBadge.addEventListener("click", function() {
                    this.style.transform = "translateX(400px)";
                    setTimeout(() => this.remove(), 600);
                });
                
                // Insert and animate in
                document.body.appendChild(discountBadge);
                
                // Animate in after small delay
                setTimeout(() => {
                    discountBadge.style.transform = "translateX(0)";
                }, 500);
                
                // Auto-hide after 10 seconds
                setTimeout(() => {
                    if (document.body.contains(discountBadge)) {
                        discountBadge.style.transform = "translateX(400px)";
                        setTimeout(() => discountBadge.remove(), 600);
                    }
                }, 10000);
            }
        }';
        
        // DEBUG: Kiểm tra chi tiết $options array - CHUYỂN SANG CONSOLE
        $debug_info = array(
            'options_keys' => array_keys( $options ),
            'has_custom_js_code' => isset( $options['custom_js_code'] ),
            'custom_js_code_type' => isset( $options['custom_js_code'] ) ? gettype( $options['custom_js_code'] ) : 'not_set',
            'custom_js_code_length' => isset( $options['custom_js_code'] ) ? strlen( $options['custom_js_code'] ) : 0,
            'custom_js_code_empty' => isset( $options['custom_js_code'] ) ? empty( $options['custom_js_code'] ) : true,
            'custom_js_code_preview' => isset( $options['custom_js_code'] ) ? substr( $options['custom_js_code'], 0, 200 ) : 'not_set'
        );
        
        // Force use custom code if it exists and is not empty
        if ( isset( $options['custom_js_code'] ) && ! empty( trim( $options['custom_js_code'] ) ) ) {
            $custom_js_code = $options['custom_js_code'];
            $debug_info['using_custom_code'] = true;
        } else {
            $custom_js_code = $default_code;
            $debug_info['using_custom_code'] = false;
        }

        // Debug: Check what code we're actually using + add cache busting
        $code_hash = md5( $custom_js_code );
        $is_custom = isset( $options['custom_js_code'] );
        $is_using_default = $custom_js_code === $default_code;
        
        $final_debug_info = array(
            'is_custom' => $is_custom,
            'is_using_default' => $is_using_default,
            'code_length' => strlen( $custom_js_code ),
            'code_hash' => $code_hash,
            'contains_test_badge' => strpos( $custom_js_code, 'Test Badge' ) !== false,
            'contains_affiliate_savings' => strpos( $custom_js_code, 'Affiliate Savings' ) !== false,
            'code_preview' => substr( $custom_js_code, 0, 200 )
        );

        // Inject discount data vào window object
        ?>
        <script type="text/javascript">
        // 🔍 CRITICAL DEBUG INFO - Options và Custom Code
        console.log('🔍 AOI DEBUG - Options Analysis:', <?php echo wp_json_encode( $debug_info ); ?>);
        console.log('🔍 AOI DEBUG - Final Code Analysis:', <?php echo wp_json_encode( $final_debug_info ); ?>);
        
        // Alert để debug nhanh (có thể tắt sau)
        if (window.location.href.includes('debug=1')) {
            alert('AOI DEBUG:\nUsing Custom Code: ' + <?php echo $debug_info['using_custom_code'] ? 'true' : 'false'; ?> + 
                  '\nCustom Code Length: ' + <?php echo $debug_info['custom_js_code_length']; ?> + 
                  '\nContains Test Badge: ' + <?php echo $final_debug_info['contains_test_badge'] ? 'true' : 'false'; ?>);
        }
        
        // TEMP DEBUG: Add unique identifier to test
        window.aoiDebugTimestamp = new Date().getTime();
        console.log('🆔 AOI: Setting debug timestamp:', window.aoiDebugTimestamp);
        
        window.aoiDiscountData = <?php echo wp_json_encode( $discount_data ); ?>;
        console.log('🎯 AOI Debug Info:', {
            codeHash: '<?php echo esc_js( $code_hash ); ?>',
            codeLength: <?php echo strlen( $custom_js_code ); ?>,
            isCustom: <?php echo $is_custom ? 'true' : 'false'; ?>,
            timestamp: new Date().toISOString(),
            discountData: window.aoiDiscountData
        });
        
        // TEST: Force test data for debugging
        if (!window.aoiDiscountData.hasDiscount) {
            console.log('🧪 AOI: No real discount data, creating test data...');
            window.aoiDiscountData = {
                hasDiscount: true,
                discountPercent: 10,
                discountAmount: 50000,
                formattedAmount: "50.000₫",
                linkId: "test123"
            };
            console.log('🧪 AOI: Test data created:', window.aoiDiscountData);
        }
        </script>
        <?php

        // Inject custom JS code (always có code - default hoặc custom)
        ?>
        <script type="text/javascript" data-aoi-hash="<?php echo esc_attr( $code_hash ); ?>">
        
        // Flag to prevent multiple executions (with cache busting)
        console.log('🔧 AOI: Script execution check:', {
            executed: window.aoiBadgeExecuted || false,
            lastHash: window.aoiLastCodeHash || 'none',
            currentHash: '<?php echo esc_js( $code_hash ); ?>',
            shouldExecute: !window.aoiBadgeExecuted || window.aoiLastCodeHash !== '<?php echo esc_js( $code_hash ); ?>'
        });
        
        if (!window.aoiBadgeExecuted || window.aoiLastCodeHash !== '<?php echo esc_js( $code_hash ); ?>') {
            console.log('✅ AOI: Executing badge script with hash:', '<?php echo esc_js( $code_hash ); ?>');
            window.aoiBadgeExecuted = true;
            window.aoiLastCodeHash = '<?php echo esc_js( $code_hash ); ?>';
            
            // Clear any existing badges if code changed
            const existingBadges = document.querySelectorAll('.aoi-modern-discount-badge');
            console.log('🧹 AOI: Clearing existing badges:', existingBadges.length);
            existingBadges.forEach(badge => badge.remove());
            
            // Execute immediately if DOM ready, otherwise wait for DOMContentLoaded
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                console.log('🚀 AOI: Executing immediately (DOM ready)');
                console.log('🔍 AOI: About to execute custom code. Preview:', `<?php echo esc_js( substr( $custom_js_code, 0, 200 ) ); ?>...`);
                console.log('🔍 AOI: Code contains Test Badge:', <?php echo strpos( $custom_js_code, 'Test Badge' ) !== false ? 'true' : 'false'; ?>);
                
                // Monitor badge creation
                const originalCreateElement = document.createElement;
                document.createElement = function(tagName) {
                    const element = originalCreateElement.call(this, tagName);
                    if (tagName.toLowerCase() === 'div') {
                        const originalSetAttribute = element.setAttribute;
                        element.setAttribute = function(name, value) {
                            if (name === 'class' && value && value.includes('aoi-modern-discount-badge')) {
                                console.log('🎯 AOI: Badge div created!');
                            }
                            return originalSetAttribute.call(this, name, value);
                        };
                        
                        Object.defineProperty(element, 'className', {
                            set: function(value) {
                                if (value && value.includes('aoi-modern-discount-badge')) {
                                    console.log('🎯 AOI: Badge className set:', value);
                                }
                                this.setAttribute('class', value);
                            },
                            get: function() {
                                return this.getAttribute('class') || '';
                            }
                        });
                    }
                    return element;
                };
                
                try {
                    // CRITICAL DEBUG: Log exactly what's happening
                    console.log('🔥 AOI: EXECUTING CUSTOM CODE NOW');
                    console.log('🔥 AOI: window.aoiDiscountData:', window.aoiDiscountData);
                    console.log('🔥 AOI: discount data hasDiscount:', window.aoiDiscountData ? window.aoiDiscountData.hasDiscount : 'no data');
                    
                    <?php echo $custom_js_code; ?>
                    console.log('✅ AOI: Badge script executed successfully');
                    
                    // IMMEDIATE check for badges
                    console.log('🔍 AOI: IMMEDIATE badge check after execution');
                    const immediateBadges = document.querySelectorAll('.aoi-modern-discount-badge');
                    console.log('🔍 AOI: Found badges immediately:', immediateBadges.length);
                    
                    // Check what badges exist after execution
                    setTimeout(() => {
                        const badges = document.querySelectorAll('.aoi-modern-discount-badge');
                        console.log('🔍 AOI: Badges found after execution:', badges.length);
                        badges.forEach((badge, index) => {
                            console.log(`🔍 AOI: Badge ${index} content:`, badge.innerHTML.substring(0, 200));
                        });
                        
                        // If no badges, force create one for testing
                        if (badges.length === 0) {
                            console.log('🚨 AOI: NO BADGES FOUND - Creating test badge manually');
                            const testBadge = document.createElement('div');
                            testBadge.className = 'aoi-modern-discount-badge';
                            testBadge.innerHTML = '<h4>MANUAL TEST BADGE</h4>';
                            testBadge.style.cssText = `
                                position: fixed;
                                top: 120px;
                                right: 30px;
                                background: red;
                                color: white;
                                padding: 20px;
                                z-index: 99999;
                            `;
                            document.body.appendChild(testBadge);
                            console.log('🚨 AOI: Manual test badge created');
                        }
                    }, 1000);
                } catch (error) {
                    console.error('❌ AOI: Error in immediate JavaScript:', error);
                    console.error('❌ AOI: Error stack:', error.stack);
                }
            } else {
                console.log('⏳ AOI: Waiting for DOMContentLoaded');
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('🚀 AOI: Executing on DOMContentLoaded');
                    console.log('🔍 AOI: About to execute custom code. Preview:', `<?php echo esc_js( substr( $custom_js_code, 0, 200 ) ); ?>...`);
                    console.log('🔍 AOI: Code contains Test Badge:', <?php echo strpos( $custom_js_code, 'Test Badge' ) !== false ? 'true' : 'false'; ?>);
                    try {
                        // CRITICAL DEBUG: Log exactly what's happening  
                        console.log('🔥 AOI: EXECUTING CUSTOM CODE NOW - DOMContentLoaded');
                        console.log('🔥 AOI: window.aoiDiscountData:', window.aoiDiscountData);
                        console.log('🔥 AOI: discount data hasDiscount:', window.aoiDiscountData ? window.aoiDiscountData.hasDiscount : 'no data');
                        
                        <?php echo $custom_js_code; ?>
                        console.log('✅ AOI: Badge script executed successfully on DOMContentLoaded');
                        
                        // IMMEDIATE check for badges
                        console.log('🔍 AOI: IMMEDIATE badge check after DOMContentLoaded execution');
                        const immediateBadges = document.querySelectorAll('.aoi-modern-discount-badge');
                        console.log('🔍 AOI: Found badges immediately:', immediateBadges.length);
                        
                        // Check what badges exist after execution
                        setTimeout(() => {
                            const badges = document.querySelectorAll('.aoi-modern-discount-badge');
                            console.log('🔍 AOI: Badges found after execution:', badges.length);
                            badges.forEach((badge, index) => {
                                console.log(`🔍 AOI: Badge ${index} content:`, badge.innerHTML.substring(0, 200));
                            });
                            
                            // If no badges, force create one for testing
                            if (badges.length === 0) {
                                console.log('🚨 AOI: NO BADGES FOUND - Creating test badge manually');
                                const testBadge = document.createElement('div');
                                testBadge.className = 'aoi-modern-discount-badge';
                                testBadge.innerHTML = '<h4>MANUAL TEST BADGE - DOMContentLoaded</h4>';
                                testBadge.style.cssText = `
                                    position: fixed;
                                    top: 120px;
                                    right: 30px;
                                    background: red;
                                    color: white;
                                    padding: 20px;
                                    z-index: 99999;
                                `;
                                document.body.appendChild(testBadge);
                                console.log('🚨 AOI: Manual test badge created');
                            }
                        }, 1000);
                    } catch (error) {
                        console.error('❌ AOI: Error in DOMContentLoaded JavaScript:', error);
                        console.error('❌ AOI: Error stack:', error.stack);
                    }
                });
            }
        } else {
            console.log('🔧 AOI: Badge already executed with same code hash, skipping...');
        }
        </script>
        <?php
    }

    /**
     * Kiểm tra có nên load script trên page hiện tại không
     *
     * @return bool
     */
    private function should_load_on_current_page() {
        $options = get_option( 'aoi_options', array() );
        $enabled_pages = isset( $options['custom_js_pages'] ) ? (array) $options['custom_js_pages'] : array( 'checkout', 'thankyou', 'cart' );

        // Debug log current page
        error_log( '🔍 AOI: Current URL: ' . $_SERVER['REQUEST_URI'] );
        error_log( '🔍 AOI: Enabled pages: ' . implode( ', ', $enabled_pages ) );
        
        // Enhanced checkout detection - bao gồm order-review
        if ( in_array( 'checkout', $enabled_pages ) ) {
            $is_checkout_related = false;
            
            // Standard checkout detection
            if ( function_exists( 'is_checkout' ) && is_checkout() ) {
                $is_checkout_related = true;
                error_log( '✅ AOI: Standard checkout detected' );
            }
            
            // URL-based detection cho order-review
            $current_url = $_SERVER['REQUEST_URI'] ?? '';
            if ( strpos( $current_url, 'checkout' ) !== false || strpos( $current_url, 'order-review' ) !== false ) {
                $is_checkout_related = true;
                error_log( '✅ AOI: Checkout URL detected: ' . $current_url );
            }
            
            // WooCommerce endpoint detection
            if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
                $is_checkout_related = true;
                error_log( '✅ AOI: Order-pay endpoint detected' );
            }
            
            if ( $is_checkout_related ) {
                return true;
            }
        }

        if ( in_array( 'thankyou', $enabled_pages ) && function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
            error_log( '✅ AOI: Thank you page detected' );
            return true;
        }

        if ( in_array( 'cart', $enabled_pages ) && function_exists( 'is_cart' ) && is_cart() ) {
            error_log( '✅ AOI: Cart page detected' );
            return true;
        }

        error_log( '❌ AOI: No matching page detected for current context' );
        return false;
    }

    /**
     * Lấy discount data để inject vào JavaScript
     *
     * @return array
     */
    private function get_discount_data() {
        $default_data = array(
            'hasDiscount'     => false,
            'discountPercent' => 0,
            'discountAmount'  => 0,
            'formattedAmount' => '',
        );

        // Kiểm tra có class AOI_Affiliate_API không
        if ( ! class_exists( 'AOI_Affiliate_API' ) ) {
            return $default_data;
        }

        try {
            $api = new AOI_Affiliate_API();
            $ctv_cookie = $api->get_ctv_cookie();

            if ( ! $ctv_cookie ) {
                return $default_data;
            }

            // Verify token và lấy linkId
            $ctv_data = $api->verify_ctv_token( $ctv_cookie );
            if ( ! $ctv_data || ! isset( $ctv_data['linkId'] ) ) {
                return $default_data;
            }

            $link_id = $ctv_data['linkId'];

            // Lấy chiết khấu từ API, cache trong session
            if ( ! isset( $_SESSION ) ) {
                session_start();
            }

            if ( ! isset( $_SESSION['affiliate_discount_' . $link_id] ) ) {
                $_SESSION['affiliate_discount_' . $link_id] = $api->get_affiliate_discount( $link_id );
            }

            $discount_percent = $_SESSION['affiliate_discount_' . $link_id];

            if ( $discount_percent <= 0 ) {
                return $default_data;
            }

            // Tính chiết khấu dựa trên cart subtotal (nếu có WooCommerce)
            $discount_amount = 0;
            if ( function_exists( 'WC' ) && WC()->cart ) {
                $cart_subtotal = WC()->cart->get_cart_contents_total();
                $discount_amount = $cart_subtotal * ( $discount_percent / 100 );
            }

            return array(
                'hasDiscount'     => true,
                'discountPercent' => $discount_percent,
                'discountAmount'  => $discount_amount,
                'formattedAmount' => number_format( $discount_amount, 0, ',', '.' ) . '₫',
                'linkId'          => $link_id
            );

        } catch ( Exception $e ) {
            return $default_data;
        }
    }
}
