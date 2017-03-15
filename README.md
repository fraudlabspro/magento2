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

## Install Manually

1.  Download the FraudLabs Pro plugin from the FraudLabs Pro GitHub site at https://github.com/fraudlabspro/magento2.
2.  Create a folder and name as Hexasoft.
3.  Unzip the file that downloaded from FraudLabs Pro GitHub site, rename it to FraudLabsPro and transfer it into Hexasoft folder.
4.  Upload the Hexasoft folder to the subdirectory of Magento installation root directory as: magento2/app/code/
5.  Login to the Magento admin page and disable the cache under the System -> Cache Management page. 
6.  At the Linux server command line enter the following command in the Magento root directory: php bin/magento setup:upgrade

## Install via Composer

1.  At the Linux server command line enter the following command in Magento root directory: composer require hexasoft/module-fraudlabspro
2.  Next continue by entering: composer update
3.  Then follow by: php bin/magento setup:upgrade

For more detail information please refer http://www.fraudlabspro.com/tutorials/how-to-install-fraudlabs-pro-plugin-on-magento2.
