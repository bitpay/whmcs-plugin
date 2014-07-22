bitpaywhmcs-plugin
==================

# Installation

Extract these files into your whmcs directory (parent directory of
modules/folder)

# Configuration

1. Check that you have set your Domain and WHMCS System URL under whmcs/admin
   > Setup > General Settings
2. Create an API Key in your bitpay account at bitpay.com.
3. In the admin control panel, go to "Setup" > "Payment Gateways", select
   "Bit-pay" in the list of modules and click Activate.
4. Enter your API Key from step 1. 
5. Choose a transaction speed (refer to bitpay's help section for more
   information about these choices).
6. If you see the "Convert To For Processing" option, choose a currency that is
   accepted by Bitpay (e.g. BTC/USD/CAD).
7. If you see the option but such a currency does not appear here, or if don't
   see the option and you are currently accepting a currency that is not
   accepted by Bitpay:
   a. Click "Save Changes."
   b. Create an accepted currency (e.g. USD/BTC/CAD) by going to "Setup" >
      "Currencies," filling out the form and clicking "Add Currency" **NOTE**
      You will have to update the conversion rate manually for BTC, so it's
      advisable here to choose your local currency over BTC since the BTC
      exchange rate update can be automated for your local currency.
   c. Return to "Setup" > "Payment Gateways" and choose this new currency for
      the "Convert To For Processing" setting.
8. Click "Save Changes."

# Usage

When a client chooses the Bitpay payment method, they will be presented with an
invoice showing a button to pay the order.  Upon requesting to pay their order,
the system takes the client to a bitpay.com invoice page where the client is
presented with bitcoin payment instructions.  Once payment is received, a link
is presented to the shopper that will return them to your website.

In your Admin control panel, you can see the information associated with each
order made via Bitpay ("Orders" > "Pending Orders").  This screen will tell
you whether payment has been received by the bitpay servers.  

**NOTE** This extension does not provide a means of automatically pulling a
current BTC exchange rate for presenting BTC prices to shoppers.  If you want to
have a BTC currency in your installation, you must update the exchange rate
manually.

# Support

## BitPay Support

* [GitHub Issues](https://github.com/bitpay/whmcs-plugin/issues)
  * Open an issue if you are having issues with this plugin.
* [Support](https://support.bitpay.com)
  * BitPay merchant support documentation

## WHMCS Support

* [Homepage](https://www.whmcs.com/)
* [Documentation](http://docs.whmcs.com/Main_Page)
* [SupportForums](http://forum.whmcs.com/)

# Contribute

To contribute to this project, please fork and submit a pull request.

# License

The MIT License (MIT)

Copyright (c) 2011-2014 BitPay

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
