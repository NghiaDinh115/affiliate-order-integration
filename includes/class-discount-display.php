<?php
/**
 * Discount Display Handler
 * Xử lý hiển thị chiết khấu trên frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AOI_Discount_Discount {

    public function __construct() {
        add_action( 'wp_footer', array($this, 'inject_discount_display') );
    }

    /**
     * Inject discount display into the footer
     */
    public function inject_discount_display() {
        if ( ! is_wc_endpoint_url( 'order-received' ) ) {
            return; // Only inject on order received page
        }

        $options = get_option( 'aoi_options', array() );
        $enabled = isset( $options['enable_discount_display'] ) ? $options['enable_discount_display'] : '0';

        if ( '1' !== $enabled) {
            return; // Discount display is disabled
        }

        $order_id = get_query_var( 'order-received' );
        if ( ! $order_id ) {
            return; // No order ID found
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return; // Invalid order
        }

        // Kiểm tra xem có Affiliate discount không
        $discount_amount = 0;
        foreach ( $order->get_fees() as $fee) {
            if ( $fee->get_name() === 'Affiliate Discount' ) {
                $discount_amount = abs( $fee->get_total() );
                break;
            }
        }

        if ( $discount_amount <= 0 ) {
            return;
        }

        // Lấy settings để hiển thị
        $selector = isset( $options['discount_dom_selector'] ) ? $options['discount_dom_selector'] : '.woocommerce-order-overview';
        $template = isset( $options['discount_message_template'] ) ? $options['discount_message_template'] : '';

        if ( empty( $template ) ) {
            return;
        }

        // replace placeholders
        $message = str_replace(
            array( '{discount_amount}', '{discount_raw}', '{order_id}' ),
            array( wc_price( $discount_amount ), $discount_amount, $order_id ),
            $template
        );
        ?>
        <script>
            jQuery(document).ready(function($) {
                var $target = $ ('<?php echo esc_js( $selector ); ?>');
                if ($target.lenght > 0) {
                    $target.after('<?php echo addslashes( $message ); ?>');
                }
            });
        </script>
        <?php
    }
}