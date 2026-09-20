// Adds "Select all" boxes to the permissions table (staff form and roles page).
// When a feature has both View (Global) and View (Own), select-all picks View (Own).
(function ($) {
  $(function () {
    var $table = $('table.roles');
    if (!$table.length) {
      return;
    }

    var label = window.permissionsSelectAllLabel || 'Select all';
    var busy = false;

    function setRow($row, checked) {
      busy = true;

      var $global = $row.find('[data-can-view]');
      var hasOwn = $row.find('[data-can-view-own]').length > 0;

      // Switch a ticked View (Global) over to View (Own) so the "own" box is free to tick
      if (checked && hasOwn && $global.length && $global.prop('checked') && !$global.prop('disabled')) {
        $global.prop('checked', false).trigger('change');
      }

      $row
        .find('input.capability')
        .not('[disabled]')
        .not('[data-not-applicable="true"]')
        .each(function () {
          var $input = $(this);
          if (checked && hasOwn && $input.is('[data-can-view]')) {
            return;
          }
          if ($input.prop('checked') !== checked) {
            $input.prop('checked', checked).trigger('change');
          }
        });

      busy = false;
    }

    $table.find('tbody tr[data-name]').each(function () {
      var $row = $(this);
      if (!$row.find('input.capability').length) {
        return;
      }
      var id = 'psa_' + $row.data('name');
      $row.children('td').first().append(
        '<div class="checkbox tw-mt-2"><input type="checkbox" id="' + id + '" class="psa-row">' +
        '<label for="' + id + '">' + label + '</label></div>'
      );
    });

    $table.find('thead th').first().append(
      '<div class="checkbox tw-mt-2"><input type="checkbox" id="psa_all" class="psa-all">' +
      '<label for="psa_all">' + label + '</label></div>'
    );

    $table.on('change', 'input.psa-row', function () {
      setRow($(this).closest('tr'), this.checked);
    });

    $table.on('change', 'input.psa-all', function () {
      var checked = this.checked;
      $table.find('tbody tr[data-name]').each(function () {
        setRow($(this), checked);
      });
      $table.find('input.psa-row').prop('checked', checked);
    });

    // Any manual change (or a role change) makes the "select all" boxes stale
    $table.on('change', 'input.capability', function () {
      if (busy) {
        return;
      }
      $(this).closest('tr').find('input.psa-row').prop('checked', false);
      $table.find('input.psa-all').prop('checked', false);
    });
  });
})(jQuery);
