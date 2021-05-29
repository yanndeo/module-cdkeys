<?php

require_once _PS_OVERRIDE_DIR_ . 'modules/cdkeys/models/CdKeysArchive.php';

class AdminCdKeysArchiveControllerOverride extends AdminCdKeysArchiveController
{

    private $parent;


    public function __construct()
    {
        parent::__construct();
        $this->className = 'CdKeysArchiveOverride';


        $this->fields_list['cdkeypwd'] =  [
            'title' => $this->l('Cdkeypwd'),
            'width' => 'auto',
            'orderby' => true,
        ];
        // self::dd($this->className);die;
        $this->parent = AdminCdKeysArchiveController::class;
    }

    /**
     * Add cdkeypwd field into form USED CDKEYS
     *
     */
    public function renderForm()
    {
        parent::renderForm();

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('CdKey'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Customer ID'),
                    'name' => 'id_customer',
                    'required' => true,
                    'lang' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Order ID'),
                    'name' => 'id_order',
                    'required' => true,
                    'lang' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Code:'),
                    'name' => 'code',
                    'required' => true,
                    'lang' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Cdkeypwd:'),
                    'name' => 'cdkeypwd',
                    'required' => true,
                    'lang' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('ID of cdkeys group'),
                    'name' => 'id_cdkey_group',
                    'required' => true,
                    'lang' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Group name'),
                    'name' => 'name',
                    'required' => true,
                    'lang' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Product'),
                    'name' => 'product',
                    'required' => true,
                    'lang' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Customer'),
                    'name' => 'customer',
                    'required' => true,
                    'lang' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Customer email'),
                    'name' => 'customer_mail',
                    'required' => true,
                    'lang' => false,
                ),

            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        $parent = get_parent_class($this->parent);

        return $parent::renderForm();
    }


    /**
     * Send email from USED CDKEYS panel
     * add cdkeypwd value
     */
    public function init()
    {

        if (Shop::getContext() == Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->_where = Shop::addSqlRestriction(false, 'a');
        }

        if (Tools::getValue('addcdkey_used', 'false') != 'false') {
            //$this->errors[] = $this->l('It is not possible to add here new entry');
        }
        if (Tools::getValue('id_cdkey_used', 'false') != 'false' && Tools::getValue('deletecdkey_used', 'false') == 'false') {
            //$this->errors[] = $this->l('Changing these details will affect details of code that customer receive');
        }

        if (Tools::getValue('SendEmail', 'false') != 'false') {
            if (is_int((int)Tools::getValue('SendEmail'))) {
                $templateVars = array();
                $code = array();

                $CdKeyArchive = new CdKeysArchiveOverride(Tools::getValue('SendEmail'));
                $customer_temp = Customer::getCustomersByEmail($CdKeyArchive->customer_mail);
                $customer = new Customer($customer_temp[0]['id_customer']);
                $order = new Order($CdKeyArchive->id_order);
                $currency = new Currency($order->id_currency);
                $id_shop = $this->context->shop->id;
                $cdkeyGroup = new CdKeysGroup($CdKeyArchive->id_cdkey_group, $order->id_lang, $id_shop);

                $code['code'] = $CdKeyArchive->code;
                $code['cdkeypwd'] = $CdKeyArchive->cdkeypwd; //ici

                $templateVars['{firstname}'] = $customer->firstname;
                $templateVars['{lastname}'] = $customer->lastname;
                $templateVars['{code}'] = $code['code'];
                $templateVars['{cdkeypwd}'] = $code['cdkeypwd']; //ici

                $templateVars['{product}'] = $CdKeyArchive->product;
                $templateVars['{group_url}'] = $cdkeyGroup->url;
                $templateVars['{group_desc}'] = $cdkeyGroup->group_desc;
                $templateVars['{group_title}'] = $cdkeyGroup->title;
                $templateVars['{product_description}'] = '';
                $templateVars['{product_description_short}'] = '';
                $templateVars['{id_order}'] = $CdKeyArchive->id_order;
                $templateVars['{order_name}'] = $order->getUniqReference();
                $templateVars['{total_paid}'] = Tools::displayPrice($order->total_paid, $currency, false);
                $templateVars['{total_products}'] = Tools::displayPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? $order->total_products : $order->total_products_wt, $currency, false);
                $templateVars['{total_discounts}'] = Tools::displayPrice($order->total_discounts, $currency, false);
                $templateVars['{total_shipping}'] = Tools::displayPrice($order->total_shipping, $currency, false);
                $templateVars['{total_shipping_tax_excl}'] = Tools::displayPrice($order->total_shipping_tax_excl, $currency, false);
                $templateVars['{total_shipping_tax_incl}'] = Tools::displayPrice($order->total_shipping_tax_incl, $currency, false);
                $templateVars['{total_wrapping}'] = Tools::displayPrice($order->total_wrapping, $currency, false);
                $templateVars['{total_tax_paid}'] = Tools::displayPrice(($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl), $currency, false);
                $templateVars['{payment}'] = Tools::substr($order->payment, 0, 255);

                $cdkeys_module = Module::getInstanceByName('cdkeys');
                $templateVars['{group_desc}'] = $cdkeys_module->replaceMailTitle($cdkeyGroup->group_desc, $templateVars);


                if (Mail::Send($order->id_lang, $cdkeyGroup->template, $cdkeys_module->replaceMailTitle($cdkeyGroup->title, $templateVars), $templateVars, strval($customer->email), null, strval(Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop)), strval(Configuration::get('PS_SHOP_NAME', null, null, $id_shop)), null, null, dirname(__file__) . '../../../mails/', false, $id_shop)) {
                    $this->confirmations[] = $this->l('Email sent properly');
                } else {
                    $this->errors[] = $this->l('Errors with email delivery');
                }
            } else {
                $this->errors[] = $this->l('Errors with email delivery');
            }
        }

        $parent = get_parent_class($this->parent);
        $parent::init();
    }


    private static function dd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}
