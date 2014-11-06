$(function() {

	function countMessages() {
	    $.ajax({
            type: "GET",
            dataType: 'json',
            url: "/api/retext/message/count"
        })
        .done(function( data ) {	
        	$("#retext_message").html(data.count);
        })
        .fail(function() { alert( "Error Loading Data" );});
    }

    function countCodes() {
	    $.ajax({
            type: "GET",
            dataType: 'json',
            url: "/api/retext/code/count"
        })
        .done(function( data ) {	
        	$("#retext_codes").html(data.count);
        })
        .fail(function() { alert( "Error Loading Data" );});
    }
    countMessages();
    countCodes();
});