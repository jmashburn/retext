	var table = $("#dataTables-messages").DataTable( {
		"ajax": {
			"url": "/api/retext/message",
			"dataSrc": "APIResponse.responseData.messages"
		},
		"columns": [
			{ "data": "code"},
			{ "data": "message_received"}
		]
	});

	// $('#dataTables-messages tbody').on('click', 'tr', function() {
	//   var url = '/api/retext/message/' + $(this).attr('id');
	//   $.get(url, function(data) {
	//   	$("#messageModal > .modal-body").html(data);
	//    	$('#messageModal').modal();
	//   });
	// });