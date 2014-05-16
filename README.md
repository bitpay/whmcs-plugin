bitpayWHMCS
===========

<strong>©2012-2014 BITPAY, INC.</strong>

Permission is hereby granted to any person obtaining a copy of this software
and associated documentation for use and/or modification in association with
the bitpay.com service.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

Bitcoin payment module for WHMCS using the bitpay.com service.

Installation
------------
Extract these files into your whmcs directory (parent directory of modules/ folder)

Configuration
-------------
1. Check that you have set your Domain and WHMCS System URL under whmcs/admin > Setup > General Settings
1. Create an API Key in your bitpay account at bitpay.com.
2. In the admin control panel, go to "Setup" > "Payment Gateways", select "Bit-pay" in the list of modules and click Activate.
3. Enter your API Key from step 1. 
4. Choose a transaction speed (refer to bitpay's help section for more information about these choices).
5. If you see the "Convert To For Processing" option, choose a currency that is accepted by Bitpay (e.g. BTC/USD/CAD).
6. If you see the option but such a currency does not appear here, or if don't see the option and you are currently accepting a currency that is not accepted by Bitpay:<br />
a. Click "Save Changes."<br />
b. Create an accepted currency (e.g. USD/BTC/CAD) by going to "Setup" > "Currencies," filling out the form and clicking "Add Currency"<br />
<strong>Note:</strong> You will have to update the conversion rate manually for BTC, so it's advisable here to choose your local currency over BTC since the BTC exchange rate update can be automated for your local currency.<br />
c. Return to "Setup" > "Payment Gateways" and choose this new currency for the "Convert To For Processing" setting.
7. Click "Save Changes."


Usage
-----
When a client chooses the Bitpay payment method, they will be presented with an invoice showing a button to pay the order.  Upon requesting to pay their order, the system takes the client to a bitpay.com invoice page where the client is presented with bitcoin payment instructions.  Once payment is received, a link is presented to the shopper that will return them to your website.

In your Admin control panel, you can see the information associated with each order made via Bitpay ("Orders" > "Pending Orders").  This screen will tell you whether payment has been received by the bitpay servers.  

<strong>Note:</strong> This extension does not provide a means of automatically pulling a current BTC exchange rate for presenting BTC prices to shoppers.  If you want to have a BTC currency in your installation, you must update the exchange rate manually.

Change Log
----------
Version 1
- Initial version, tested against WHMCS 4.5.2

Version 2 (9/'12)
- Updated to use new API key instead of SSL files.  Tested against WHMCS 5.2.3.

Version 3 (03/14)
- Added new HTTP header for version tracking.

Version 4 (05/14)
- Fix to use server error handling.
