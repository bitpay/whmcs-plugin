<?php

class BPC_Configuration { 
   private $apiToken;
   private $network;

   function __construct( $apiToken, $network = null) {
    $this->apiToken = $apiToken;
    if($network == 'test' || $network == null):
        $this->network = $this->BPC_getApiHostDev();
    else:
        $this->network = $this->BPC_getApiHostProd();
    endif;
}

function BPC_getAPIToken() {
    return $this->apiToken;
}

function BPC_getNetwork() {
    return $this->network;
}

public function BPC_getApiHostDev()
{
    return 'test.bitpay.com';
}

public function BPC_getApiHostProd()
{
    return 'bitpay.com';
}

public function BPC_getApiPort()
{
    return 443;
}

public function BPC_getInvoiceURL(){
    return $this->network.'/invoices';
}


} 
?>
