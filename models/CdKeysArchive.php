<?php

class CdKeysArchiveOverride extends CdKeysArchive
{
    public $cdkeypwd;

    public static $definition = array(
        'table' => 'cdkey_used',
        'primary' => 'id_cdkey_used',
        'multilang' => false,
        'fields' => array(
            'id_cdkey_used' => array('type' => ObjectModel::TYPE_INT),
            'id_shop' => array('type' => ObjectModel::TYPE_INT),
            'id_order_detail' => array('type' => ObjectModel::TYPE_INT),
            'id_customer' => array('type' => ObjectModel::TYPE_INT),
            'id_order' => array('type' => ObjectModel::TYPE_INT),
            'id_cdkey_group' => array('type' => ObjectModel::TYPE_INT),
            'code' => array('type' => ObjectModel::TYPE_STRING),
            'cdkeypwd' => array('type' => ObjectModel::TYPE_STRING), //ici
            'name' => array('type' => ObjectModel::TYPE_STRING),
            'product' => array('type' => ObjectModel::TYPE_STRING),
            'customer' => array('type' => ObjectModel::TYPE_STRING),
            'customer_mail' => array('type' => ObjectModel::TYPE_STRING),
        ),
    );
}
