<?php
/**
 * BitPay Checkout 3.0.1.9
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "bitpaycheckout" and therefore all functions
 * begin "bitpaycheckout_".
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

#create the transaction table
use WHMCS\Database\Capsule;

// Create a new table.
try {
    Capsule::schema()->create(
        '_bitpay_checkout_transactions',
        function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('order_id');
            $table->string('transaction_id');
            $table->string('transaction_status');
            $table->timestamps();
        }
    );
} catch (\Exception $e) {
    #echo "Unable to create my_table: {$e->getMessage()}";
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */

function bitpaycheckout_MetaData()
{
    return array(
        'DisplayName' => 'BitPay_Checkout_WHMCS',
        'APIVersion' => '3.0.1.9', // Use API Version 1.1
        'DisableLocalCreditCardInput' => false,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 *
 * @return array
 */

function bitpaycheckout_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'BitPay Checkout',
        ),
        // a text field type allows for single line text input
        'bitpay_checkout_token_dev' => array(
            'FriendlyName' => 'Development Token',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Your <b>development</b> merchant token.  <a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> `Require Authentication`.',
        ),
        // a text field type allows for single line text input
        'bitpay_checkout_token_prod' => array(
            'FriendlyName' => 'Production Token',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Your <b>production</b> merchant token.  <a href = "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> `Require Authentication`.',
        ),
       
            'bitpay_checkout_endpoint' => array(
            'FriendlyName' => 'Endpoint',
            'Type' => 'dropdown',
            'Options' => 'Test,Production',
            'Description' => 'Select <b>Test</b> for testing the plugin, <b>Production</b> when you are ready to go live.<br>',
        ),
        'bitpay_checkout_mode' => array(
            'FriendlyName' => 'Payment UX',
            'Type' => 'dropdown',
            'Options' => 'Modal,Redirect',
            'Description' => 'Select <b>Modal</b> to keep the user on the invoice page, or  <b>Redirect</b> to have them view the invoice at BitPay.com, and be redirected after payment.<br>',
        ),
        

    );
}

function BPC_autoloader($class)
{
    if (strpos($class, 'BPC_') !== false):
        if (!class_exists('BitPayLib/' . $class, false)):
            #doesnt exist so include it
            include_once 'bitpaycheckout/BitPayLib/' . $class . '.php';
        endif;
    endif;

}
spl_autoload_register('BPC_autoloader');

