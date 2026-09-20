<?php defined('BASEPATH') or exit('No direct script access allowed');//perfex-saas:start:my_hooks.php
//dont remove/change above line
require_once(FCPATH.'modules/perfex_saas/config/my_hooks.php');
//dont remove/change below line
//perfex-saas:end:my_hooks.php

// Only Arabic and English are offered anywhere in the app
hooks()->add_filter('before_get_languages', function ($languages) {
    return array_values(array_intersect($languages, ['arabic', 'english']));
});