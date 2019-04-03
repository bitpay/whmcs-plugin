## Integration Requirements


[![Build Status](https://travis-ci.org/bitpay/whmcs-plugin.svg?branch=master)](https://travis-ci.org/bitpay/whmcs-plugin)


This version requires the following:

* WHMCS 7.x
* A BitPay merchant account: 
 * On the [production environment.](https://bitpay.com/dashboard/signup)
 * On the [test environment.](https://test.bitpay.com/dashboard/signup), for sandbox testing.

## Installing the Plugin


1. From your WHMCS business account, go to setup > payments > payment gateways

2. On the next screen, click on the **All Payment Gateways** tab and click on **BitPay Checkout** to enable the plugin. The next step will be to configure it.


## Plugin Configuration

After you have enabled the BitPay plugin, the configuration steps are:

1. Create an API token from your BitPay merchant dashboard
	* Login to your BitPay merchant account and go to the [API token settings](/dashboard/merchant/api-tokens)
	* click on the **Add new token** button: indicate a token label (for instance: *WHMCS*), make sure "Require Authentication" is unchecked and click on the **Add Token** button
	* Copy the token value

2. Log in to your WHMCS admin dashboard, go to System > Configuration > Payment Methods. This will give you access to the BitPay plugin settings:
	* Paste the token value into the appropriate field: **Development Token** for token copied from the sandbox environment (test.bitpay.com) and **Production Token** for token copied from the live environment (bitpay.com)
	* select the endpoint - Test or Production
	* Click **Save Changes** at the bottom of the page

This plugin also includes an IPN (Instant Payment Notification) endpoint that will update your WHMCS invoice status.

An order note will automatically be added with a link to the BitPay invoice to monitor the status:

 * Initially the WHMCS invoice will be in a **Unpaid** status when it is initially created.
 * After the invoice is paid by the user, it will change to a **Payment Pending** status. 
 * When BitPay finalizes the transaction, it will change to a **Paid** status, and your order will be safe to ship, allow access to downloadable products, etc.
 * If you decide to refund a payment via your BitPay dashboard, the WHMCS invoice status will change to **Refunded** once the refund is executed.


