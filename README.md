# SEB Banklink Module

#### Compatibility
PrestaShop v1.7.x 

Introduction
--------
SEB banklink offers a comfortable payment option for an online shop client. After goods have been selected in the online shop, the client is re-directed to SEB Internet bank, where they can pay for their purchases by confirming a pre-completed payment order. The seller is notified about the order immediately after it has been paid for. Tested under PrestaShop 1.7 and only on default theme. For configuration you need to generete generate private key pair. The configurations need your private key, seb bank public key and snd id. SEB Bank is shown in invoice, in emails, in the order confirmation and in the order detail page.

Installation
------

#### Composer
```
$ composer require vaado/de_seb
```
For the activation of the bank link in SEB’s live system the merchant has to send its public key to eservice@seb.ee. After that SEB replies by sending their public key and merchant’s VK_SND_ID parameter. Private key must always be kept secret (also from SEB). SEB uses  x.509 standard .PEM format certificates and the private key length should be 2048 bits.
For the service of "Bank Link" separate set of keys is recommended which should be different from the WWW server certificate keys.
We recommend using OPENSSL (http://www.openssl.org) in order to generate the 2048 bit long private key for "Bank Link" service. This key should be used to make a certificate request. Certificate request should be sent via email to your SEB  Account Manager. Received Certificate request will be signed by SEB and certificate file will be created.

The private key can be generated using this command:
```sh
$ openssl genrsa -out privkey.pem 1024
```

And certificate request can be made like this:
```sh
$ openssl req -new -key privkey.pem -out request.pem
```
The outputs of these commands are 2 files: “privkey.pem”contains the private key (do not reveal this file content to anybody) and “request.pem” contains the certificate request (which should be sent to the bank via email)


Features
--------
- Easy and convenient for the client
- Offers a quick and comfortable SEB banklink payment method
- Payments are quickly received in the account of the company
- Quick and comfortable payment method
- The client can immediately pay for their purchases as they are directed to the pre-filled payment order in the Internet Bank
- An instant response message, by which the bank confirms that the client has paid for the order, is sent to you after the Bank Link payment
