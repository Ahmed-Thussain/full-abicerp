<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mcs_switch extends AdminController
{
    public function go($slug = '')
    {
        $url = mcs_switch_target_url($slug);
        if ($url === null) {
            set_alert('danger', 'You do not have an account in that company, so you cannot switch to it.');
            redirect(admin_url());
        }

        redirect($url);
    }
}
