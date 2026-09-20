<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s tw-mb-4" id="mcs-access-box">
    <div class="panel-body">
        <h4 class="tw-mt-0"><?= html_escape('Company access'); ?></h4>
        <p class="text-muted">
            Tick the companies this person may work in. Administrators become administrators in every ticked company.
            Other staff get the same-named role in each ticked company.
        </p>
        <input type="hidden" name="mcs_present" value="1">
        <?php foreach ($mcs_names as $slug => $name) { ?>
            <?php $is_current = $slug === $mcs_current; ?>
            <div class="checkbox checkbox-primary">
                <input type="checkbox" id="mcs_company_<?= html_escape($slug); ?>" name="mcs_companies[]"
                       value="<?= html_escape($slug); ?>"
                       <?= ($is_current || in_array($slug, $mcs_ticked, true)) ? 'checked' : ''; ?>
                       <?= $is_current ? 'onclick="return false;"' : ''; ?>>
                <label for="mcs_company_<?= html_escape($slug); ?>">
                    <?= html_escape($name); ?><?= $is_current ? ' (this company)' : ''; ?>
                </label>
            </div>
        <?php } ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var box = document.getElementById('mcs-access-box');
    var role = document.querySelector('#staff_permissions select[name="role"]');
    if (!box || !role) {
        return;
    }
    var group = role.closest('.form-group');
    if (group && group.parentNode) {
        group.parentNode.insertBefore(box, group.nextSibling);
    }
});
</script>
