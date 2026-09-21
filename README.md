## Integration Requirements


[![Build Status](https://travis-ci.org/bitpay/whmcs-plugin.svg?branch=master)](https://travis-ci.org/bitpay/whmcs-plugin)


This version requires the following:

* WHMCS 8.x
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

## Content Security Policy

If your site sends a `Content-Security-Policy` header, the payment flow needs a
few origins allowed. Without them the invoice page still loads, but payment does
not start. In Modal mode the BitPay window stays on "Please wait" with no error
shown.

The plugin loads two scripts on the invoice page:

* `https://bitpay.com/bitpay.min.js`
* `https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js`

With **Payment UX** set to **Modal** it also frames the BitPay invoice. The host
depends on the **Endpoint** setting. The script always comes from `bitpay.com`,
even when the endpoint is Test.

Add these sources to your existing policy. Keep whatever your site already needs.

**Modal**

| Directive | Add |
|---|---|
| `script-src` | `https://bitpay.com` `https://ajax.googleapis.com` |
| `frame-src` | `https://bitpay.com` (Production) or `https://test.bitpay.com` (Test) |

**Redirect**

| Directive | Add |
|---|---|
| `script-src` | `https://bitpay.com` `https://ajax.googleapis.com` |

There is no frame in Redirect mode, so `frame-src` is not needed.

`connect-src` is not needed in either mode. The payment status is polled inside
the BitPay frame, on BitPay's own origin, so your policy does not apply to it.

### Finding the right values for your site

If the payment flow breaks and you are not sure which directive is at fault, set
`Content-Security-Policy-Report-Only` with your current policy instead of
`Content-Security-Policy`. The browser then reports every violation in the
console without blocking anything, so you get the full list in one pass.
