<script type="text/javascript">
	var list_account_type_details, fnServerParams, accounts_view = 'tree', accounts_table_initialized = false;
	var acc_tree_lang = {
		edit: '<?php echo _l('edit'); ?>',
		delete: '<?php echo _l('delete'); ?>',
		history: '<?php echo _l('account_history'); ?>',
		status: '<?php echo _l('staff_dt_active'); ?>',
		no_records: '<?php echo _l('acc_no_accounts_found'); ?>',
		self_parent: '<?php echo _l('acc_parent_account_self_error'); ?>',
	};
	(function($) {
		"use strict";

    $( document ).ready(function() {
		appValidateForm($('#account-form'), {
			account_type_id: 'required',
			account_detail_type_id: 'required',
			name: 'required',
			number: 'required',
    	},account_form_handler);

		fnServerParams = {
      "ft_type": '[name="ft_type"]',
      "ft_detail_type": '[name="ft_detail_type"]',
      "ft_parent_account": '[name="ft_parent_account"]',
      "ft_account": '[name="ft_account"]',
      "ft_active": '[name="ft_active"]',
    };
    $('select[name="ft_type"]').on('change', function() {
      refresh_accounts_view();
    });
    $('select[name="ft_active"]').on('change', function() {
      refresh_accounts_view();
    });
    $('select[name="ft_detail_type"]').on('change', function() {
      refresh_accounts_view();
    });

    $('select[name="ft_parent_account"]').on('change', function() {
      refresh_accounts_view();
    });

    $('select[name="ft_account"]').on('change', function() {
      refresh_accounts_view();
    });

    $('#accounts-view-switch button').on('click', function() {
      var view = $(this).data('view');
      if (view === accounts_view) {
        return;
      }
      accounts_view = view;
      $('#accounts-view-switch button').removeClass('active');
      $(this).addClass('active');

      if (view === 'tree') {
        $('#accounts-list-view').hide();
        $('#accounts-tree-view').show();
        $('#accounts-tree-actions').removeClass('hide');
        load_accounts_tree();
      } else {
        $('#accounts-tree-view').hide();
        $('#accounts-list-view').show();
        $('#accounts-tree-actions').addClass('hide');
        // The table is built on first use so it never initializes while hidden
        if (!accounts_table_initialized) {
          accounts_table_initialized = true;
          init_account_table();
        } else if ($.fn.DataTable.isDataTable('.table-accounts')) {
          $('.table-accounts').DataTable().columns.adjust().draw(false);
        }
      }
    });

    $('#accounts-tree-expand-all').on('click', function() {
      set_tree_node_collapsed($('#accounts-tree-root .tree-node'), false);
    });

    $('#accounts-tree-collapse-all').on('click', function() {
      set_tree_node_collapsed($('#accounts-tree-root .tree-node'), true);
    });

    $('#accounts-tree-root').on('click', '.tree-toggle', function() {
      var $node = $(this).closest('.tree-node');
      set_tree_node_collapsed($node, !$node.hasClass('collapsed'));
    });

    // Clicking a category/type header row anywhere toggles that branch
    $('#accounts-tree-root').on('click', '.tree-group > .tree-row, .tree-parent > .tree-row', function(e) {
      if ($(e.target).closest('.tree-toggle, .tree-actions').length) {
        return;
      }
      var $node = $(this).closest('.tree-node');
      set_tree_node_collapsed($node, !$node.hasClass('collapsed'));
    });

    // Compact active/inactive button, same endpoint the list view switch uses
    $('#accounts-tree-root').on('click', '.tree-btn-status', function(e) {
      e.preventDefault();
      var $btn = $(this);
      var status = $btn.hasClass('is-active') ? 0 : 1;
      requestGet(admin_url + 'accounting/change_account_status/' + $btn.data('id') + '/' + status);
      $btn.toggleClass('is-active');
      $btn.closest('.tree-row').find('.tree-name').toggleClass('inactive-account');
    });

	 	list_account_type_details = <?php echo json_encode($detail_types); ?>;

		  $('.add-new-account').on('click', function(){
          if($('select[name="account_type_id"]').val() <= 10 && $('select[name="account_type_id"]').val() != 1 && $('select[name="account_type_id"]').val() != 6){
            $('#div_balance').removeClass('hide');
          }else{
            $('#div_balance').addClass('hide');
          }

          if($('select[name="account_type_id"]').val() == 3 && $('select[name="account_detail_type_id"]').val() == 14){
            $('#div_bank_info').removeClass('hide');
          }else{
            $('#div_bank_info').addClass('hide');
          }

          $('#account-modal').find('button[type="submit"]').prop('disabled', false);

          $('select[name="parent_account"]').val('').change();

          $('input[name="name"]').val('');
          $('input[name="number"]').val('');
          $('input[name="balance"]').val('');
          $('input[name="balance_as_of"]').val('');
          $('input[name="bank_name"]').val('');
          $('input[name="bank_account"]').val('');
          $('input[name="bank_routing"]').val('');
          $('input[name="address_line_1"]').val('');

          tinyMCE.activeEditor.setContent('');
          $('textarea[name="description"]').val('');
          $('input[name="id"]').val('');
	        $('#account-modal').modal('show');
	    });

    var html = '';
      var note = 0;
        $.each(list_account_type_details, function( index, value ) {
          if(value.account_type_id == $('select[name="account_type_id"]').val()){
            if(note == 0){
              $('#detail_type_note').val(value.note);
              note = 1;
            }
            html += '<option value="'+value.id+'">'+value.name+'</option>';
          }
      });

      $('select[name="account_detail_type_id"]').html(html);
      $('select[name="account_detail_type_id"]').selectpicker('refresh');

      $.each(list_account_type_details, function( index, value ) {
          if(value.id == $('select[name="account_detail_type_id"]').val()){
            $('.detail_type_note').html(value.note);
          }
      });

 	// Tree is the default view; the list table is initialized the first time it is opened
 	load_accounts_tree();

 	// Hierarchical codes: picking a parent suggests the next free child
 	// code (parent 1 -> 11, 12, ...), otherwise the next free code inside
 	// the group digit of the selected type (never another group's digit).
 	// Only fills an empty number field, never overwrites what the user typed.
 	function suggest_account_number() {
 	  var $number = $('#account-modal input[name="number"]');
 	  if ($number.length === 0 || $.trim($number.val()) !== '') {
 	    return;
 	  }
 	  var parent_id = $('#account-modal select[name="parent_account"]').val() || 0;
 	  var type_id = $('#account-modal select[name="account_type_id"]').val() || 0;
 	  requestGetJSON(admin_url + 'accounting/suggest_account_number?parent_account=' + parent_id + '&account_type_id=' + type_id).done(function(response) {
 	    if (response && response.number && $.trim($number.val()) === '') {
 	      $number.val(response.number);
 	    }
 	  });
 	}
 	$('#account-modal select[name="parent_account"]').on('change', suggest_account_number);
 	$('#account-modal select[name="account_type_id"]').on('change', suggest_account_number);

		$('select[name="account_type_id"]').on('change', function() {

      if($(this).val() <= 10 && $(this).val() != 1 && $(this).val() != 6 && $('input[name="id"]').val() == ''){
        $('#div_balance').removeClass('hide');
      }else{
        $('#div_balance').addClass('hide');
      }

      

			var html = '';
			var note = 0;
		  	$.each(list_account_type_details, function( index, value ) {
		  		if(value.account_type_id == $('select[name="account_type_id"]').val()){
		  			if(note == 0){
			  			$('#detail_type_note').val(value.note);
			  			note = 1;
		  			}
			  		html += '<option value="'+value.id+'">'+value.name+'</option>';
		  		}
			});

			$('select[name="account_detail_type_id"]').html(html);
			$('select[name="account_detail_type_id"]').selectpicker('refresh');

      $.each(list_account_type_details, function( index, value ) {
          if(value.id == $('select[name="account_detail_type_id"]').val()){
            $('.detail_type_note').html(value.note);
          }
      });

      if($('select[name="account_type_id"]').val() == 3 && $('select[name="account_detail_type_id"]').val() == 14){
        $('#div_bank_info').removeClass('hide');
      }else{
        $('#div_bank_info').addClass('hide');
      }
		});

	  	$('select[name="account_detail_type_id"]').on('change', function() {
        if($('select[name="account_type_id"]').val() == 3 && $('select[name="account_detail_type_id"]').val() == 14){
          $('#div_bank_info').removeClass('hide');
        }else{
          $('#div_bank_info').addClass('hide');
        }

	  		$.each(list_account_type_details, function( index, value ) {
		  		if(value.id == $('select[name="account_detail_type_id"]').val()){
			  		$('.detail_type_note').html(value.note);
		  		}
			});
	 	});

	$("input[data-type='currency']").on({
      keyup: function() {
        formatCurrency($(this));
      },
      blur: function() {
        formatCurrency($(this), "blur");
      }
  });

  $('input[name="mass_activate"]').on('change', function() {
    if($('#mass_activate').is(':checked') == true){
      $('#mass_delete').prop( "checked", false );
      $('#mass_deactivate').prop( "checked", false );
    }
  });

  $('input[name="mass_deactivate"]').on('change', function() {
    if($('#mass_deactivate').is(':checked') == true){
      $('#mass_delete').prop( "checked", false );
      $('#mass_activate').prop( "checked", false );
    }
  });

  $('input[name="mass_delete"]').on('change', function() {
    if($('#mass_delete').is(':checked') == true){
      $('#mass_activate').prop( "checked", false );
      $('#mass_deactivate').prop( "checked", false );
    }
  });

  });
})(jQuery);

