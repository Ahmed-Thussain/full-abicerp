(function(){
  "use strict";
  
  var fnServerParams = {
  }

  initDataTable('.table-shift_type', admin_url + 'timesheets/table_shift_type', false, false, fnServerParams, [0, 'desc']);

    appValidateForm($('#shift_type'), {
           'shift_type_name': 'required',
           'color': 'required',
           'time_start': 'required',
           'time_end': 'required',
           'time_start_work': {required: function(){ return !$('#is_day_off').is(':checked'); }},
           'time_end_work': {required: function(){ return !$('#is_day_off').is(':checked'); }},
          
  });

  $('#is_day_off').on('change', function(){
    toggle_day_off_fields();
  });
})(jQuery);

function toggle_day_off_fields(){
  "use strict";

  var is_off = $('#is_day_off').is(':checked');
  $('#shift_time_fields').toggleClass('hide', is_off);
  $('#day_off_symbol_wrapper').toggleClass('hide', !is_off);
}

function new_shift_type(){
  "use strict";

		$('input[name="id"]').val('');
		$('input[name="shift_type_name"]').val('');
    $('.colorpicker-input').colorpicker('setValue', '');
		$('input[name="time_start"]').val('');
		$('input[name="time_end"]').val('');
		$('input[name="time_start_work"]').val('');
		$('input[name="time_end_work"]').val('');
		$('input[name="start_lunch_break_time"]').val('');
		$('input[name="end_lunch_break_time"]').val('');
		$('textarea[name="description"]').val('');
    $('#is_day_off').prop('checked', false);
    $('input[name="day_off_symbol"]').val('OFF');
    toggle_day_off_fields();
    $('.edit-title').addClass('hide');
    $('.add-title').removeClass('hide');
    $('#shift').modal('show');
}

function edit_shift_type(invoker){
  "use strict";
  
    $('input[name="id"]').val($(invoker).data('id'));
		$('input[name="shift_type_name"]').val($(invoker).data('shift_type_name'));
    $('.colorpicker-input').colorpicker('setValue', $(invoker).data('color'));
		$('input[name="time_start"]').val($(invoker).data('time_start'));
		$('input[name="time_end"]').val($(invoker).data('time_end'));
		$('input[name="time_start_work"]').val($(invoker).data('time_start_work'));
		$('input[name="time_end_work"]').val($(invoker).data('time_end_work'));
		$('input[name="start_lunch_break_time"]').val($(invoker).data('start_lunch_break_time'));
		$('input[name="end_lunch_break_time"]').val($(invoker).data('end_lunch_break_time'));
		$('textarea[name="description"]').val($(invoker).data('description'));
    $('#is_day_off').prop('checked', parseInt($(invoker).data('is_day_off')) === 1);
    $('input[name="day_off_symbol"]').val($(invoker).data('day_off_symbol') || 'OFF');
    toggle_day_off_fields();

    $('.add-title').addClass('hide');
    $('.edit-title').removeClass('hide');
    $('#shift').modal('show');
}