<?php

/**
 * BitPay Checkout IPN 5.1.0
 *
 * This file verifies that the payment gateway module is active,
 * validates an Invoice ID, checks for the existence of a Transaction ID,
 * and adds Payment to an Invoice.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

use WHMCS\Database\Capsule;

// Require libraries needed for gateway module functions.
require_once  '../../../../init.php';
require_once ROOTDIR . "/includes/gatewayfunctions.php";
require_once ROOTDIR . "/includes/invoicefunctions.php";

// Detect module name from filename.
$gatewayModuleName = 'bitpaycheckout';

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);
define('TEST_URL', 'https://test.bitpay.com/invoices/');
define('PROD_URL', 'https://bitpay.com/invoices/');

function checkInvoiceStatus($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$response = json_decode(file_get_contents("php://input"), true);
$data = $response['data'];

$file = 'bitpay.txt';
$err = 'bitpay_err.txt';

file_put_contents($file, '===========INCOMING IPN=========================', FILE_APPEND);
file_put_contents($file, date('d.m.Y H:i:s'), FILE_APPEND);
file_put_contents($file, print_r($response, true), FILE_APPEND);
file_put_contents($file, '===========END OF IPN===========================', FILE_APPEND);

$order_status = $data['status'];
$order_invoice = $data['id'];
$endpoint = $gatewayParams['bitpay_checkout_endpoint'];
if ($endpoint == 'Test') {
    $url_check = TEST_URL . $order_invoice;
} else {
    $url_check = PROD_URL . $order_invoice;
}
$invoiceStatus = json_decode(checkInvoiceStatus($url_check));

if (!$invoiceStatus || !isset($invoiceStatus->data) || !isset($invoiceStatus->data->status) || !isset($invoiceStatus->data->orderId)) {
    file_put_contents($err, '===========IPN ERROR=========================', FILE_APPEND);
    file_put_contents($err, date('d.m.Y H:i:s') . " unable to verify invoice {$order_invoice} with BitPay\n", FILE_APPEND);
    file_put_contents($err, print_r($response, true), FILE_APPEND);
    file_put_contents($err, '===========END OF IPN ERROR===========================', FILE_APPEND);
    http_response_code(400);
    exit();
}

$serverStatus = $invoiceStatus->data->status;

$orderid = checkCbInvoiceID($invoiceStatus->data->orderId, 'bitpaycheckout');
$price = $invoiceStatus->data->price;
// First see if the ipn matches
$trans_data = Capsule::table('_bitpay_checkout_transactions')
    ->select('order_id', 'transaction_id', 'transaction_status')
    ->where([
        ['order_id', '=', $orderid],
        ['transaction_id', '=', $order_invoice],
    ])
    ->get();
$rowdata = (array) $trans_data[0];
$btn_id = $rowdata['transaction_id'];
$transaction_status = $rowdata['transaction_status'];

if ($btn_id) {
    switch ($serverStatus) {
        // Complete, update invoice table to Paid
        case 'complete':
            if ($transaction_status === 'complete') {
                exit();
            }

            // Update the bitpay_invoice table
            $table = '_bitpay_checkout_transactions';
            $update = array('transaction_status' => 'complete', 'updated_at' => date('Y-m-d H:i:s'));
            try {
                Capsule::table($table)
                    ->where([
                        ['order_id', '=', $orderid],
                        ['transaction_id', '=', $order_invoice],
                    ])
                    ->update($update);
            } catch (Exception $e) {
                file_put_contents($file, $e, FILE_APPEND);
            }

            addInvoicePayment(
                $orderid,
                $order_invoice,
                $price,
                0,
                'bitpaycheckout'
            );
            break;

        // Processing - put in Payment Pending
        case 'paid':
            // Update the invoices table
            $table = 'tblinvoices';
            $update = array("status" => 'Payment Pending','datepaid' => date('Y-m-d H:i:s'));
            try {
                Capsule::table($table)
                    ->where([
                        ['id', '=', $orderid],
                        ['paymentmethod', '=', 'bitpaycheckout'],
                    ])
                    ->update($update);
            } catch (Exception $e) {
                file_put_contents($file, $e, FILE_APPEND);
            }

            // Update the bitpay_invoice table
            $table = '_bitpay_checkout_transactions';
            $update = array('transaction_status' => 'paid', 'updated_at' => date('Y-m-d H:i:s'));
            try {
                Capsule::table($table)
                    ->where([
                        ['order_id', '=', $orderid],
                        ['transaction_id', '=', $order_invoice],
                    ])
                    ->update($update);
            } catch (Exception $e) {
                file_put_contents($file, $e, FILE_APPEND);
            }
            break;

        // Expired, remove from transaction table, wont be in invoice table
        case 'expired':
            // Delete any orphans
            $table = '_bitpay_checkout_transactions';
            try {
                Capsule::table($table)
                    ->where('transaction_id', '=', $order_invoice)
                    ->delete();
            } catch (Exception $e) {
                file_put_contents($file, $e, FILE_APPEND);
            }
            break;

        // Refunded, set invoice and bitpay transaction to refunded status
        case 'pending':
            if ($event['name'] == 'refund_pending') {
                //update the invoices table
                $table = 'tblinvoices';
                $update = array('status' => 'Refunded','datepaid' => date('Y-m-d H:i:s'));
                try {
                    Capsule::table($table)
                        ->where([
                            ['id', '=', $orderid],
                            ['paymentmethod', '=', 'bitpaycheckout'],
                        ])
                        ->update($update);
                } catch (Exception $e) {
                    file_put_contents($file, $e, FILE_APPEND);
                }

                // Update the bitpay invoice table
                $table = '_bitpay_checkout_transactions';
                $update = array('transaction_status' => 'refunded', 'updated_at' => date('Y-m-d H:i:s'));
                try {
                    Capsule::table($table)
                        ->where([
                            ['order_id', '=', $orderid],
                            ['transaction_id', '=', $order_invoice],
                        ])
                        ->update($update);
                } catch (Exception $e) {
                    file_put_contents($file, $e, FILE_APPEND);
                }
                break;
            }
    }

    http_response_code(200);
}
