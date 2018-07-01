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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class de_SEB extends PaymentModule
{

    protected $html = '';

    public $orderState;

    public function __construct()
    {
        $this->name = 'de_seb';
        parent::__construct();
        $this->displayName = $this->l('SEB');
        $this->description = $this->l('Accept payments by SEB banklink');
        $this->orderState = $this->l('Awaiting SEB payment');
        $this->tab = 'payments_gateways';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->version = '1.0.0';
        $this->author = 'DevExpert';
        $this->banklink_url = 'https://www.seb.ee/cgi-bin/dv.sh/ipank.r';
        $this->bootstrap = true;
    }

    /**
     * install
     *
     * @return boolean
     * @throws Exception
     */
    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('paymentReturn') ||
            !$this->registerHook('paymentOptions') ||
            !$this->addOrderState($this->orderState)) {
            return false;
        }

        return true;
    }

    /**
     * uninstall
     *
     * @return boolean true, if successful
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * add order state
     *
     * @param string $name Order state name
     * @return boolean true
     * @throws Exception
     */
    public function addOrderState($name)
    {
        $state_exist = false;
        $states = OrderState::getOrderStates((int)$this->context->language->id);

        foreach ($states as $state) {
            if (in_array($name, $state)) {
                $state_exist = true;
                break;
            }
        }

        if (!$state_exist) {
            $order_state = new OrderState();
            $order_state->color = '#60cd19';
            $order_state->send_email = false;
            $order_state->name = array();
            $languages = Language::getLanguages(false);

            foreach ($languages as $language) {
                $order_state->name[$language['id_lang']] = $name;
            }

            $order_state->add();
            $id_order_state = $order_state->id;
            $this->updateConfiguration('PAYMENT_STATUS', $id_order_state);
            $logo_path = $this->local_path.'/views/img/status_logo.gif';
            $new_logo_path = _PS_IMG_DIR_.'os/' . $id_order_state . '.gif';
            copy($logo_path, $new_logo_path);
        }

        return true;
    }

    /**
     * Module configuration page
     *
     * @return string html
     */
    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->postProcess();
        }

        $this->html .= $this->displayDevExpert();
        $this->html .= $this->renderForm();

        return $this->html;
    }

    protected function displayDevExpert()
    {
        return $this->display(__FILE__, 'settings_header.tpl');
    }

    /**
     * Form generation
     *
     * @return string form html
     */
    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Account details', array(), 'Modules.Wirepayment.Admin'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('SND ID'),
                        'name' => 'SND_ID',
                        'required' => true,
                        'lang' => false
                    ),
                    array(
                        'type' => 'textarea',
                        'rows' => 10,
                        'cols' => 100,
                        'label' => $this->l('Public key'),
                        'name' => 'PUBLIC_KEY',
                        'required' => true,
                        'lang' => false
                    ),
                    array(
                        'type' => 'textarea',
                        'rows' => 10,
                        'cols' => 100,
                        'label' => $this->l('Private key'),
                        'name' => 'PRIVATE_KEY',
                        'required' => true,
                        'lang' => false
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Private key password'),
                        'name' => 'PRIVATE_KEY_PASSWORD',
                        'required' => false,
                        'lang' => false
                    ),

                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='
            .$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * get configuration fieles values
     *
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            'SND_ID' => $this->getConfiguration('SND_ID'),
            'PUBLIC_KEY' => $this->getConfiguration('PUBLIC_KEY'),
            'PRIVATE_KEY' => $this->getConfiguration('PRIVATE_KEY'),
            'PRIVATE_KEY_PASSWORD' => $this->getConfiguration('PRIVATE_KEY_PASSWORD'),
        );
    }

    protected function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->updateConfiguration('SND_ID', Tools::getValue('SND_ID'));
            $this->updateConfiguration('PUBLIC_KEY', Tools::getValue('PUBLIC_KEY'));
            $this->updateConfiguration('PRIVATE_KEY', Tools::getValue('PRIVATE_KEY'));
            $this->updateConfiguration('PRIVATE_KEY_PASSWORD', Tools::getValue('PRIVATE_KEY_PASSWORD'));
        }

        $this->html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Global'));
    }

    /**
     * Payment option hook
     *
     * @param array $params
     * @return array html
     */
    public function hookPaymentOptions($params)
    {
        $this->smarty->assign(array(
            'href' => 'test',
            'this_path' => $this->_path,
            'moduleName' => $this->name,
            'displayName' => $this->displayName
        ));

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Pay by SEB banklink', array(), 'Modules.SEB.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true));
        $payment_options = array($newOption);

        return $payment_options;
    }

    /**
     * Get configuration value
     *
     * @param string $name key
     * @return string
     */
    public function getConfiguration($name)
    {
        return Configuration::get($this->name . $name);
    }

    /**
     * Get configuration value
     *
     * @param string $name
     * @param string $value
     * @return string
     */
    public function updateConfiguration($name, $value)
    {
        return Configuration::updateValue($this->name . $name, $value);
    }
}
