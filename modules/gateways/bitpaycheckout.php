<?php
/**
 * BitPay Checkout 5.1.2
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

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require(__DIR__ . DIRECTORY_SEPARATOR . 'bitpaycheckout'
                     . DIRECTORY_SEPARATOR .  'vendor'
                     . DIRECTORY_SEPARATOR . 'autoload.php');

use BitPaySDK\PosClient;
use BitPaySDK\Model\Invoice\Invoice;
use BitPaySDK\Model\Invoice\Buyer;
use BitPaySDK\Model\Facade;
use WHMCS\Database\Capsule;

// Create a new table, if it doesn't exist
if (!Capsule::schema()->hasTable('_bitpay_checkout_transactions')) {
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
        echo "Unable to create my_table: {$e->getMessage()}";
    }
}

// Earlier versions wrote raw IPN payloads to these files inside the web root
// (4.x in bitpaycheckout/, 5.x in bitpaycheckout/callback/). Nothing reads them, so remove them.
foreach (array('bitpaycheckout', 'bitpaycheckout/callback') as $bitpayLegacyDir) {
    foreach (array('bitpay.txt', 'bitpay_err.txt') as $bitpayLegacyFile) {
        $bitpayLegacyPath = __DIR__ . '/' . $bitpayLegacyDir . '/' . $bitpayLegacyFile;
        if (is_file($bitpayLegacyPath)) {
            @unlink($bitpayLegacyPath);
        }
    }
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
 *
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 *
 * @return array
 */

if (!function_exists('bitpaycheckout_config')) {
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
                // @phpcs:ignore Generic.Files.LineLength.TooLong
                'Description' => 'Your <b>development</b> merchant token.  <a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> `Require Authentication`.',
            ),
            // a text field type allows for single line text input
            'bitpay_checkout_token_prod' => array(
                'FriendlyName' => 'Production Token',
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                // @phpcs:ignore Generic.Files.LineLength.TooLong
                'Description' => 'Your <b>production</b> merchant token.  <a href = "https://bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a> and <b>uncheck</b> `Require Authentication`.',
            ),
        
                'bitpay_checkout_endpoint' => array(
                'FriendlyName' => 'Endpoint',
                'Type' => 'dropdown',
                'Options' => 'Test,Production',
                // @phpcs:ignore Generic.Files.LineLength.TooLong
                'Description' => 'Select <b>Test</b> for testing the plugin, <b>Production</b> when you are ready to go live.<br>',
            ),
            'bitpay_checkout_mode' => array(
                'FriendlyName' => 'Payment UX',
                'Type' => 'dropdown',
                'Options' => 'Modal,Redirect',
                // @phpcs:ignore Generic.Files.LineLength.TooLong
                'Description' => 'Select <b>Modal</b> to keep the user on the invoice page, or  <b>Redirect</b> to have them view the invoice at BitPay.com, and be redirected after payment.<br>',
            ),
        );
    }
}

