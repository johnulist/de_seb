<?php
/**
 * 2018 DevExpert OÜ
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * DevExpert OÜ hereby grants you a personal, non-transferable,
 * non-exclusive licence to use the SEB banklink.
 * DevExpert OÜ shall at all times retain ownership of
 * the Software as originally downloaded by you and all
 * subsequent downloads of the Software by you.
 * The Software (and the copyright, and other intellectual
 * property rights of whatever nature in the
 * Software, including any modifications made thereto)
 * are and shall remain the property of DevExpert OÜ.
 * This EULA agreement, and any dispute arising out of or in
 * connection with this EULA agreement, shall
 * be governed by and construed in accordance with the laws of Estonia.
 *
 * @author    DevExpert OÜ
 * @copyright 2018 DevExpert OÜ
 * @license   End User License Agreement (EULA)
 * @link      https://devexpert.ee
 * @package   de_seb
 * @version   1.0.0
 */

class de_SEBValidationModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {
        $this->context->controller->registerJavascript(
            'de_seb_submit',
            'modules/'.$this->module->name.'/views/js/de_seb.js',
            array('position' => 'bottom', 'priority' => 150)
        );
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 ||
            $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 ||
            !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        // Check that this payment option is still available
        // in case the customer changed his address just before
        // the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'ps_wirepayment') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->getTranslator()->trans(
                'This payment method is not available.',
                array(),
                'Modules.DESEB.Shop'
            ));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $this->module->validateOrder(
            $cart->id,
            $this->module->getConfiguration('PAYMENT_STATUS'),
            $total,
            $this->module->displayName,
            null,
            null,
            (int)$currency->id,
            false,
            $customer->secure_key
        );

        $order = new Order($this->module->currentOrder);

        $this->context->smarty->assign(array(
            'banklink_form' => $this->getBanklinkForm($order),
            'banklink_url' => $this->module->banklink_url
        ));

        try {
            $this->setTemplate('module:de_seb/views/templates/front/validate.tpl');
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage());
        }
    }

    /**
     * Generating array for form
     *
     * @param object $order
     * @return array form_data
     */
    public function getBanklinkForm($order)
    {
        $currency = CurrencyCore::getCurrency($order->id_currency);
        $datetime = new DateTime();
        $return_url = $this->context->link->getModuleLink(
            $this->module->name,
            'return',
            array(),
            true
        );

        $form_data = array(
            'VK_SERVICE' => '1012',
            'VK_VERSION' => '008',
            'VK_SND_ID' => $this->module->getConfiguration('SND_ID'),
            'VK_STAMP' => $order->id,
            'VK_AMOUNT' => Tools::ps_round($order->total_paid, 2),
            'VK_CURR' => $currency['iso_code'],
            'VK_REF' => '',
            'VK_MSG' => $this->module->getTranslator()->trans('Order ').$order->id,
            'VK_RETURN' => $return_url,
            'VK_CANCEL' => $return_url,
            'VK_DATETIME' => $datetime->format('Y-m-d\TH:i:sO'),
        );

        $mac = $this->generateMAC($form_data);

        $form_data['VK_MAC'] = $mac;
        $form_data['VK_ENCODING'] = 'UTF-8';
        $form_data['VK_LANG'] = 'EST';

        return $form_data;
    }


    /**
     * Generating MAC string
     *
     * @param array $form_data data for MAC calculating
     * @return string MAC code
     */
    public function generateMAC($form_data)
    {
        $data = '';
        $signature = '';

        foreach ($form_data as $form_row) {
            $data .= str_pad(Tools::strlen($form_row), 3, '0', STR_PAD_LEFT) . $form_row;
        }

        $private_key = $this->module->getConfiguration('PRIVATE_KEY');
        $private_key_pass = $this->module->getConfiguration('PRIVATE_KEY_PASSWORD');
        $private_key = openssl_get_privatekey($private_key, $private_key_pass);
        openssl_sign($data, $signature, $private_key);
        $mac_signature = base64_encode($signature);

        return $mac_signature;
    }
}
