<?php

require_once _PS_OVERRIDE_DIR_ . 'modules/cdkeys/models/CdKeysList.php';


class AdminCdKeysListControllerOverride extends AdminCdKeysListController
{
    public $parent;

    public function __construct()
    {
        parent::__construct();
        $this->className = 'CdKeysListOverride';

        $this->fields_list['cdkeypwd'] = [
            'title' => $this->l('Cdkeypwd'),
            'width' => 'auto',
            'orderby' => true,
        ];
       // self::dd($this->className);die;

        $this->parent = AdminCdKeysListController::class;
    }



    /**
     * FORM adding CDKEY / CDKEYPWD
     * SHOW FORM
     */
    public function renderForm()
    {

       $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('CdKey'),
            ),
            'input' => array(
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Code:'),
                    'name' => 'code',
                    'required' => true,
                    'lang' => false,
                    'desc' => $this->l('Add one code or list of codes (each code in new line like example below)') . '<br/>',
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Cdkeypwd:'),
                    'name' => 'cdkeypwd',
                    'required' => true,
                    'lang' => false,
                    'desc' => $this->l('Add one password or list of passwords (each password in new line like example below)') . '<br/>',
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Group'),
                    'name' => 'id_cdkey_group',
                    'required' => true,
                    'lang' => false,
                    'options' => array(
                        'query' => Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'cdkey_group` WHERE 1 ' . (Shop::getContext() == Shop::CONTEXT_SHOP && Shop::isFeatureActive() ? Shop::addSqlRestriction(false) : '') . ' ORDER BY name ASC'),
                        'id' => 'id_cdkey_group',
                        'name' => 'name'
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Active:'),
                    'name' => 'active',
                    'required' => true,
                    'lang' => false,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('On')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Off')
                        )
                    ),
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
     * ONSUBMIT LOOP EVERY COUPLE CODE/PASSWORD
     * AND SAVE IT TO DATABASE
     */
    public function processAdd()
    {
        $data = $_POST;
       // self::dd($data);die;

        //string => array from input fields
        $array_codekeys = (explode("\n", $data['code']));
        $array_password = (explode("\n", $data['cdkeypwd']));

        if (count($array_codekeys) !== count($array_password)) {
            //Length of list of codes must be correspond with length of password
            throw new \logicException("input fields must be have same length");
        } else {
            for ($i = 0; $i < count($array_codekeys); $i++) {
                $ck = new CdKeysListOverride();
                $ck->code = trim($array_codekeys[$i]);
                $ck->cdkeypwd = trim($array_password[$i]);
                $ck->active = Tools::getValue('active');
                $ck->id_cdkey_group = Tools::getValue('id_cdkey_group');
                $ck->add();
            }
        }

        return true;
    }



    private static function dd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}
