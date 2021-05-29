<?php

require_once _PS_MODULE_DIR_ . 'cdkeys/models/CdKeysGroup.php';
require_once _PS_OVERRIDE_DIR_ . 'modules/cdkeys/models/CdKeysList.php';
require_once _PS_OVERRIDE_DIR_ . 'modules/cdkeys/models/CdKeysArchive.php';


class cdkeysOverride extends cdkeys
{
    public function __construct()
    {
        parent::__construct();

        $this->availableTemplateVars['{cdkeypwd}'] = '';
    }

    /**
     * Change default table of module
     * Adding columns cdkeypwd
     */
    public function installdb()
    {
        $prefix = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $statements = array();

        $statements[] = "CREATE TABLE IF NOT EXISTS `${prefix}cdkey_group` (" . '`id_cdkey_group` int(10) NOT NULL AUTO_INCREMENT,' . '`id_product` int(10) NOT NULL, `id_shop` int(10) NOT NULL,' . '`name` VARCHAR(200),' . 'PRIMARY KEY (`id_cdkey_group`)' . ")";
        $statements[] = "CREATE TABLE IF NOT EXISTS `${prefix}cdkey` (" . '`id_cdkey` int(10) NOT NULL AUTO_INCREMENT,' . '`id_cdkey_group` int(10) NOT NULL, ' . '`code` VARCHAR(200),' . '`cdkeypwd` VARCHAR(255),' . '`active` int(1) NOT NULL DEFAULT 1,' . 'PRIMARY KEY (`id_cdkey`)' . ")";
        $statements[] = "CREATE TABLE IF NOT EXISTS `${prefix}cdkey_used` (" . '`id_cdkey_used` int(10) NOT NULL AUTO_INCREMENT,' . '`id_customer` int(10), `id_shop` int(10) NOT NULL,' . '`id_order` int(10),' . '`code` VARCHAR(200),' . '`cdkeypwd` VARCHAR(255),' . '`name` VARCHAR(200),' . '`product` VARCHAR(200),' . '`customer` VARCHAR(200),' . '`customer_mail` VARCHAR(200),' . 'PRIMARY KEY (`id_cdkey_used`)' . ")";
        $statements[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cdkey_group_lang` (`id_cdkey_group` int(11) NOT NULL,`id_lang` int(11) NOT NULL, `title` VARCHAR(250)) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        $statements[] = " ALTER TABLE `${prefix}cdkey` ADD `cdkeypwd` varchar(255) NOT NULL DEFAULT ";

        $statements[] = " ALTER TABLE `${prefix}cdkey_used` ADD `cdkeypwd` varchar(255) NOT NULL DEFAULT ";

        
        foreach ($statements as $statement) {
            if (!Db::getInstance()->Execute($statement)) {
                return false;
            }
        }

        $this->inconsistency(0);
        return true;
    }


    public function insert_cdkey($array_values)
    {

        $cdkey = new cdKeysListOverride();
        $cdkey->id_cdkey_group;
        $cdkey->code = $array_values['code'];
        $cdkey->cdkeypwd = $array_values['cdkeypwd']; //ici
        $cdkey->active = Configuration::get('cdkey_active');
        $cdkey->id_cdkey_group = Configuration::get('cdkey_group');
        $cdkey->add();
    }



    public function hookactionOrderStatusUpdate($params)
    {

        $delivered = 0;
        $codes = array();
        $allowed = 0;
        $array = array();
        $array = explode(",", Configuration::get('cdkeys_groups'));
        $order = new Order($params['id_order']);


        if ((float)Configuration::get('CDKEYS_MAX_ORDERTOTAL') > 0) {
            $currency_order = new Currency($order->id_currency);
            $currency_default = new Currency(Configuration::get('PS_DEFAULT_CURRENCY'));
            $total_paid_default_currency = Tools::convertPriceFull($order->total_paid, $currency_order, $currency_default);
            if ($total_paid_default_currency > (float)Configuration::get('CDKEYS_MAX_ORDERTOTAL')) {
                return;
            }
        }


        $customer_groups = Customer::getGroupsStatic($order->id_customer);
        foreach ($customer_groups as $group) {
            if (in_array($group, $array)) {
                $allowed = 1;
            }
        }

        if ($allowed != 0) {
            $jest = 0;
            foreach (explode(",", Configuration::get('cdkeys_ostates')) as $k => $v) {
                if ($v == $params['newOrderStatus']->id) {
                    $jest = 1;
                }
            }

            if ($jest == 1) {
                $order = new Order($params['id_order']);
                $customer = new Customer($order->id_customer);
                $cdkeys_product_is_in_order = 0;
                $order_products = $order->getProducts();
                $order_products_with_pack_items = array();
                foreach ($order_products as $number => $product) {
                    $product['id_product'] = $product['product_id'];
                    $order_products_with_pack_items[] = $product;
                    if (Pack::isPack($product['product_id']) && Configuration::get('cdkey_pack')) {
                        foreach (Pack::getItemTable($product['product_id'], $order->id_lang, true) as $pack_product) {
                            $pack_product['product_id'] = $pack_product['id_product'];
                            $pack_product['product_quantity'] = $product['product_quantity'];
                            $pack_product['product_name'] = $pack_product['name'];
                            $pack_product['product_attribute_id'] = $pack_product['id_product_attribute_item'];
                            $pack_product['id_order_detail'] = $product['id_order_detail'];
                            $order_products_with_pack_items[] = $pack_product;
                        }
                    }
                }


                foreach (CdKeysGroup::getAll() as $key => $value) {
                    foreach ($order_products_with_pack_items as $number => $product) {
                        $purchased_product = new Product($product['product_id'], true, $order->id_lang);
                        if ($value['for_attr'] == 1) {
                            if ($value['id_product'] == $product['product_id'] && $value['id_product_attribute'] == $product['product_attribute_id']) {
                                for ($i = 1; $i <= $product['product_quantity']; $i++) {
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['id_order_detail'] = (isset($product['id_order_detail']) ? $product['id_order_detail'] : 0);
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['id_cdkey_group'] = $value['id_cdkey_group'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['syncstock'] = $value['syncstock'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['id_product'] = $product['product_id'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['id_product_attribute'] = $product['product_attribute_id'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['product_name'] = $product['product_name'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['product_description'] = (isset($purchased_product->description) ? $purchased_product->description : '-- ' . $this->l('No product description') . ' --');
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['product_description_short'] = (isset($purchased_product->description_short) ? $purchased_product->description_short : '-- ' . $this->l('No product short description') . ' --');
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['group_name'] = $value['name'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['group_url'] = $value['url'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['invoice_name'] = $value['invoice_name'];
                                }
                                $cdkeys_product_is_in_order = 1;
                            }
                        } else {
                            if ($value['id_product'] == $product['product_id']) {
                                for ($i = 1; $i <= $product['product_quantity']; $i++) {
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['id_order_detail'] = (isset($product['id_order_detail']) ? $product['id_order_detail'] : 0);
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['id_cdkey_group'] = $value['id_cdkey_group'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['syncstock'] = $value['syncstock'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['id_product'] = $product['product_id'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['id_product_attribute'] = $product['product_attribute_id'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['product_name'] = $product['product_name'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['product_description'] = (isset($purchased_product->description) ? $purchased_product->description : '-- ' . $this->l('No product description') . ' --');
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['product_description_short'] = (isset($purchased_product->description_short) ? $purchased_product->description_short : '-- ' . $this->l('No product short description') . ' --');
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['group_name'] = $value['name'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['group_url'] = $value['url'];
                                    $list_of_codes[$product['id_product'] . "_" . $i . '_' . $product['product_attribute_id']]['invoice_name'] = $value['invoice_name'];
                                }
                                $cdkeys_product_is_in_order = 1;
                            }
                        }
                    }
                }

                $cdkey_already_sent = CdKeysArchiveOverride::verifyIfExists($order->id_customer, $params['id_order']);
                $multikey = array();


                if ($cdkey_already_sent == false && $jest == 1 && $cdkeys_product_is_in_order == 1) {
                    $keysForInvoice = array();
                    $NamesForInvoice = array();

                    foreach ($list_of_codes as $k => $v) {

                        $id_lang = Context::getContext()->language->id;
                        $id_shop = Context::getContext()->shop->id;
                        $CdKeysGroup = new CdKeysGroup($v['id_cdkey_group'], $order->id_lang, $this->context->shop->id);
                        $templateVars['{firstname}'] = $customer->firstname;
                        $templateVars['{lastname}'] = $customer->lastname;
                        $templateVars['{product}'] = $v['product_name'];
                        $templateVars['{product_description}'] = $v['product_description'];
                        $templateVars['{product_description_short}'] = $v['product_description_short'];
                        $templateVars['{group_url}'] = $v['group_url'];
                        $templateVars['{group_desc}'] = $v['group_desc'];
                        $templateVars['{group_title}'] = $v['title'];


                        if ($CdKeysGroup->hm > 0) {

                            $keysForSqlQuery = array();
                            for ($i = 0; $i < $CdKeysGroup->hm; $i++) {
                                $code = CdKeysList::getOne($v['id_cdkey_group']);

                                if ($code) {

                                    $keysForSqlQuery[] = $code['code'];
                                    $CdKeysArchive = new CdKeysArchiveOverride();
                                    $CdKeysArchive->id_order = $params['id_order'];
                                    $CdKeysArchive->id_order_detail = $v['id_order_detail'];
                                    $CdKeysArchive->code = $code['code'];
                                    $CdKeysArchive->cdkeypwd = $code['cdkeypwd'];
                                    $CdKeysArchive->name = $v['group_name'];
                                    $CdKeysArchive->id_cdkey_group = $v['id_cdkey_group'];
                                    $CdKeysArchive->product = $v['product_name'];
                                    $CdKeysArchive->customer = $customer->firstname . " " . $customer->lastname;
                                    $CdKeysArchive->customer_mail = $customer->email;
                                    $CdKeysArchive->id_customer = $customer->id;
                                    $CdKeysArchive->id_shop = $this->context->shop->id;

                                    if ($CdKeysArchive->add()) {
                                        $CdKeysList = new CdKeysList($code['id_cdkey']);
                                        $CdKeysList->delete();
                                    }

                                    if ($v['syncstock'] == 1) {
                                        StockAvailable::setQuantity((int)$v['id_product'], (int)$v['id_product_attribute'], $this->getAvKeys(null, $CdKeysArchive->id_cdkey_group), (int)$CdKeysArchive->id_shop);
                                    }

                                    $keysForInvoice[$v['id_order_detail']][] = $code['code'];
                                } else {

                                    $keysForSqlQuery[] = $this->l('No CdKey Available, please contact with us');
                                    $keysForInvoice[$v['id_order_detail']][] = $this->l('No CdKey Available, please contact with us');
                                }
                            }
                            $templateVars['{code}'] = implode("<br/>", $keysForSqlQuery);
                            $templateVars['{cdkeypwd}'] = $code['cdkeypwd']; //ici
                        }

                        if (Configuration::get('CDKEY_HOWDELIVER') == 1) {

                            $multikey[$k]['firstname'] = $templateVars['{firstname}'];
                            $multikey[$k]['lastname'] = $templateVars['{lastname}'];
                            $multikey[$k]['code'] = $templateVars['{code}'];
                            $multikey[$k]['cdkeypwd'] = $templateVars['{cdkeypwd}']; //ici

                            $multikey[$k]['product'] = $templateVars['{product}'];
                            $multikey[$k]['product_description'] = $templateVars['{product_description}'];
                            $multikey[$k]['product_description_short'] = $templateVars['{product_description_short}'];
                            $multikey[$k]['group_url'] = $templateVars['{group_url}'];
                            $multikey[$k]['cdkeys_group_title'] = $CdKeysGroup->title;
                            $multikey[$k]['cdkeys_group_desc'] = $CdKeysGroup->group_desc;
                        }

                        //GLOBAL EMAIL VARIABLES
                        $templateVars['{id_order}'] = $params['id_order'];

                        // NEW
                        $templateVars['{email}'] = $customer->email;

                        if (isset($params['id_order'])) {
                            $order = new Order($params['id_order']);
                            $currency = new Currency($order->id_currency);
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
                        }



                        // REPLACE GROUP DESC
                        if (Configuration::get('CDKEY_HOWDELIVER') != 1) {
                            Mail::Send($order->id_lang, $CdKeysGroup->template, $this->replaceMailTitle($CdKeysGroup->title, $templateVars), $templateVars, strval($customer->email), null, strval(Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop)), strval(Configuration::get('PS_SHOP_NAME', null, null, $id_shop)), null, null, dirname(__file__) . '/mails/', false, $id_shop);
                        }

                        /**
                         * $url_address = "localhost/17210/";
                         * $to = "48698400799";
                         * $text = "Cdkey code you bought: ".$CdKeysArchive->code;
                         * $unicode=0;
                         * $type = "customer";
                         *
                         * $status = "";
                         * $query = "to=".urlencode($to)."&text=".urlencode($text)."&unicode=".$unicode."&type=".$type."&transaction=".$transaction;
                         *
                         * function URLopen($url)
                         * {
                         * $dh = fopen("$url",'r');
                         * $result = fread($dh, 8192);
                         * return $result;
                         * }
                         * $data = @URLopen("http://".$url_address."/modules/prestasms/api.php?".$query);
                         **/

                        $NamesForInvoice[$v['id_order_detail']] = $v['invoice_name'] . ': ';
                    }
                    $this->updateOrderDetail($NamesForInvoice, $keysForInvoice);

                    $email_title = Configuration::get('CDKEYS_MULTITITLE', $order->id_lang);
                    if (Configuration::get('CDKEY_HOWDELIVER') == 1) {
                        $this->context->smarty->assign('multikey', $multikey);
                        $html_to_send = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'cdkeys/views/templates/email/multikey-instance.tpl');
                        $templateVars['{firstname}'] = $customer->firstname;
                        $templateVars['{lastname}'] = $customer->lastname;
                        $templateVars['{html_to_send}'] = $html_to_send;
                        $templateVars['{group_desc}'] = $this->replaceMailTitle($v['group_desc'], $templateVars);

                        Mail::Send($order->id_lang, 'multikeymail', $this->replaceMailTitle($email_title, $templateVars), $templateVars, strval($customer->email), null, strval(Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop)), strval(Configuration::get('PS_SHOP_NAME', null, null, $id_shop)), null, null, dirname(__file__) . '/mails/', false, $id_shop);
                    }
                }
            }
        }
    }


    private function dd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}



