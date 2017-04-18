<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2011-2015 BitPay
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

use WHMCS\Database\Capsule;

// Required File Includes
include '../../../includes/functions.php';
include '../../../includes/gatewayfunctions.php';
include '../../../includes/invoicefunctions.php';

if (file_exists('../../../dbconnect.php')) {
    include '../../../dbconnect.php';
} else if (file_exists('../../../init.php')) {
    include '../../../init.php';
} else {
    bpLog('[ERROR] In modules/gateways/bitpay/createinvoice.php: include error: Cannot find dbconnect.php or init.php');
    die('[ERROR] In modules/gateways/bitpay/createinvoice.php: include error: Cannot find dbconnect.php or init.php');
}

require_once '../bit-pay/bp_lib.php';

$gatewaymodule = 'bitpay';
$GATEWAY       = getGatewayVariables($gatewaymodule);

if (!$GATEWAY['type']) {
    logTransaction($GATEWAY['name'], $_POST, 'Not activated');
    bpLog('[ERROR] In modules/gateways/callback/bitpay.php: bitpay module not activated');
    die('[ERROR] In modules/gateways/callback/bitpay.php: Bitpay module not activated.');
}

$response = bpVerifyNotification($GATEWAY['apiKey'], $GATEWAY['network']);

if (true === is_string($response) || true === empty($response)) {
    logTransaction($GATEWAY['name'], $_POST, $response);
    die('[ERROR] In modules/gateways/callback/bitpay.php: Invalid response received: ' . $response);
} else {
    $invoiceid = $response['posData'];

    // Checks invoice ID is a valid invoice number or ends processing
    $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY['name']);

    $transid = $response['id'];
    
    
    $invoice = Capsule::table('tblinvoices')->where('id', $invoiceid)->first();
    
    $userid = $invoice->userid;

    // Checks transaction number isn't already in the database and ends processing if it does
    checkCbTransID($transid);

    $amount = $response['price'];
    $fee = bcmul($response['price'], "0.01",4);

    switch ($response['status']) {
        case 'paid':
            // New payment, not confirmed
            logTransaction($GATEWAY['name'], $response, 'The payment has been received, but the transaction has not been confirmed on the bitcoin network. This will be updated when the transaction has been confirmed.');
            break;
        case 'confirmed':
            // Apply Payment to Invoice
            Capsule::table('tblclients')->where('id', $userid)->update(array('defaultgateway' => $gatewaymodule));
            addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule);
            logTransaction($GATEWAY['name'], $response, 'The payment has been received, and the transaction has been confirmed on the bitcoin network. This will be updated when the transaction has been completed.');
            break;
        case 'complete':
            // Apply Payment to Invoice
            Capsule::table('tblclients')->where('id', $userid)->update(array('defaultgateway' => $gatewaymodule));
            addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule);
            logTransaction($GATEWAY['name'], $response, 'The transaction is now complete.');
            break;
        case 'expired':
        case 'invalid':
            // Bad payment transaction
            logTransaction($GATEWAY['name'], $response, 'The transaction is invalid. Do not process this order!');
            break;
        default:
            logTransaction($GATEWAY['name'], $response, 'Unknown response received.');
    }
}
