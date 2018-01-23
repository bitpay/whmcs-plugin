<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2011-2018 BitPay
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

include '../../../includes/functions.php';
include '../../../includes/gatewayfunctions.php';
include '../../../includes/invoicefunctions.php';

require 'bp_lib.php';

if (file_exists('../../../dbconnect.php')) {
    include '../../../dbconnect.php';
} else if (file_exists('../../../init.php')) {
    include '../../../init.php';
} else {
    bpLog('[ERROR] In modules/gateways/bitpay/createinvoice.php: include error: Cannot find dbconnect.php or init.php');
    die('[ERROR] In modules/gateways/bitpay/createinvoice.php: include error: Cannot find dbconnect.php or init.php');
}

$gatewaymodule = 'bitpay';

$GATEWAY = getGatewayVariables($gatewaymodule);

// get invoice
$invoiceId = (int) $_POST['invoiceId'];
$price     = $currency = false;
$result    = Capsule::connection()->select("SELECT tblinvoices.total, tblinvoices.status, tblcurrencies.code FROM tblinvoices, tblclients, tblcurrencies where tblinvoices.userid = tblclients.id and tblclients.currency = tblcurrencies.id and tblinvoices.id=$invoiceId");
$data      = (array)$result[0];

if (!$data) {
    bpLog('[ERROR] In modules/gateways/bitpay/createinvoice.php: No invoice found for invoice id #' . $invoiceId);
    die('[ERROR] In modules/gateways/bitpay/createinvoice.php: Invalid invoice id #' . $invoiceId);
}

$price    = $data['total'];
$currency = $data['code'];
$status   = $data['status'];

if ($status != 'Unpaid') {
    bpLog('[ERROR] In modules/gateways/bitpay/createinvoice.php: Invoice status must be Unpaid.  Status: ' . $status);
    die('[ERROR] In modules/gateways/bitpay/createinvoice.php: Bad invoice status of ' . $status);
}

// if convert-to option is set (gateway setting), then convert to requested currency
$convertTo = false;
$query     = "SELECT value from tblpaymentgateways where `gateway` = '$gatewaymodule' and `setting` = 'convertto'";
$result    = Capsule::connection()->select($query);
$data      = (array)$result[0];

if ($data) {
    $convertTo = $data['value'];
}

if ($convertTo) {
    // fetch $currency and $convertTo currencies
    $query           = "SELECT rate FROM tblcurrencies where `code` = '$currency'";
    $result          = Capsule::connection()->select($query);
    $currentCurrency = (array)$result[0];

    if (!$currentCurrency) {
        bpLog('[ERROR] In modules/gateways/bitpay/createinvoice.php: Invalid invoice currency of ' . $currency);
        die('[ERROR] In modules/gateways/bitpay/createinvoice.php: Invalid invoice currency of ' . $currency);
    }

    $result            = Capsule::connection()->select("SELECT code, rate FROM tblcurrencies where `id` = $convertTo");
    $convertToCurrency = (array)$result[0];

    if (!$convertToCurrency) {
        bpLog('[ERROR] In modules/gateways/bitpay/createinvoice.php: Invalid convertTo currency of ' . $convertTo);
        die('[ERROR] In modules/gateways/bitpay/createinvoice.php: Invalid convertTo currency of ' . $convertTo);
    }

    $currency = $convertToCurrency['code'];
    $price    = $price / $currentCurrency['rate'] * $convertToCurrency['rate'];
}

// create invoice
$options = $_POST;

unset($options['invoiceId']);
unset($options['systemURL']);

$options['notificationURL']  = $_POST['systemURL'].'/modules/gateways/callback/bitpay.php';
$options['redirectURL']      = $_POST['systemURL'];
$options['apiKey']           = $GATEWAY['apiKey'];
$options['transactionSpeed'] = $GATEWAY['transactionSpeed'];
$options['currency']         = $currency;
$options['network']          = $GATEWAY['network'];

$invoice                     = bpCreateInvoice($invoiceId, $price, $invoiceId, $options);

if (isset($invoice['error'])) {
    bpLog('[ERROR] In modules/gateways/bitpay/createinvoice.php: Invoice error: ' . var_export($invoice['error'], true));
    die('[ERROR] In modules/gateways/bitpay/createinvoice.php: Invoice error: ' . var_export($invoice['error'], true));
} else {
    header('Location: ' . $invoice['url']);
}
