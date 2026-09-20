<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mcs_enter extends App_Controller
{
    public function index()
    {
        $claims = mcs_token_read(APP_ENC_KEY, (string) $this->input->get('token'), mcs_current_slug(), time());
        if (!$claims || !mcs_use_nonce($claims['nonce'])) {
            return $this->fail();
        }

        if (!in_array(mcs_current_slug(), mcs_access_slugs($claims['email']), true)) {
            return $this->fail();
        }

        $staff = $this->db->get_where(db_prefix() . 'staff', ['email' => $claims['email'], 'active' => 1])->row();
        if (!$staff) {
            return $this->fail();
        }

        hooks()->do_action('before_staff_login', ['email' => $staff->email, 'userid' => $staff->staffid]);

        $this->session->set_userdata([
            'staff_user_id'   => $staff->staffid,
            'staff_logged_in' => true,
        ]);

        redirect(admin_url());
    }

    private function fail()
    {
        set_alert('danger', 'The company switch link is invalid or has expired. Please try again.');
        redirect(admin_url('authentication'));
    }
}
