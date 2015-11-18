# Using the BitPay payment plugin for WHMCS

## Prerequisites

* Last Version Tested: 5.2.3

You must have a BitPay merchant account to use this plugin.  It's free and easy to [sign-up for a BitPay merchant account](https://bitpay.com/start).



## Installation

Extract these files into the WHMCS directory on your webserver (parent directory of
modules/folder).


## Configuration

1. Take a moment to ensure that you have set your store's domain and the WHMCS System URL under **whmcs/admin > Setup > General Settings**.
2. Create a "Legacy API Key" in your BitPay merchant account dashboard:
  * Log into https://bitpay.com with your account username/password.
  * On the left side of the screen, choose **Settings**.
  * The menu will expand downward revealing a list of options. Choose the **Legacy API Keys** option.
  * On the right side of the page, click on the grey **+ Add New API Key** button to instantly create a new one.
  * Select and copy the entire string for the new API Key ID that you just created. It will look something like this: 43rp4rpa24d6Bz4BR44j8zL44PrU4npVv4DtJA4Kb8.
3. In the admin control panel, go to **Setup > Payment Gateways**, select **Bit-pay** in the list of modules and click **Activate**.
4. Paste the API Key ID string that you created and copied from step 2. 
5. Choose a transaction speed. This setting determines how quickly you will receive a payment confirmation from BitPay after an invoice is paid by a customer.
  * High: A confirmation is sent instantly once the payment has been received by the gateway.
  * Medium: A confirmation is sent after 1 block confirmation (~10 mins) by the bitcoin network.
  * Low: A confirmation is sent after the usual 6 block confirmations (~1 hour) by the bitcoin network.
6. If you see the **Convert To For Processing** option, choose a currency that is accepted by BitPay (e.g. BTC/USD/CAD).  You can see a full list of our supported currencies here: [Bitcoin Exchange Rates](https://bitpay.com/bitcoin-exchange-rates).
7. If you see the option but such a currency does not appear here, or if don't see the option and you are currently accepting a currency that is not accepted by BitPay:
  * Click **Save Changes**.
  * Create an accepted currency (e.g. USD/BTC/CAD) by going to **Setup > Currencies**, filling out the form and clicking **Add Currency**.
    * **NOTE:** You will have to update the conversion rate manually for BTC, so it's advisable here to choose your local currency over BTC since the BTC exchange rate update can be automated for your local currency.
  * Return to **Setup > Payment Gateways** and choose this new currency for the "Convert To For Processing" setting.
8. Click **Save Changes**.

You're done!


## Usage

When a client chooses the BitPay payment method, they will be presented with an invoice showing a button they will have to click on in order to pay their order.  Upon requesting to pay their order, the system takes the client to a full-screen bitpay.com invoice page where the client is presented with payment instructions.  Once payment is received, a link is presented to the shopper that will return them to your website.

**NOTE:** Don't worry!  A payment will automatically update your WHMCS store whether or not the customer returns to your website after they've paid the invoice.

In your WHMCS control panel, you can see the information associated with each order made via BitPay by choosing **Orders > Pending Orders**.  This screen will tell you whether payment has been received by the BitPay servers.  You can also view the details for any paid invoice inside your BitPay merchant dashboard under the **Payments** page.

**NOTE:** This extension does not provide a means of automatically pulling a current BTC exchange rate for presenting BTC prices to shoppers.  If you want to have a BTC currency in your installation, you must update the exchange rate manually.
