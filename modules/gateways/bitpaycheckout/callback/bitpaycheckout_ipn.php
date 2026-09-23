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

/**
 * The notification tells us what happened. The invoice we fetch back from BitPay
 * tells us whether that is consistent. BitPay may already be ahead of the event
 * we are processing, but it must never be behind it.
 *
 * Mismatches are recorded through logTransaction so they show up under
 * Billing > Gateway Log in the admin area.
 */
function bitpayStatusAllowed($serverStatus, array $allowed, $eventName, $invoiceId, $payload)
{
    if (in_array($serverStatus, $allowed, true)) {
        return true;
    }

    $reason = "Ignored {$eventName} for invoice {$invoiceId}: BitPay reports status '{$serverStatus}',"
        . ' expected one of ' . implode(', ', $allowed);
    logTransaction('bitpaycheckout', $payload, $reason);

    return false;
}

/**
 * Move the transaction row forward only if it is still in one of the $from states.
 * The check and the write happen in one UPDATE, so two callbacks racing on the
 * same invoice cannot both win. Returns true only for the callback that moved it.
 */
function bitpayAdvanceStatus($orderId, $invoiceId, array $from, $to)
{
    try {
        $affected = Capsule::table('_bitpay_checkout_transactions')
            ->where([
                ['order_id', '=', $orderId],
                ['transaction_id', '=', $invoiceId],
            ])
            ->whereIn('transaction_status', $from)
            ->update(array('transaction_status' => $to, 'updated_at' => date('Y-m-d H:i:s')));
    } catch (Exception $e) {
        logTransaction('bitpaycheckout', $e->getMessage(), "Failed to move invoice {$invoiceId} to {$to}");
        return false;
    }

    return $affected === 1;
}

$response = json_decode(file_get_contents("php://input"), true);
$data = $response['data'];
$event = isset($response['event']) && is_array($response['event']) ? $response['event'] : array();
$eventName = isset($event['name']) ? $event['name'] : '';

$order_status = $data['status'];
$order_invoice = $data['id'];
$endpoint = $gatewayParams['bitpay_checkout_endpoint'];
if ($endpoint == 'Test') {
    $url_check = TEST_URL . $order_invoice;
} else {
    $url_check = PROD_URL . $order_invoice;
}
$invoiceStatus = json_decode(checkInvoiceStatus($url_check));

$hasInvoice = $invoiceStatus
    && isset($invoiceStatus->data)
    && isset($invoiceStatus->data->status)
    && isset($invoiceStatus->data->orderId)
    && isset($invoiceStatus->data->price);

if (!$hasInvoice) {
    logTransaction($gatewayModuleName, $response, "Unable to verify invoice {$order_invoice} with BitPay");
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
    switch ($eventName) {
        // Paid but not yet confirmed on chain. Park the invoice in Payment Pending.
        case 'invoice_paidInFull':
            $allowed = ['paid', 'confirmed', 'complete'];
            if (!bitpayStatusAllowed($serverStatus, $allowed, $eventName, $order_invoice, $response)) {
                break;
            }

            // Only from 'new'. A late or retried paidInFull must not undo a payment
            // that invoice_confirmed or invoice_completed already applied.
            if (bitpayAdvanceStatus($orderid, $order_invoice, ['new'], 'paid')) {
                try {
                    Capsule::table('tblinvoices')
                        ->where([
                            ['id', '=', $orderid],
                            ['paymentmethod', '=', 'bitpaycheckout'],
                            ['status', '=', 'Unpaid'],
                        ])
                        ->update(array('status' => 'Payment Pending', 'datepaid' => date('Y-m-d H:i:s')));
                } catch (Exception $e) {
                    logTransaction($gatewayModuleName, $e->getMessage(), 'Database update failed');
                }
            }
            break;

        // Enough confirmations for the merchant's transaction speed. Apply the payment.
        case 'invoice_confirmed':
            $allowed = ['confirmed', 'complete'];
            if (!bitpayStatusAllowed($serverStatus, $allowed, $eventName, $order_invoice, $response)) {
                break;
            }

            if (bitpayAdvanceStatus($orderid, $order_invoice, ['new', 'paid'], 'confirmed')) {
                addInvoicePayment($orderid, $order_invoice, $price, 0, 'bitpaycheckout');
            }
            break;

        // Settled on BitPay's side. Apply the payment unless invoice_confirmed already did.
        case 'invoice_completed':
            $allowed = ['complete'];
            if (!bitpayStatusAllowed($serverStatus, $allowed, $eventName, $order_invoice, $response)) {
                break;
            }

            if (bitpayAdvanceStatus($orderid, $order_invoice, ['new', 'paid'], 'complete')) {
                // invoice_confirmed never ran for this invoice, so the payment is still ours to apply.
                addInvoicePayment($orderid, $order_invoice, $price, 0, 'bitpaycheckout');
            } else {
                // Already applied by invoice_confirmed. Just record that it settled.
                bitpayAdvanceStatus($orderid, $order_invoice, ['confirmed'], 'complete');
            }
            break;

        // Expired, remove from transaction table, wont be in invoice table
        case 'invoice_expired':
            $allowed = ['expired'];
            if (!bitpayStatusAllowed($serverStatus, $allowed, $eventName, $order_invoice, $response)) {
                break;
            }

            $table = '_bitpay_checkout_transactions';
            try {
                Capsule::table($table)
                    ->where('transaction_id', '=', $order_invoice)
                    ->delete();
            } catch (Exception $e) {
                logTransaction($gatewayModuleName, $e->getMessage(), 'Database update failed');
            }
            break;

        // Refunded, set invoice and bitpay transaction to refunded status
        case 'invoice_refundComplete':
            $table = 'tblinvoices';
            $update = array('status' => 'Refunded', 'datepaid' => date('Y-m-d H:i:s'));
            try {
                Capsule::table($table)
                    ->where([
                        ['id', '=', $orderid],
                        ['paymentmethod', '=', 'bitpaycheckout'],
                    ])
                    ->update($update);
            } catch (Exception $e) {
                logTransaction($gatewayModuleName, $e->getMessage(), 'Database update failed');
            }

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
                logTransaction($gatewayModuleName, $e->getMessage(), 'Database update failed');
            }
            break;

        default:
            logTransaction($gatewayModuleName, $response, "No handler for event '{$eventName}'");
            break;
    }

    http_response_code(200);
}