function edit_account(id) {
  "use strict";
    $('#account-modal').find('button[type="submit"]').prop('disabled', false);

  requestGetJSON(admin_url + 'accounting/get_data_account/'+id).done(function(response) {
      $('#account-modal').modal('show');

      $('select[name="account_type_id"]').val(response.account_type_id).change();
      $('select[name="account_detail_type_id"]').val(response.account_detail_type_id).change();
      if(response.parent_account != 0){
        $('select[name="parent_account"]').val(response.parent_account).change();
      }else{
        $('select[name="parent_account"]').val('').change();
      }
      $('input[name="number"]').val(response.number);
      $('input[name="name"]').val(response.name);
      $('input[name="id"]').val(id);
      $('input[name="balance"]').val(response.balance);
      $('input[name="balance_as_of"]').val(response.balance_as_of);
      $('input[name="bank_name"]').val(response.bank_name);
      $('input[name="bank_account"]').val(response.bank_account);
      $('input[name="bank_routing"]').val(response.bank_routing);
      $('input[name="address_line_1"]').val(response.address_line_1);

      if(response.description != null){
          tinyMCE.activeEditor.setContent(response.description);
      }else{
            tinyMCE.activeEditor.setContent('');
      }
      $('textarea[name="description"]').val(response.description);
      if(response.balance > 0){
        $('input[name="update_balance"]').val(0);
        $('#div_balance').addClass('hide');
      }else{
        $('input[name="update_balance"]').val(1);
        $('#div_balance').removeClass('hide');
      }
  });
}