function bitpaycheckout_link($config_params)
{
    $bitpay_checkout_endpoint = $config_params['bitpay_checkout_endpoint'];
    if ($bitpay_checkout_endpoint == 'Test') {
        $bitpay_checkout_token = $config_params['bitpay_checkout_token_dev'];
    } else {
        $bitpay_checkout_token = $config_params['bitpay_checkout_token_prod'];
    }
    $bitpay_checkout_mode = $config_params['bitpay_checkout_mode'];

    $curpage = basename($_SERVER['SCRIPT_FILENAME']);
    
    $curpage = str_replace("/", "", $curpage);
    if ($curpage != 'viewinvoice.php') {
        return;
    }
    ?>
    <script src="https://bitpay.com/bitpay.min.js" type="text/javascript"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    <?php
    $platformInfo = 'BitPay_WHCMS_v5.1.2';
    $client = new PosClient($bitpay_checkout_token, $bitpay_checkout_endpoint, $platformInfo);

    // Check to make sure we don't already have a valid BitPay Invoice active
    $checkDup = Capsule::table('_bitpay_checkout_transactions')
        ->select('transaction_id')
        ->where('order_id', '=', $config_params['invoiceid'])
        ->first();
    if ($checkDup->transaction_id) {
        $basicInvoice = $client->getInvoice($checkDup->transaction_id, Facade::POS, false);
    } else {
        // Invoice Parameters
        $invoiceId = $config_params['invoiceid'];
        $description = $config_params['description'];
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

        // BITPAY INVOICE DETAILS
        $params = new stdClass();

        $dir = dirname($_SERVER['REQUEST_URI']);
        if ($dir == '/') {
            $dir = '';
        }

        $params->orderId = trim($invoiceId);
        // @phpcs:ignore Generic.Files.LineLength.TooLong
        $protocol = 'https://';
        // @phpcs:ignore Generic.Files.LineLength.TooLong
        $params->notificationURL = $protocol . $_SERVER['SERVER_NAME']. $dir . '/modules/gateways/bitpaycheckout/callback/bitpaycheckout_ipn.php';
        // @phpcs:ignore Generic.Files.LineLength.TooLong
        $params->redirectURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $params->fullNotifications = true;

        // @phpcs:ignore Generic.Files.LineLength.TooLong
        $notificationURL = $protocol . $_SERVER['SERVER_NAME'] . $dir . '/modules/gateways/bitpaycheckout/callback/bitpaycheckout_ipn.php';
        // @phpcs:ignore Generic.Files.LineLength.TooLong
        $redirectURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $invoice = new Invoice($amount, $currencyCode);
        $invoice->setOrderId($invoiceId);
        $invoice->setFullNotifications(true);
        $invoice->setExtendedNotifications(true);
        $invoice->setNotificationURL($params->notificationURL);
        $invoice->setRedirectURL($params->redirectURL);
        $invoice->setItemDesc($description);
        $invoice->setNotificationEmail($email);

        $buyer = new Buyer();
        $buyer->setName($firstname . ' ' . $lastname);
        $buyer->setEmail($email);
        $buyer->setAddress1($address1);
        $buyer->setAddress2($address2);
        $buyer->setCountry($country);
        $buyer->setLocality($city);
        $buyer->setNotify(true);
        $buyer->setPhone($phone);
        $buyer->setPostalCode($postcode);
        $buyer->setRegion($state);

        $invoice->setBuyer($buyer);
        $basicInvoice = $client->createInvoice($invoice, Facade::POS, false);

        error_log('=======USER LOADED BITPAY CHECKOUT INVOICE=====');
        error_log(date('d.m.Y H:i:s'));
        error_log('=======END OF INVOICE==========================');
        
        // Insert into the database
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
    
        try {
            $statement = $pdo->prepare(
                // @phpcs:ignore Generic.Files.LineLength.TooLong
                'insert into _bitpay_checkout_transactions (order_id, transaction_id, transaction_status,created_at) values (:order_id, :transaction_id, :transaction_status,:created_at)'
            );

            $statement->execute(
                [
                    ':order_id' => $config_params['invoiceid'],
                    ':transaction_id' => $basicInvoice->getId(),
                    ':transaction_status' => 'new',
                    ':created_at' => date('Y-m-d H:i:s'),
                ]
            );
            $pdo->commit();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $pdo->rollBack();
        }
    }

    if ($bitpay_checkout_mode == 'Modal') {
        // @phpcs:ignore Generic.Files.LineLength.TooLong
        $htmlOutput .= '<button name = "bitpay-payment" class = "btn btn-success btn-sm" onclick = "showModal(\'' . $basicInvoice->getId() . '\');return false;">' . $config_params['langpaynow'] . '</button>';
    } else {
        // @phpcs:ignore Generic.Files.LineLength.TooLong
        $htmlOutput .= '<button name = "bitpay-payment" class = "btn btn-success btn-sm" onclick = "redirectURL(\'' . $basicInvoice->getUrl(). '\');return false;">' . $config_params['langpaynow'] . '</button>';
    }
    ?>

    <script type='text/javascript'>
        function redirectURL($url){
            window.location=$url;
        }

        var payment_status = null;
        var is_paid = false;
        window.addEventListener('message', function(event) {
            payment_status = event.data.status;
            if(payment_status == 'paid' || payment_status == 'confirmed' || payment_status == 'complete'){
                is_paid = true;
            }
            if (is_paid == true) {
                window.location.reload();
            }
        }, false);
        function showModal(){
            // Show the modal
            <?php if ($bitpay_checkout_endpoint == 'Test') : ?>
                bitpay.enableTestMode()
            <?php endif;?>
                bitpay.showInvoice('<?php echo $basicInvoice->getId(); ?>');
        }
    </script>
    <?php
    $htmlOutput .= '</form>';
    return $htmlOutput;
}
?>