function bitpaycheckout_link($config_params)
{
    $curpage = basename($_SERVER["SCRIPT_FILENAME"]);
    
    $curpage = str_replace("/", "", $curpage);
    if ($curpage != 'viewinvoice.php'): return;endif;
    ?>
<script src="//bitpay.com/bitpay.min.js" type="text/javascript"></script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<?php

    // Invoice Parameters
    $invoiceId = $config_params['invoiceid'];
    $description = $config_params["description"];
    $amount = $config_params['amount'];
    $currencyCode = $config_params['currency'];
    // Client Parameters
    $firstname = $config_params['clientdetails']['firstname'];
    $lastname = $config_params['clientdetails']['lastname'];
    $email = $config_params['clientdetails']['email'];
    $address1 = $config_params['clientdetails']['address1'];
    $address2 = $config_params['clientdetails']['address2'];
    $city = $config_params['clientdetails']['city'];
    $state = $config_params['clientdetails']['state'];
    $postcode = $config_params['clientdetails']['postcode'];
    $country = $config_params['clientdetails']['country'];
    $phone = $config_params['clientdetails']['phonenumber'];
    // System Parameters
    $companyName = $config_params['companyname'];
    $systemUrl = $config_params['systemurl'];
    $returnUrl = $config_params['returnurl'];
    $langPayNow = $config_params['langpaynow'];
    $moduleDisplayName = $config_params['name'];
    $moduleName = $config_params['paymentmethod'];


    $whmcsVersion = $config_params['whmcsVersion'];
    $postfields = array();
    $postfields['username'] = $username;
    $postfields['invoice_id'] = $invoiceId;
    $postfields['description'] = $description;
    $postfields['amount'] = $amount;
    $postfields['currency'] = $currencyCode;
    $postfields['first_name'] = $firstname;
    $postfields['last_name'] = $lastname;
    $postfields['email'] = $email;
    $postfields['address1'] = $address1;
    $postfields['address2'] = $address2;
    $postfields['city'] = $city;
    $postfields['state'] = $state;
    $postfields['postcode'] = $postcode;
    $postfields['country'] = $country;
    $postfields['phone'] = $phone;
    $postfields['return_url'] = $returnUrl;
    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    }

    #BITPAY INVOICE DETAILS
    $wh = new BPC_Wh();
    $config_params['bitpay_checkout_endpoint'] = strtolower($config_params['bitpay_checkout_endpoint']);
    $config_params['bitpay_checkout_mode'] = strtolower($config_params['bitpay_checkout_mode']);


    $bitpay_checkout_token = $wh->BPC_getBitPayToken($config_params['bitpay_checkout_endpoint'], $config_params);
    $bitpay_checkout_endpoint = $config_params['bitpay_checkout_endpoint'];
    $bitpay_checkout_mode = $config_params['bitpay_checkout_mode'];


    $config = new BPC_Configuration($bitpay_checkout_token, $config_params['bitpay_checkout_endpoint']);

    $params = new stdClass();
    $dir = dirname($_SERVER['REQUEST_URI']);
    if ($dir == '/') {
        $dir = '';
    }
   #$protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https://' : 'http://';
    $protocol = 'https://';

    $callback_url = $protocol . $_SERVER['SERVER_NAME'] . $dir . '/modules/gateways/bitpaycheckout/bitpaycheckout_callback.php';
    $params->extension_version = bitpaycheckout_MetaData();
    $params->extension_version = $params->extension_version['DisplayName'] . '_' . $params->extension_version['APIVersion'];
    $params->price = $amount;
    $params->currency = $currencyCode;
    $params->orderId = trim($invoiceId);

    $params->notificationURL = $protocol . $_SERVER['SERVER_NAME'] . $dir . '/modules/gateways/bitpaycheckout/bitpaycheckout_ipn.php';
    $params->redirectURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    $params->extendedNotifications = true;
    
    #set the transaction speed in the plugin and override the plugin
    
    $params->acceptanceWindow = 1200000;
    if (!empty($email)):
        $buyerInfo = new stdClass();
        $buyerInfo->name = $firstname . ' ' . $lastname;
        $buyerInfo->email = $email;
        $params->buyer = $buyerInfo;
    endif;

    $item = new BPC_Item($config, $params);
    $invoice = new BPC_Invoice($item);
    //this creates the invoice with all of the config params from the item
    $invoice->BPC_createInvoice();
    $invoiceData = json_decode($invoice->BPC_getInvoiceData());
    $invoiceID = $invoiceData->data->id;

    

        error_log("=======USER LOADED BITPAY CHECKOUT INVOICE=====");
        error_log(date('d.m.Y H:i:s'));
       # error_log(print_r($invoiceData, true));
        error_log("=======END OF INVOICE==========================");
        error_log(print_r($params,true));
    
    #insert into the database
    $pdo = Capsule::connection()->getPdo();
    $pdo->beginTransaction();
   
    $created_at = 'Y-m-d';
    


    try {
        $statement = $pdo->prepare(
            'insert into _bitpay_checkout_transactions (order_id, transaction_id, transaction_status,created_at) values (:order_id, :transaction_id, :transaction_status,:created_at)'
        );

        $statement->execute(
            [
                ':order_id' => $params->orderId,
                ':transaction_id' => $invoiceID,
                ':transaction_status' => 'new',
                ':created_at' => date($created_at.' H:i:s'),
            ]
        );
        $pdo->commit();
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $pdo->rollBack();
    }
    if($bitpay_checkout_mode == 'modal'):
    $htmlOutput .= '<button name = "bitpay-payment" class = "btn btn-success btn-sm" onclick = "showModal(\'' . base64_encode($invoice->BPC_getInvoiceData()) . '\');return false;">' . $langPayNow . '</button>';
    else:
        $htmlOutput .= '<button name = "bitpay-payment" class = "btn btn-success btn-sm" onclick = "redirectURL(\'' . $invoiceData->data->url. '\');return false;">' . $langPayNow . '</button>';

    endif;
    ?>



<script type='text/javascript'>
function redirectURL($url){
    window.location=$url;
}
function showModal(invoiceData) {
    $post_url = '<?php echo $callback_url; ?>'
    $idx = $post_url.indexOf('https')
    if($idx == -1 && location.protocol == 'https:'){
        $post_url = $post_url.replace('http','https')
    }
    
    
    $encodedData = invoiceData
    invoiceData = atob(invoiceData);

    var payment_status = null;
    var is_paid = false
    window.addEventListener("message", function(event) {
        payment_status = event.data.status;
        if(payment_status == 'paid' || payment_status == 'confirmed' || payment_status == 'complete'){
                is_paid = true
            }
        if (is_paid == true) {
            //just some test stuff
            var saveData = jQuery.ajax({
                type: 'POST',
                url: $post_url,
                data: $encodedData,
                dataType: "text",
                success: function(resultData) {
                    location.reload();
                },
                error: function(resultData) {
                    //console.log('error', resultData)
                }
            });
        }
    }, false);

    //show the modal
    <?php if ($bitpay_checkout_endpoint == 'test'): ?>
    bitpay.enableTestMode()
    <?php endif;?>
    bitpay.showInvoice('<?php echo $invoiceID; ?>');
}
</script>
<?php
$htmlOutput .= '</form>';
    return $htmlOutput;

}