function account_form_handler(form) {
    "use strict";
    // An account can never be its own parent; deeper loops are rejected
    // server-side with an explanatory message.
    var edited_id = $(form).find('input[name="id"]').val();
    var parent_val = $(form).find('select[name="parent_account"]').val();
    if (edited_id != '' && parent_val != '' && parent_val != null && String(parent_val) === String(edited_id)) {
      alert_float('danger', acc_tree_lang.self_parent || 'An account cannot be its own parent account');
      $('#account-modal').find('button[type="submit"]').prop('disabled', false);
      return false;
    }
    $('#account-modal').find('button[type="submit"]').prop('disabled', true);
    tinyMCE.triggerSave();
    
    var formURL = form.action;
    var formData = new FormData($(form)[0]);

    $.ajax({
        type: $(form).attr('method'),
        data: formData,
        mimeType: $(form).attr('enctype'),
        contentType: false,
        cache: false,
        processData: false,
        url: formURL
    }).done(function(response) {
        response = JSON.parse(response);
        if (response.success === true || response.success == 'true' || $.isNumeric(response.success)) {
          	alert_float('success', response.message);

          	$('#account-modal').modal('hide');
          	window.location.reload();
        }else{
          // Keep the modal open so the user can fix the parent selection.
          $('#account-modal').find('button[type="submit"]').prop('disabled', false);
          alert_float('danger', response.message);
        }
    }).fail(function(error) {
        $('#account-modal').find('button[type="submit"]').prop('disabled', false);
        alert_float('danger', JSON.parse(error.mesage));
    });

    return false;
}

function formatNumber(n) {
  "use strict";
  // format number 1000000 to 1,234,567 (with dynamic separator)
  var thousand_sep = (typeof(acc_thousand_separator) !== 'undefined') ? acc_thousand_separator : ((typeof(app) !== 'undefined' && app.options && app.options.thousand_separator) ? app.options.thousand_separator : ',');
  return n.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, thousand_sep);
}

