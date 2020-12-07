<?php

/**
 * BitPay Checkout Callback 3.0.1.9
 *
 * This file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
// Require libraries needed for gateway module functions.
require_once  '../../../init.php';
require_once  '../../../includes/gatewayfunctions.php';
require_once  '../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = 'bitpaycheckout';
// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

#print_r($gatewayParams);die();
// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$all_data = (file_get_contents("php://input"));
$all_data=base64_decode($all_data);
$all_data = json_decode($all_data); 


// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$success = true;
$invoiceId = $all_data->data->orderId;
$transactionId =$all_data->data->id;
$paymentAmount = $all_data->data->price;

$transactionStatus = $success ? 'Success' : 'Failure';
/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */
checkCbTransID($transactionId);
/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
if ($success) {

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */

    

#update the tblinvoices table
#update the table to show Payment Pending until IPN updates
$pending_lbl = 'Payment Pending';

$table = "tblinvoices";
$update = array("status" => $pending_lbl,'datepaid' => date("Y-m-d H:i:s"));
$where = array("id" => $all_data->data->orderId, "paymentmethod" => "bitpaycheckout");
update_query($table, $update, $where);

#update the _bitpay_transactions table
$table = "_bitpay_checkout_transactions";
$update = array("transaction_status" => "paid","updated_at" => date("Y-m-d H:i:s"));
$where = array("order_id" => $all_data->data->orderId, "transaction_id" => $transactionId);
update_query($table, $update, $where);


#delete any orphans
$table = "_bitpay_checkout_transactions";
$delete = 'DELETE FROM _bitpay_checkout_transactions WHERE order_id = "'.$all_data->data->orderId.'" AND transaction_status = "new"';
full_query($delete);


}
