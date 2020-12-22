<?php

use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Module;
use ElementorPro\Plugin;

/**
 * Class IDPay_Payment_Callback
 */

class IDPay_Payment_Callback {

    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     *
     */
    public function process() {

        $params    = !empty($_POST['id']) ? $_POST : $_GET;
        $status    = !empty( $params['status'] )  ? sanitize_text_field( $params['status'] )   : '';
        $track_id  = !empty( $params['track_id'] )? sanitize_text_field( $params['track_id'] ) : '';
        $id        = !empty( $params['id'] )      ? sanitize_text_field( $params['id'] )       : '';
        $order_id  = !empty( $params['order_id'] )? sanitize_text_field( $params['order_id'] ) : '';

        if ( empty( $id ) || empty( $order_id ) ) {
            return;
        }

        $IDPay = new IDPay_Elementor_Extension;
        $row = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM " . $this->wpdb->prefix . $IDPay::IDPAY_TABLE_NAME ." WHERE order_id='%s'", $order_id ) );

        if ( empty( $row ) || $row->status != 'bank' || $id != $row->trans_id) {
//                wp_redirect( add_query_arg( ['status' => 'success', 'message' => filled_message( $value['success_message'], $row->track_id, $row->order_id )], $value['return'] ) );
//                exit();
            return;
        }

        if ( $status != 10 ) {
            $this->wpdb->update( $this->wpdb->prefix . $IDPay::IDPAY_TABLE_NAME,
                array(
                    'status'   => 'failed',
                    'track_id' => $track_id,
                    'log'  => 'data => <pre>'. print_r($params, true) . '</pre>'
                ),
                array( 'trans_id' => $id ),
                array(
                    '%s',
                    '%s',
                    '%s',
                ),
                array( '%d' )
            );
//            wp_redirect( add_query_arg( ['status' => 'failed', 'message' => filled_message( $value['failed_message'], $track_id, $order_id )], $value['return'] ) );
//            exit();
            return;
        }

        $api_key = $this->get_global_setting('idpay_api_key');
        $sandbox = $this->get_global_setting('idpay_sandbox') || 'false';

        $data = array(
            'id'       => $id,
            'order_id' => $order_id,
        );
        $headers = array(
            'Content-Type' => 'application/json',
            'X-API-KEY'    => $api_key,
            'X-SANDBOX'    => $sandbox,
        );
        $args    = array(
            'body'    => json_encode( $data ),
            'headers' => $headers,
            'timeout' => 15,
        );

        $response = $IDPay->call_gateway_endpoint( 'https://api.idpay.ir/v1.1/payment/verify', $args );

        if ( is_wp_error( $response ) ) {
//            wp_redirect( add_query_arg( ['status' => 'failed', 'message' => $response->get_error_message()], $value['return'] ) );
            return;
        }

        $http_status = wp_remote_retrieve_response_code( $response );
        $result      = wp_remote_retrieve_body( $response );
        $result      = json_decode( $result );

        if ( $http_status != 200 ) {
            $message = sprintf( __( 'An error occurred while verifying a transaction. error status: %s, error code: %s, error message: %s', 'idpay-elementor' ), $http_status, $result->error_code, $result->error_message );
            $this->wpdb->update( $this->wpdb->prefix . $IDPay::IDPAY_TABLE_NAME,
                array(
                    'status' => 'failed',
                    'log'  => $message . '\n data => <pre>'. print_r($params, true) . '</pre>',
                ),
                array( 'trans_id' => $id ),
                array(
                    '%s',
                    '%s',
                ),
                array( '%d' )
            );

//            wp_redirect( add_query_arg( ['status' => 'failed', 'message' => $message], $value['return'] ) );
//            exit();
            return;
        }

        $verify_status   = empty( $result->status ) ? NULL : $result->status;
        $verify_track_id = empty( $result->track_id ) ? NULL : $result->track_id;
        $verify_id       = empty( $result->id ) ? NULL : $result->id;
        $verify_order_id = empty( $result->order_id ) ? NULL : $result->order_id;

        if ( empty( $verify_status ) || empty( $verify_track_id ) || $verify_status < 100 ) {
            $this->wpdb->update( $this->wpdb->prefix . $IDPay::IDPAY_TABLE_NAME,
                array(
                    'status'   => 'failed',
                    'track_id' => $verify_track_id,
                    'log'  => 'verify result => <pre>'. print_r($result, true) . '</pre>',
                ),
                array( 'trans_id' => $verify_id ),
                array(
                    '%s',
                    '%s',
                    '%s',
                ),
                array( '%d' )
            );

//            wp_redirect( add_query_arg( ['status' => 'failed', 'message' => filled_message( $value['failed_message'], $verify_track_id, $verify_order_id )], $value['return'] ) );
//            exit();
            return;
        } else {
            $this->wpdb->update( $this->wpdb->prefix . $IDPay::IDPAY_TABLE_NAME,
                array(
                    'status'   => 'completed',
                    'track_id' => $verify_track_id,
                    'log'  => 'result => <pre>'. print_r($result, true) . '</pre>',
                ),
                array( 'trans_id' => $verify_id ),
                array(
                    '%s',
                    '%s',
                    '%s',
                ),
                array( '%d' )
            );

//            wp_redirect( add_query_arg( ['status' => 'success', 'message' => filled_message( $value['success_message'], $verify_track_id, $verify_order_id )], $value['return'] ) );
//            exit();
            return;
        }

    }

    /**
     * @param $name
     *
     * @return bool|mixed|void
     */
    private function get_global_setting( $name ) {
        return get_option( 'elementor_' . $name );
    }
}
