<?php

class CdKeysListOverride extends CdKeysList
{

    public $cdkeypwd;


    public function __construct($id_cdkey = null)
    {
        parent::__construct($id_cdkey);
    }

    public static $definition = array(
        'table' => 'cdkey',
        'primary' => 'id_cdkey',
        'multilang' => false,
        'fields' => array(
            'id_cdkey' => array('type' => ObjectModel::TYPE_INT),
            'id_cdkey_group' => array('type' => ObjectModel::TYPE_INT),
            'code' => array('type' => ObjectModel::TYPE_STRING),
            'cdkeypwd' => array('type' => ObjectModel::TYPE_STRING),
            'active' => array('type' => ObjectModel::TYPE_INT),
        ),
    );
}
