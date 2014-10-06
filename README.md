## SEQR Magento Plugin			

### SEQR ###
SEQR is Sweden’s and Europe’s most used mobile wallet in stores and online. SEQR enables anybody with a smartphone to pay in stores online and in-app. Users can also transfer money at no charge, store receipts digitally and receive offers and promotions directly through one mobile app.

SEQR offer the merchant 50% in reduction to payment card interchange and no capital investment requirements. SEQR as method of payment is also completely independent of PCI and traditional card networks.

SEQR is based on Seamless’ technology, a mobile phone payment and transaction service using QR codes & NFC on the front-end and Seamless’ proven transaction server on the back-end. SEQR is the only fully-integrated mobile phone payment solution handling the entire transaction chain, from customer through to settlement. Through our state of the art technology, we have created the easiest, secure, and most cost effective payment system.

Learn more about SEQR on www.seqr.com

### Dowload ###
* Version 1.0: [Seamless_SEQR-1.0.0.tgz](https://github.com/SeamlessDistribution/seqr-magento-plugin/raw/master/builds/Seamless_SEQR-1.0.0.tgz)

### Plugin ###
Plugin provide possibility for shop clients to select SEQR as payment method, and after order placement pay it via scanning QR code (or directly from your mobile device).  

* SEQR as payment method on checkout page. 
 
![alt tag](https://raw.githubusercontent.com/SeamlessDistribution/seqr-magento-plugin/master/doc/Magento-Checkout.png)

* Payment via scanning of QR code.

![alt tag](https://raw.githubusercontent.com/SeamlessDistribution/seqr-magento-plugin/master/doc/Magento-Payment-QR.png)

* Payment from 

![alt tag](https://raw.githubusercontent.com/SeamlessDistribution/seqr-magento-plugin/master/doc/Magento-Payment-Mobile.png)
 
### Installation & Configuration ###
![alt tag](https://raw.githubusercontent.com/SeamlessDistribution/seqr-magento-plugin/master/doc/Magento-SEQR-Settings.png)

Plugin can be installed via installation in Magento Connect Manager or by copping all plugin files to the magento directory. Magento Connect available on Magento administration page System > Magento Connect > Magento Connect Manager

Plugin configuration properties available on Magento administration page System > Configuration > Payment Methods (System > Configuration > Payment Options).

Contact Seamless on integrations@seamless.se to get the right settings for the SOAP url, Terminal ID and Terminal Password. 

New order, paid order, cancelled order statuses, used to marking orders in Magento.

Title is shown as option of payment method in checkout process. 

All properties are required and should be configured before enabling this payment method in production.

### Development & File structure ###

Plugin based on javascript plugin for SEQR integration. Please check it for understanding how work web component http://github.com/SeamlessDistribution/seqr-webshop-plugin. For more information about SEQR API please check http://developer.seqr.com/merchant/webshop/

##### Plugin categories: #####
* /app/code/community/Seamless/SEQR/
/app/design/frontend/base/default/layout/seqr.xml
* /app/design/frontend/base/default/template/seqr/
* /app/etc/modules/Seamless_SEQR.xml
* /js/seqr/
* /skin/frontend/base/default/css/seqr/seqr.css

#### Main php classes ####
1. Seamless_SEQR_PaymentController 
(/app/code/community/Seamless/SEQR/controllers/PaymentController.php)
Provide actions and pages for SEQR payment proceed.
2. Seamless_SEQR_Model_Invoice
(/app/code/community/Seamless/SEQR/Model/Invoice.php)
Main domain object providing logic of plugin. Make calls to API, caching responses and run additional logic. 
3. Seamless_SEQR_Model_Api 
(/app/code/community/Seamless/SEQR/Model/Api.php)
Communication API for work with SEQR SOA Service. Contains requests structure and remote API calls. 
