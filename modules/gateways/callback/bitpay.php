<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2011-2014 BitPay
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

# Required File Includes
include '../../../dbconnect.php';
include '../../../includes/functions.php';
include '../../../includes/gatewayfunctions.php';
include '../../../includes/invoicefunctions.php';

require_once '../bit-pay/bp_lib.php';

$gatewaymodule = "bitpay";
$GATEWAY       = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) {
    logTransaction($GATEWAY["name"], $_POST, 'Not activated');
    bpLog('bitpay module not activated');
    die("Bitpay module not activated");
}

$response = bpVerifyNotification($GATEWAY['apiKey'], $GATEWAY['network']);

if (is_string($response) || is_null($response)) {
    logTransaction($GATEWAY["name"], $_POST, $response);
    die($response);
} else {
    $invoiceid = $response['posData'];
    # Checks invoice ID is a valid invoice number or ends processing
    $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]);

    $transid = $response['id'];
    checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does

    # Successful
    $fee = 0;
    $amount = ''; // left blank, this will auto-fill as the full balance
    switch ($response['status']) {
    case "paid":
        logTransaction($GATEWAY["name"], $response, "The payment has been received, but the transaction has not been confirmed on the bitcoin network. This will be updated when the transaction has been confirmed.");
        break;
    case "confirmed":
        addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule); # Apply Payment to Invoice
        logTransaction($GATEWAY["name"], $response, "The payment has been received, and the transaction has been confirmed on the bitcoin network. This will be updated when the transaction has been completed.");
        break;
    case "complete":
        addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule); # Apply Payment to Invoice
        logTransaction($GATEWAY["name"], $response, "The transaction is now complete.");
        break;
    }
}