function formatCurrency(input, blur) {
  "use strict";
  var decimal_sep = (typeof(acc_decimal_separator) !== 'undefined') ? acc_decimal_separator : ((typeof(app) !== 'undefined' && app.options && app.options.decimal_separator) ? app.options.decimal_separator : '.');
  var thousand_sep = (typeof(acc_thousand_separator) !== 'undefined') ? acc_thousand_separator : ((typeof(app) !== 'undefined' && app.options && app.options.thousand_separator) ? app.options.thousand_separator : ',');

  // get input value
  var input_val = input.val();

  // don't validate empty input
  if (input_val === "") { return; }

  // original length
  var original_len = input_val.length;

  // initial caret position
  var caret_pos = input.prop("selectionStart");

  // check for decimal
  if (input_val.indexOf(decimal_sep) >= 0) {

    // get position of first decimal
    var decimal_pos = input_val.indexOf(decimal_sep);
    var minus = input_val.substring(0, 1);
    if(minus != '-'){
      minus = '';
    }

    // split number by decimal point
    var left_side = input_val.substring(0, decimal_pos);
    var right_side = input_val.substring(decimal_pos + 1);

    left_side = formatNumber(left_side);

    // validate right side (only digits)
    right_side = right_side.replace(/\D/g, "");

    // Limit decimal to only 2 digits
    right_side = right_side.substring(0, 2);

    // join number by decimal separator
    input_val = minus + left_side + decimal_sep + right_side;

  } else {
    // no decimal entered
    var minus = input_val.substring(0, 1);
    if(minus != '-'){
      minus = '';
    }
    input_val = formatNumber(input_val);
    input_val = minus + input_val;

  }

  // send updated string to input
  input.val(input_val);

  // put caret back in the right position
  var updated_len = input_val.length;
  caret_pos = updated_len - original_len + caret_pos;  
}

function init_account_table() {
  "use strict";

  if ($.fn.DataTable.isDataTable('.table-accounts')) {
    $('.table-accounts').DataTable().destroy();
  }
  initDataTable('.table-accounts', admin_url + 'accounting/accounts_table', [0], <?php echo (get_option('acc_enable_secondary_currency') == 1) ? '[0,1,2,5,6,7,8,9]' : '[0,1,2,5,6,7,8]'; ?>, fnServerParams, []);
  $('.dataTables_filter').addClass('hide');
}

// Reload whichever accounts view (list/tree) is currently visible, keeping current filters
function refresh_accounts_view() {
  "use strict";
  if (accounts_view === 'tree') {
    load_accounts_tree();
  } else {
    accounts_table_initialized = true;
    init_account_table();
  }
}

function accounts_tree_filter_params() {
  "use strict";
  return {
    ft_type: $('select[name="ft_type"]').val(),
    ft_detail_type: $('select[name="ft_detail_type"]').val(),
    ft_parent_account: $('select[name="ft_parent_account"]').val(),
    ft_account: $('select[name="ft_account"]').val(),
    ft_active: $('select[name="ft_active"]').val(),
  };
}

function load_accounts_tree() {
  "use strict";
  var $root = $('#accounts-tree-root');
  $root.html('<li class="text-muted">' + acc_tree_lang.no_records + '</li>');

  $.post(admin_url + 'accounting/accounts_tree', accounts_tree_filter_params(), function(response) {
    var data = typeof response === 'string' ? JSON.parse(response) : response;
    var tree = data.tree || [];

    if (tree.length === 0) {
      $root.html('<li class="text-muted">' + acc_tree_lang.no_records + '</li>');
      return;
    }

    $root.html(render_accounts_tree_nodes(tree, data.acc_enable_secondary_currency == 1, 0));
    // Open on the master classes only; everything below starts collapsed
    set_tree_node_collapsed($root.find('.tree-node'), true);
  });
}

function set_tree_node_collapsed($nodes, collapsed) {
  "use strict";
  $nodes.each(function() {
    var $node = $(this);
    var $icon = $node.children('.tree-row').find('.tree-toggle i');
    if (collapsed) {
      $node.addClass('collapsed');
      $icon.removeClass('fa-minus-square-o').addClass('fa-plus-square-o');
    } else {
      $node.removeClass('collapsed');
      $icon.removeClass('fa-plus-square-o').addClass('fa-minus-square-o');
    }
  });
}

function acc_tree_escape(value) {
  "use strict";
  return $('<div>').text(value == null ? '' : value).html();
}

