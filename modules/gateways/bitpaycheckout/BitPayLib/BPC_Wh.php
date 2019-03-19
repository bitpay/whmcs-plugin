<?php

class BPC_Wh { 

   function __construct() {
    
}

public function BPC_getBitPayToken($endpoint, $config_params)
    {
        //dev or prod token
        switch (strtolower($endpoint)) {
            case 'test':
            default:
                return $config_params['bitpay_checkout_token_dev'];
                break;
            case 'production':
                return $config_params['bitpay_checkout_token_prod'];
                break;
        }

    }

}

?>
