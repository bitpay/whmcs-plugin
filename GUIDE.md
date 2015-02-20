# Using the BitPay plugin for WHMCS

## Prerequisites
You must have a BitPay merchant account to use this plugin.  It's free to [sign-up for a BitPay merchant account](https://bitpay.com/start).



## Installation

Extract these files into your whmcs directory (parent directory of
modules/folder)

## Configuration

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

## Usage

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