// Icon per level: master class, account type, parent account, single account
function accounts_tree_icon(node, depth, has_children) {
  "use strict";
  if (node.is_group) {
    return depth === 0 ? 'fa fa-th-large' : 'fa fa-th-list';
  }
  return has_children ? 'fa fa-dot-circle-o' : 'fa fa-circle-o';
}

function render_accounts_tree_nodes(nodes, show_secondary, depth) {
  "use strict";
  var html = '';

  $.each(nodes, function(index, node) {
    var has_children = node.children && node.children.length > 0;
    var icon_class = accounts_tree_icon(node, depth, has_children);
    var row_class = node.is_group ? (depth === 0 ? ' tree-group tree-class' : ' tree-group') : (has_children ? ' tree-parent' : '');

    html += '<li class="tree-node' + row_class + '"' + (node.is_group ? '' : ' data-id="' + node.id + '"') + '>';
    html += '<div class="tree-row">';
    html += '<span class="tree-toggle' + (has_children ? '' : ' no-children') + '"><i class="fa fa-minus-square-o"></i></span>';
    html += '<span class="tree-icon"><i class="' + icon_class + '"></i></span>';

    if (node.is_group) {
      // Category / account type header: label plus the rolled up total of everything below it
      html += '<span class="tree-name tree-group-name">' + acc_tree_escape(node.name) + '</span>';
      html += '<span class="tree-leader"></span>';
      html += '<span class="tree-meta">';
      html += '<span class="tree-balance">' + node.balance + '</span>';
      if (show_secondary && node.secondary_amount) {
        html += '<span class="tree-secondary">' + node.secondary_amount + '</span>';
      }
      html += '</span>';
    } else {
      var actions = '';

      if (node.can_edit) {
        actions += '<a href="#" class="tree-btn tree-btn-status' + (node.active == 1 ? ' is-active' : '') + '" data-id="' + node.id + '" title="' + acc_tree_lang.status + '"><i class="fa fa-power-off"></i></a>';
        actions += '<a href="#" class="tree-btn" onclick="edit_account(' + node.id + '); return false;" title="' + acc_tree_lang.edit + '"><i class="fa fa-pencil"></i></a>';
      }
      actions += '<a href="' + admin_url + 'accounting/rp_account_history?account=' + node.id + '" class="tree-btn" title="' + acc_tree_lang.history + '"><i class="fa fa-history"></i></a>';
      if (node.can_delete && node.default_account == 0) {
        actions += '<a href="' + admin_url + 'accounting/delete_account/' + node.id + '" class="tree-btn tree-btn-delete _delete" title="' + acc_tree_lang.delete + '"><i class="fa fa-trash-o"></i></a>';
      }

      // a parent account expands/collapses on click (edit stays on the pencil); a leaf opens the editor
      html += '<span class="tree-name' + (node.active == 1 ? '' : ' inactive-account') + '"' + (has_children ? '' : ' onclick="edit_account(' + node.id + ')"') + ' title="' + acc_tree_escape(node.name) + '">' + acc_tree_escape(node.name) + '</span>';
      html += '<span class="tree-actions">' + actions + '</span>';
      html += '<span class="tree-leader"></span>';
      html += '<span class="tree-meta">';
      html += '<span class="tree-balance">' + node.balance + '</span>';
      if (show_secondary && node.secondary_amount) {
        html += '<span class="tree-secondary">' + node.secondary_amount + '</span>';
      }
      html += '</span>';
    }

    html += '</div>';

    if (has_children) {
      html += '<ul class="tree-children">' + render_accounts_tree_nodes(node.children, show_secondary, depth + 1) + '</ul>';
    }

    html += '</li>';
  });

  return html;
}

// journal entry bulk actions action
function bulk_action(event) {
  "use strict";
    if (confirm_delete()) {
        var ids = [],
            data = {};
            data.mass_delete = $('#mass_delete').prop('checked');
            data.mass_activate = $('#mass_activate').prop('checked');
            data.mass_deactivate = $('#mass_deactivate').prop('checked');

        var rows = $($('#accounts_bulk_actions').attr('data-table')).find('tbody tr');

        $.each(rows, function() {
            var checkbox = $($(this).find('td').eq(0)).find('input');
            if (checkbox.prop('checked') === true) {
                ids.push(checkbox.val());
            }
        });
        data.ids = ids;
        
        $(event).addClass('disabled');
        setTimeout(function() {
            $.post(admin_url + 'accounting/accounts_bulk_action', data).done(function() {
                window.location.reload();
            });
        }, 200);
    }
}
</script>
