<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.6.1 — Unregister the unused actionObjectUpdateAfter / actionObjectAddAfter hooks
 * and register displayBackOfficeFooter (previously auto-registered from hookBackOfficeHeader).
 *
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_6_1($object)
{
    if (!$object->isRegisteredInHook('displayBackOfficeFooter')) {
        $object->registerHook('displayBackOfficeFooter');
    }

    foreach (['actionObjectUpdateAfter', 'actionObjectAddAfter'] as $hook) {
        if ($object->isRegisteredInHook($hook)) {
            $object->unregisterHook($hook);
        }
    }

    return true;
}
