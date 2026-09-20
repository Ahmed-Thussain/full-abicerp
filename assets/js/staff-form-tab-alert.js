// When the staff form fails validation on a field that lives on another tab,
// switch to that tab and show a banner, so Save never looks like it did nothing.
(function ($) {
  $(function () {
    var $form = $('form.staff-form');
    if (!$form.length) {
      return;
    }

    $form.on('invalid-form.validate', function (event, validator) {
      var invalid = validator.errorList && validator.errorList.length ? validator.errorList[0].element : null;
      if (!invalid) {
        return;
      }

      var tabName = '';
      var $panes = $(invalid).parents('.tab-pane').get().reverse();
      $.each($panes, function () {
        var $link = $('a[href="#' + this.id + '"]').first();
        if ($link.length) {
          $link.tab('show');
          tabName = $.trim($link.text());
        }
      });

      var message = $('body').hasClass('rtl')
        ? 'يوجد حقل غير صحيح أو مطلوب في تبويب «' + tabName + '». يرجى تصحيحه قبل الحفظ.'
        : 'A field is missing or invalid in the "' + tabName + '" tab. Please fix it before saving.';

      $form.find('.tab-alert-banner').remove();
      var $banner = $('<div class="alert alert-danger tab-alert-banner"></div>').text(message);
      $form.prepend($banner);
      setTimeout(function () {
        $banner.fadeOut(300, function () {
          $(this).remove();
        });
      }, 10000);

      if (typeof alert_float === 'function') {
        alert_float('danger', message.replace(/"/g, "'"));
      }
      setTimeout(function () {
        $(invalid).trigger('focus');
      }, 200);
    });
  });
})(jQuery);
