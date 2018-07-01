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

class de_SEBReturnModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();

        $service = Tools::getValue('VK_SERVICE');
        $id_order = Tools::getValue('VK_STAMP');
        $verified = $this->verifyPayment();
        $order = new Order($id_order);

        if ($verified && $service == '1111') {
            $paid_amount = Tools::getValue('VK_AMOUNT');
            if ($paid_amount == $order->total_paid) {
                $order->valid = true;
                $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                Tools::redirectLink(__PS_BASE_URI__ .
                    'order-confirmation.php?id_cart='.$order->id_cart.
                    '&id_module='.$this->module->id .
                    '&id_order='.$order->id.'&key='.$order->secure_key);
            } else {
                try {
                    $this->context->smarty->assign(array(
                        'message' => $this->module->getTranslator()->trans(
                            'Payment error. Paid and charged amounts does not match.',
                            array(),
                            'Modules.DESEB.Shop'
                        )
                    ));
                    $this->setTemplate('module:de_seb/views/templates/front/return.tpl');
                } catch (Exception $e) {
                    PrestaShopLogger::addLog($e->getMessage());
                }
            }
        } elseif ($verified && $service == '1911') {
            try {
                $order->setCurrentState(Configuration::get('PS_OS_CANCELED'));
                $this->context->smarty->assign(array(
                    'message' => $this->module->getTranslator()->trans(
                        'Your order has been cancelled',
                        array(),
                        'Modules.DESEB.Shop'
                    )
                ));
                $this->setTemplate('module:de_seb/views/templates/front/return.tpl');
            } catch (Exception $e) {
                PrestaShopLogger::addLog($e->getMessage());
            }
        } else {
            try {
                $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
                $this->context->smarty->assign(array(
                    'message' => $this->module->getTranslator()->trans(
                        'Verification error.',
                        array(),
                        'Modules.DESEB.Shop'
                    )
                ));
                $this->setTemplate('module:de_seb/views/templates/front/return.tpl');
            } catch (Exception $e) {
                PrestaShopLogger::addLog($e->getMessage());
            }
        }
    }


    private function verifyPayment()
    {
        $data = '';
        $return_data = array();

        if (Tools::getIsset('VK_SERVICE')) {
            $return_data['VK_SERVICE'] = Tools::getValue('VK_SERVICE');
            $return_data['VK_VERSION'] = Tools::getValue('VK_VERSION');
            $return_data['VK_SND_ID'] = Tools::getValue('VK_SND_ID');
            $return_data['VK_REC_ID'] = Tools::getValue('VK_REC_ID');
            $return_data['VK_STAMP'] = Tools::getValue('VK_STAMP');
            if (Tools::getValue('VK_SERVICE') == '1111') {
                $return_data['VK_T_NO'] = Tools::getValue('VK_T_NO');
                $return_data['VK_AMOUNT'] = Tools::getValue('VK_AMOUNT');
                $return_data['VK_CURR'] = Tools::getValue('VK_CURR');
                $return_data['VK_REC_ACC'] = Tools::getValue('VK_REC_ACC');
                $return_data['VK_REC_NAME'] = Tools::getValue('VK_REC_NAME');
                $return_data['VK_SND_ACC'] = Tools::getValue('VK_SND_ACC');
                $return_data['VK_SND_NAME'] = Tools::getValue('VK_SND_NAME');
                $return_data['VK_REF'] = Tools::getValue('VK_REF');
                $return_data['VK_MSG'] = Tools::getValue('VK_MSG');
                $return_data['VK_T_DATETIME'] = Tools::getValue('VK_T_DATETIME');
            } elseif (Tools::getValue('VK_SERVICE') == '1911') {
                $return_data['VK_REF'] = Tools::getValue('VK_REF');
                $return_data['VK_MSG'] = Tools::getValue('VK_MSG');
            }
        }

        foreach ($return_data as $row) {
            $data .= str_pad(Tools::strlen($row), 3, '0', STR_PAD_LEFT) . $row;
        }

        $mac = Tools::getValue('VK_MAC');
        $public_key = openssl_get_publickey($this->module->getConfiguration('PUBLIC_KEY'));
        $verified = openssl_verify($data, base64_decode($mac), $public_key);

        return $verified;
    }
}
