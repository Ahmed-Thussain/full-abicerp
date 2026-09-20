<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mcs_switch extends AdminController
{
    public function go($slug = '')
    {
        $staff    = get_staff(get_staff_user_id());
        $siblings = mcs_sibling_rows();

        if (!$staff || !isset($siblings[$slug]) || !in_array($slug, mcs_access_slugs($staff->email), true)) {
            access_denied('multi_company_staff');
        }

        $company                  = $siblings[$slug];
        $company->package_invoice = perfex_saas_tenant()->package_invoice;

        $token = mcs_token_make(APP_ENC_KEY, $staff->email, $slug, time());

        redirect(perfex_saas_tenant_base_url($company, 'multi_company_staff/mcs_enter/index?token=' . urlencode($token)));
    }
}
