<?php

/**
 * BitPay Checkout IPN 3.0.1.9
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

function checkInvoiceStatus($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$all_data = json_decode(file_get_contents("php://input"), true);
$file = 'bitpay.txt';

file_put_contents($file,"===========INCOMING IPN=========================",FILE_APPEND);
file_put_contents($file,date('d.m.Y H:i:s'),FILE_APPEND);
file_put_contents($file,print_r($all_data, true),FILE_APPEND);
file_put_contents($file,"===========END OF IPN===========================",FILE_APPEND);
    
$data = $all_data['data'];
$order_status = $data['status'];
$order_invoice = $data['id'];
$endpoint = $gatewayParams['bitpay_checkout_endpoint'];

if($endpoint == "Test"):
    $url_check = 'https://test.bitpay.com/invoices/'.$order_invoice;
else:
    $url_check = 'https://www.bitpay.com/invoices/'.$order_invoice;
endif;
$invoiceStatus = json_decode(checkInvoiceStatus($url_check));

if($order_status != $invoiceStatus->data->status):
    #ipn doesnt match data, stop
    die();
endif;

$event = $all_data['event'];
$orderid = $invoiceStatus->data->orderId;
$price = $invoiceStatus->data->price;

#first see if the ipn matches
#get the user id first
$table = "_bitpay_checkout_transactions";
$fields = "order_id,transaction_id";
$where = array("order_id" => $orderid,"transaction_id" => $order_invoice);

$result = select_query($table, $fields, $where);
$rowdata = mysql_fetch_array($result);
$btn_id = $rowdata['transaction_id'];
if($btn_id):
switch ($event['name']) {
     #complete, update invoice table to Paid
     case 'invoice_completed':
     
        $table = "tblinvoices";
        $update = array("status" => 'Paid','datepaid' => date("Y-m-d H:i:s"));
        $where = array("id" => $orderid, "paymentmethod" => "bitpaycheckout");
        try{
        update_query($table, $update, $where);
        }
        catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }

        #update the bitpay_invoice table
        $table = "_bitpay_checkout_transactions";
        $update = array("transaction_status" => $event['name']);
        $where = array("order_id" => $orderid, "transaction_id" => $order_invoice);
        try{
        update_query($table, $update, $where);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }

      addInvoicePayment(
        $orderid,
        $order_invoice,
        $price,
        0,
        'bitpaycheckout'
    );

     break;
     
     #processing - put in Payment Pending
     case 'invoice_paidInFull':
        $table = "tblinvoices";
        $update = array("status" => 'Payment Pending','datepaid' => date("Y-m-d H:i:s"));
        $where = array("id" => $orderid, "paymentmethod" => "bitpaycheckout");
        try{
        update_query($table, $update, $where);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }

        #update the bitpay_invoice table
        $table = "_bitpay_checkout_transactions";
        $update = array("transaction_status" => $event['name']);
        $where = array("order_id" => $orderid, "transaction_id" => $order_invoice);
        try{
        update_query($table, $update, $where);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }
     break;
     
     #confirmation error - put in Unpaid
     case 'invoice_failedToConfirm':
        $table = "tblinvoices";
        $update = array("status" => 'Unpaid');
        $where = array("id" => $orderid, "paymentmethod" => "bitpaycheckout");
        try{
        update_query($table, $update, $where);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }

        #update the bitpay_invoice table
        $table = "_bitpay_checkout_transactions";
        $update = array("transaction_status" => $event['name']);
        $where = array("order_id" => $orderid, "transaction_id" => $order_invoice);
        try{
        update_query($table, $update, $where);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }

     break;
     
     #expired, remove from transaction table, wont be in invoice table
     case 'invoice_expired':
        #delete any orphans
        $table = "_bitpay_checkout_transactions";
        $delete = 'DELETE FROM _bitpay_checkout_transactions WHERE transaction_id = "' . $order_invoice.'"';
        try{
        full_query($delete);
        }catch (Exception $e ){
         file_put_contents($file,$e,FILE_APPEND);
      }
     break;
     
     #update both table to refunded
     case 'invoice_refundComplete':

        #get the user id first
        $table = "tblaccounts";
        $fields = "id,userid";
        $where = array("transid" => $order_invoice);
        $result = select_query($table, $fields, $where);
        $rowdata = mysql_fetch_array($result);
        $id = $rowdata['id'];
        $userid = $rowdata['userid'];


        #do an insert on tblaccounts
        $values = array("userid" => $userid, "description" => "BitPay Refund of Transaction ID: ".$order_invoice, "amountin" => "0","currency"=>"0","amountout" => $price,"invoiceid" =>$orderid,"date"=>date("Y-m-d H:i:s"));
        $newid = insert_query($table, $values);

        #update the tblinvoices to show Refunded
        $table = "tblinvoices";
        $update = array("status" => 'Refunded','datepaid' => date("Y-m-d H:i:s"));
        $where = array("id" => $orderid, "paymentmethod" => "bitpaycheckout");
        update_query($table, $update, $where);

        #update the bitpay_invoice table
        $table = "_bitpay_checkout_transactions";
        $update = array("transaction_status" => $event['name']);
        $where = array("order_id" => $orderid, "transaction_id" => $order_invoice);
        update_query($table, $update, $where);

     break;
}
endif;#end of the table lookup
