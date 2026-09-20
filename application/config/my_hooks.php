<?php defined('BASEPATH') or exit('No direct script access allowed');//perfex-saas:start:my_hooks.php
//dont remove/change above line
require_once(FCPATH.'modules/perfex_saas/config/my_hooks.php');
//dont remove/change below line
//perfex-saas:end:my_hooks.php

// Only Arabic and English are offered anywhere in the app
hooks()->add_filter('before_get_languages', function ($languages) {
    return array_values(array_intersect($languages, ['arabic', 'english']));
});

// "Select all" boxes on the permissions table (staff form and roles page)
hooks()->add_action('app_admin_footer', function () {
    $label = _l('select_all');
    $label = $label === 'select_all' ? 'Select all' : $label;
    echo '<script>window.permissionsSelectAllLabel = ' . json_encode($label) . ';'
        . file_get_contents(FCPATH . 'assets/js/permissions-select-all.js') . '</script>';
});