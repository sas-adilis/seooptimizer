<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_1($object)
{
    if (!Configuration::get('SEOO_SECURE_KEY')) {
        Configuration::updateValue('SEOO_SECURE_KEY', bin2hex(random_bytes(16)));
    }

    return true;
}
