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
        wp_cache_flush();
        delete_transient( 'aoi_options' );
        
        // Lấy custom JS code từ admin
        $options = get_option( 'aoi_options', array() );
        
        // Default code (NO DOMContentLoaded wrapper)
        $default_code = '
        // Modern Discount Display Script with Cart Update Detection

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
        

        
        // Force use custom code if it exists and is not empty
        if ( isset( $options['custom_js_code'] ) && ! empty( trim( $options['custom_js_code'] ) ) ) {
            $custom_js_code = $options['custom_js_code'];
        } else {
            $custom_js_code = $default_code;
        }

        // Cache busting with code hash
        $code_hash = md5( $custom_js_code );

        // Inject custom JS code (always có code - default hoặc custom)
        ?>
        <script type="text/javascript">
        // Inject discount data vào global scope
        window.aoiDiscountData = <?php echo json_encode( $discount_data ); ?>;
        </script>
        
        <script type="text/javascript" data-aoi-hash="<?php echo esc_attr( $code_hash ); ?>">
        
        // Flag to prevent multiple executions
        if (!window.aoiBadgeExecuted || window.aoiLastCodeHash !== '<?php echo esc_js( $code_hash ); ?>') {
            window.aoiBadgeExecuted = true;
            window.aoiLastCodeHash = '<?php echo esc_js( $code_hash ); ?>';
            
            // Clear any existing badges if code changed
            const existingBadges = document.querySelectorAll('.aoi-modern-discount-badge');
            existingBadges.forEach(badge => badge.remove());
            
            // Execute immediately if DOM ready, otherwise wait for DOMContentLoaded
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                try {
                    <?php echo $custom_js_code; ?>
                } catch (error) {
                    console.error('AOI Error:', error);
                }
            } else {
                document.addEventListener('DOMContentLoaded', function() {
                    try {
                        <?php echo $custom_js_code; ?>
                    } catch (error) {
                        console.error('AOI Error:', error);
                    }
                });
            }
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

        $enabled_pages = array();
        if( isset( $options['custom_js_pages'] ) && is_array( $options['custom_js_pages'] ) ) {
            $enabled_pages = $options['custom_js_pages'];
        } else if ( isset( $options['custom_js_pages'] ) && empty( $options['custom_js_pages'] ) ) {
            // User đã save settings nhưng không chọn page nào => empty array
            $enabled_pages = array();
        } else {
            // Chưa có settings => dùng default
            $enabled_pages = array( 'checkout', 'thankyou', 'cart' );
        }
        
        // Enhanced checkout detection - bao gồm order-review
        if ( in_array( 'checkout', $enabled_pages ) ) {
            $is_checkout_related = false;
            
            // WooCommerce endpoint detection
            if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
                $is_checkout_related = true;
            } else {
                $is_checkout_related = false;
            }
            
            if ( $is_checkout_related ) {
                return true;
            }
        }

        if ( in_array( 'thankyou', $enabled_pages ) && function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
            return true;
        }

        if ( in_array( 'cart', $enabled_pages ) && function_exists( 'is_cart' ) && is_cart() ) {
            return true;
        }

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

            // Tính chiết khấu dựa trên order total hoặc cart subtotal
            $discount_amount = 0;
            $order_total = 0;

            // Trên thank you page, lấy từ order vừa được tạo
            if ( function_exists( 'is_order_received_page') && is_order_received_page() ) {
                global $wp;
                if ( isset( $wp->query_vars['order-received'] ) ) {
                    $order_id = intval( $wp->query_vars['order-received'] );
                    $order = wc_get_order( $order_id );
                    if ( $order ) {
                        // Dùng subtotal để tránh tính discount 2 lần
                        $order_total = $order->get_total();
                        $subtotal = $order->get_subtotal();
                        $order_total = $subtotal > 0 ? $subtotal : $order_total;
                    }
                }
            }
            // Trên các page khác, dùng cart data
            elseif ( function_exists( 'WC' ) && WC()->cart ) {
                $order_total = WC()->cart->get_cart_contents_total();
            }

            $discount_amount = $order_total * ( $discount_percent / 100 );

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