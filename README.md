# Description

Bitcoin payment plugin for WHMCS using the bitpay.com service.

[![Build Status](https://travis-ci.org/bitpay/whmcs-plugin.svg?branch=master)](https://travis-ci.org/bitpay/whmcs-plugin)

## Quick Start Guide

To get up and running with our plugin quickly, see the GUIDE here: https://github.com/bitpay/whmcs-plugin/blob/master/GUIDE.md

## Support

**BitPay Support:**

* Last Version Tested: 5.2.3
* [BitPay Labs](https://labs.bitpay.com/c/plugins/whmcs)
  * Post a question in our discussion forums
* [GitHub Issues](https://github.com/bitpay/whmcs-plugin/issues)
  * Open an issue if you are having issues with this plugin
* [Support](https://support.bitpay.com)
  * BitPay merchant support documentation

**WHMCS Support:**

* [Homepage](https://www.whmcs.com/)
* [Documentation](http://docs.whmcs.com/Main_Page)
* [SupportForums](http://forum.whmcs.com/)

## Troubleshooting

0. Sometimes a download can become corrupted for various reasons.  However, you can verify that the release package you downloaded is correct by checking the md5 checksum "fingerprint" of your download against the md5 checksum value shown on the Releases page.  Even the smallest change in the downloaded release package will cause a different value to be shown!
  * If you are using Windows, you can download a checksum verifier tool and instructions directly from Microsoft here: http://www.microsoft.com/en-us/download/details.aspx?id=11533
  * If you are using Linux or OS X, you already have the software installed on your system.
    * On Linux systems use the md5sum program.  For example:
      * md5sum filename
    * On OS X use the md5 program.  For example:
      * md5 filename
1. Ensure a valid SSL certificate is installed on your server. Also ensure your root CA cert is updated. If your CA cert is not current, you will see curl SSL verification errors.
2. Verify that your web server is not blocking POSTs from servers it may not recognize. Double check this on your firewall as well, if one is being used.
3. Check the version of this plugin against the official plugin repository to ensure you are using the latest version. Your issue might have been addressed in a newer version! See the [Releases](https://github.com/bitpay/whmcs-plugin/releases/latest) page for the latest.
4. If all else fails, contact us using one of the methods described in the Support section above.

**TIP**: When contacting support it will help us is you provide:

* WHMCS and BitPay Plugin Version
* Other plugins you have installed
  * Some plugins do not play nice
* Configuration settings for the plugin (Most merchants take screen grabs)
* Any log files that will help
  * Web server error logs
* Screen grabs of error message if applicable.

## Contribute

Would you like to help with this project?  Great!  You don't have to be a developer, either.  If you've found a bug or have an idea for an improvement, please open an [issue](https://github.com/bitpay/whmcs-plugin/issues) and tell us about it.

If you *are* a developer wanting contribute an enhancement, bugfix or other patch to this project, please fork this repository and submit a pull request detailing your changes. We review all PRs!

This open source project is released under the [MIT license](http://opensource.org/licenses/MIT) which means if you would like to use this project's code in your own project you are free to do so.  Speaking of, if you have used our code in a cool new project we would like to hear about it!  Please send us an email or post a new thread on [BitPay Labs](https://labs.bitpay.com).

## License

Please refer to the [LICENSE](https://github.com/bitpay/whmcs-plugin/blob/master/LICENSE) file that came with this project.
