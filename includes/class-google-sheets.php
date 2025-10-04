<?php
/**
 * Google Sheets Integration Class
 * Handles sending order data to Google Sheets
 * 
 * @package AffiliateOrderIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class AOI_Google_Sheets
 * Handles integration with Google Sheets for order data
 */

class AOI_Google_Sheets {
    /**
     * Google Sheets Form URL
     * @var string
     */
    private $google_form_url;

    /**
     * Constructor
     */
    public function __construct() {
        // Get Google Form URL from admin settings
        $options = get_option( 'aoi_options', array() );
        $this->google_form_url = isset( $options['google_form_url'] ) && !empty( $options['google_form_url'] ) 
            ? $options['google_form_url'] 
            : 'https://docs.google.com/forms/u/2/d/e/1FAIpQLSc2ap8yeDs2nLZNAw6EKlZxt2w3imV8tfIVj7b-zDi1S1Fq4Q/formResponse';
        
        $this->init_hooks();
    }

    /**
     * init hooks 
     */
    private function init_hooks() {
        add_action( 'woocommerce_thankyou', array( $this, 'send_order_to_google_sheets' ) );
    }

    /**
     * Send order data to Google Sheets
     *
     * @param int $order_id Order ID
     */
    public function send_order_to_google_sheets( $order_id ) {
        // Check if Google Sheets is enabled in settings
        $options = get_option( 'aoi_options', array() );
        $google_sheets_enabled = isset( $options['enable_google_sheets'] ) ? $options['enable_google_sheets'] : '1';
        
        if ( '1' !== $google_sheets_enabled ) {
            return; // Google Sheets disabled in settings
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $order_data = $order->get_data();
        $customer_note = $order->get_customer_note();
        $order_status = $order_data['status'];
        $order_billing_first_name = $order_data['billing']['first_name'];
        $order_billing_last_name = $order_data['billing']['last_name'];
        $order_billing_address_1 = $order_data['billing']['address_1'];
        $order_billing_phone = $order_data['billing']['phone'];
        $order_payment_method_title = $order_data['payment_method_title'];

        // Create product detail string
        $product_detail = '';
        foreach ( $order->get_items() as $item ) {
            $item_data = $item->get_data();
            $product_name = $item_data['name'];
            $quantity = $item_data['quantity'];
            $line_total = $item_data['total'];
            $product_detail .= $product_name . "(SL: " . $quantity . ", Thành tiền: " . $line_total .")\n";
        }

        // Create address string
        $address = str_replace(' - - ', "", str_replace(array('<br/>', $order_billing_first_name, $order_billing_last_name), " - ", $order->get_formatted_billing_address()));

        // define purchase system
        $system_buy = array( "Visinh AT" );

        // Check if order was sent to affiliate (check database log)
        if ( $this->was_sent_to_affiliate( $order_id ) ) {
            $system_buy[] = "SellMate AFF";
        }

        // Prepare data for Google Form
        $fields = array(
            'entry.446804691' => urlencode( implode( " / ", $system_buy ) ),
            'entry.874701135' => urlencode( $order_id ),
            'entry.195364413' => urlencode( $order_billing_first_name . ' ' . $order_billing_last_name ),
            'entry.514412539' => urlencode( $address ),
            'entry.232594094' => urlencode( $order_billing_phone ),
            'entry.1452142591' => urlencode( $product_detail ),
            // 'entry.1792964533' => urlencode( $customer_note ),
            'entry.1706158428' => urlencode( $order_payment_method_title ),
            'entry.994456795' => urlencode( $order_status )
        );

        // Send data to Google Sheets
        // Temporarily disabled - customer's sheet, cannot test
        $this->send_to_google_form( $fields );
    }

    /**
     * Check if order was sent to affiliate (check database log)
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    private function was_sent_to_affiliate( $order_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aoi_affiliate_orders';
        
        $log = $wpdb->get_var( $wpdb->prepare( 
            "SELECT status FROM $table_name WHERE order_id = %d AND status = 'sent'",
            $order_id
        ) );
        
        return  !empty($log) ;
    }

    /**
     * Send data to Google Form
     *
     * @param array $fields Form fields.
     */
    private function send_to_google_form( $fields ) {
        $fields_string = '';
        foreach ( $fields as $key => $value ) {
            $fields_string .= $key . '=' . $value . '&';
        }
        $fields_string = rtrim( $fields_string, '&' );

        $arg = array(
            'method'    => 'POST',
            'headers'   => array(
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            ),
            'body'      => $fields_string,
            'timeout'   => 60,
        );

        $response = wp_remote_request( $this->google_form_url, $arg );  

        // Log the response for debugging
        $this->log_google_sheets_result( $response );
    }

    /**
     * Log response from Google Sheets
     * 
     * @param array|WP_Error $response Response from wp_remote_request.
     */
    private function log_google_sheets_result( $response ) {
        $log_file = WP_CONTENT_DIR . '/logs/aff-sellmate.log';
        $timestamp = date( 'Y-m-d H:i:s' );

        if ( is_wp_error( $response ) ) {
            $message = '[' . $timestamp . '] Google Sheets Error: ' . $response->get_error_message() . PHP_EOL;
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $message = '[' . $timestamp . '] Google Sheets Response Code: ' . $response_code . PHP_EOL;
        }

        file_put_contents( $log_file, $message, FILE_APPEND | LOCK_EX );
    }
}