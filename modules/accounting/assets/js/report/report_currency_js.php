<script type="text/javascript">
function getReportCurrency() {
  "use strict";
  var el = document.querySelector('[name="report_currency"]');
  return el ? el.value : '';
}
document.addEventListener('DOMContentLoaded', function () {
  var dropdown = document.querySelector('[name="report_currency"]');
  if (dropdown) {
    dropdown.addEventListener('change', function () {
      // Reload through the report's existing filter handling.
      var form = document.getElementById('filter-form');
      if (form) {
        var page = form.querySelector('[name="page"]');
        if (page) {
          page.value = 1;
        }
        if (typeof jQuery !== 'undefined') {
          jQuery(form).submit();
        } else {
          form.submit();
        }
      }
    });
  }
});
</script>
