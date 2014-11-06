$(function() {

	var table = $("#dataTables-codes").DataTable( {
		"ajax": {
			"url": "/api/retext/code",
			"dataSrc": "APIResponse.responseData.codes"
		},
		"columns": [
			{ "data": "code"},
			{ "data": "message"}
		]
	});

	$("#codeForm").submit(function() {
		$.post('/api/retext/code', {code:$("#retextCode").val(), message:$("#retextMessage").val(), rand:Math.random()}, function(data) {
			var resp = data.APIResponse;
			table.rows.add(resp.responseData.codes).draw();
			console.log(resp.responseData.codes)
			$("#codeFormModal").modal('hide');
		})
		.fail(function(data) {
			var resp = data.responseJSON.APIResponse;
			if (resp.errorData.errorMessage) {
				$("#form-errorMessage").html('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' + resp.errorData.errorMessage + '</div>');
			}
		});
		return false;
	});



	$("#dataTables-codes tbody").on('click', 'tr', function() {
		var btnDel = $("#btnDelete");
		if ( $(this).hasClass("danger") ) {
			btnDel.addClass('disabled');
			$(this).removeClass("danger");
		} else {
			table.$('tr.danger').removeClass('danger');
			btnDel.removeClass('disabled');
			$(this).addClass('danger');
		}
	});

	$('#btnDelete').click( function () {
		var id = $("tr.danger").attr('id');
		$.ajax({
			url: '/api/retext/code/'+id,
			type: 'DELETE'
		}).done(function(data) {
			table.row($('tr.danger')).remove().draw();
		}).fail(function(data) {
			var resp = data.responseJSON.APIResponse;
			if (resp.errorData.errorMessage) {
				$("#errorMessage").html('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' + resp.errorData.errorMessage + '</div>')
			}
		})
    } );


});
