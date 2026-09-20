<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include_once APPPATH . 'views/admin/includes/helpers_bottom.php'; ?>

<?php hooks()->do_action('before_js_scripts_render'); ?>

<?= app_compile_scripts();

/**
 * Global function for custom field of type hyperlink
 */
echo get_custom_fields_hyperlink_js_function(); ?>
<?php
/**
 * Check for any alerts stored in session
 */
app_js_alerts();
?>
<?php
/**
 * Check pusher real time notifications
 */
if (get_option('pusher_realtime_notifications') == 1) { ?>
<script type="text/javascript">
    $(function() {
        // Enable pusher logging - don't include this in production
        // Pusher.logToConsole = true;
        <?php $pusher_options = hooks()->apply_filters('pusher_options', [['disableStats' => true]]);
    if (! isset($pusher_options['cluster']) && get_option('pusher_cluster') != '') {
        $pusher_options['cluster'] = get_option('pusher_cluster');
    }
    ?>
        var
            pusher_options = <?= json_encode($pusher_options); ?> ;
        var pusher = new Pusher(
            "<?= get_option('pusher_app_key'); ?>",
            pusher_options);
        var channel = pusher.subscribe(
            'notifications-channel-<?= get_staff_user_id(); ?>'
        );
        channel.bind('notification', function(data) {
            fetch_notifications();
        });
    });
</script>
<script>
$(function() {
    // Override xdsoft datetimepicker Arabic locale with Standard month names
    function setXdsoftLocale() {
        // Ensure the plugin is loaded
        if (typeof $.xdsoft === 'undefined' && typeof window.xdsoft === 'undefined') {
            return;
        }
        var dp = (typeof $.xdsoft !== 'undefined') ? $.xdsoft : window.xdsoft;
        if (!dp || !dp.i18n) return;

        // Define (or override) the Arabic locale with Standard month names
        dp.i18n.ar = {
            months: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
            monthsShort: ['ينا', 'فبر', 'مار', 'أبر', 'ماي', 'يون', 'يول', 'أغس', 'سبت', 'أكت', 'نوف', 'ديس'],
            dayOfWeek: ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
            dayOfWeekShort: ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'],
            dayOfWeekMin: ['أ', 'إ', 'ث', 'أ', 'خ', 'ج', 'س']
        };

        // Set this locale as the default
        if (typeof dp.setLocale === 'function') {
            dp.setLocale('ar');
        }

        // Also set default options for future pickers
        if (dp.defaults) {
            dp.defaults.lang = 'ar';
        }

        // For existing pickers, reinitialize with the new locale
        $('.datetimepicker, .datepicker, [data-toggle="datetimepicker"]').each(function() {
            var $this = $(this);
            var picker = $this.data('xdsoft_datetimepicker');
            if (picker) {
                // Try to update locale on the existing instance
                if (typeof picker.setLocale === 'function') {
                    picker.setLocale('ar');
                } else {
                    // Fallback: destroy and recreate
                    var val = $this.val();
                    var options = $this.data('xdsoft_datetimepicker_options') || {};
                    options.lang = 'ar';
                    $this.datetimepicker('destroy');
                    $this.datetimepicker(options);
                    if (val) $this.val(val);
                }
            }
        });
    }

    // Run after a short delay to ensure all pickers are initialized
    setTimeout(setXdsoftLocale, 300);
});
</script>
<?php } ?>
<script>
/* Fix: metisMenu not initialized because moment.tz crash halts main.min.js */
(function() {
    // 1. Polyfill moment.tz if missing (prevents future crashes)
    function patchMoment() {
        if (typeof window.moment === 'function' && !window.moment.tz) {
            window.moment.tz = function() { return window.moment.apply(this, arguments); };
            window.moment.tz.guess = function() { return 'UTC'; };
            window.moment.tz.setDefault = function() {};
            window.moment.tz.zone = function() { return null; };
            window.moment.tz.names = function() { return []; };
        }
    }
    patchMoment();

    // 2. Initialize metisMenu manually (in case main.min.js ready handler crashed)
    function fixSidebar() {
        if (typeof jQuery === 'undefined') return;
        if (!jQuery.fn.metisMenu) return;

        var $sideMenu = jQuery('#side-menu');
        if ($sideMenu.length && !$sideMenu.data('metisMenu')) {
            try { $sideMenu.metisMenu(); } catch (e) {}
        }

        var $setupMenu = jQuery('#setup-menu-wrapper');
        if ($setupMenu.length && !$setupMenu.data('metisMenu')) {
            try { $setupMenu.metisMenu(); } catch (e) {}
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(fixSidebar, 200);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(fixSidebar, 200);
        });
    }
    // Retry to catch late-loading libraries
    setTimeout(fixSidebar, 1000);
    setTimeout(fixSidebar, 2500);
})();
</script>
<?php app_admin_footer(); ?>