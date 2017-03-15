# FraudLabs Pro (Extension for Magento2)
This extension helps user to screen the order transaction for online frauds in Magento easily. An example of order transaction is the credit card order. It checks each order transaction that been performed and provides user the fraud validation result on the Magento order details page.

The features of this extension are:

* Fraud analysis and scoring
* IP address geolocation & proxy validation
* Email address validation
* Credit card issuing bank validation
* Transaction velocity validation
* Device transaction validation
* Blacklist validation
* Custom rules trigger
* Email notification of fraud orders
* Mobile app notification of fraud orders

This extension requires a valid API key to function. Please sign up for a free API key at http://www.fraudlabspro.com/sign-up.

# Installation
In order to install the extension in Magento 2.0 the steps are:

1.  Download the FraudLabs Pro plugin from the FraudLabs Pro GitHub site at https://github.com/fraudlabspro/magento2.
2.	Unzip the file and upload it to the Magento installation root directory as: magento2/app/code/
3.	Login to the Magento admin page and disable the cache under the System -> Cache Management.
4.	At the Linux server command line enter the following command in the Magento root directory: php bin/magento setup:upgrade
5.	Then the plugin settings will be available in the admin panel by opening the Stores -> Configuration.

For more detail information please refer http://www.fraudlabspro.com/tutorials/how-to-install-fraudlabs-pro-plugin-on-magento2.
