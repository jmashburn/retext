$(function() {

    function requestData(day) {
        $.ajax({
            type: "GET",
            dataType: 'json',
            url: "/api/retext/message", // This is the URL to the API
            data: { days: day } // Passing a parameter to the API to specify number of days
        })
        .done(function( data ) {
            // When the response to the AJAX request comes back render the chart with new data
            //chart.setData(data);
            console.log(data);
        })
        .fail(function() {
          // If there is no communication between the server, show an error
            alert( "Error Loading Data" );
        });
    }

    // var chart = Morris.Area({
    //     element: 'retext-area-chart',
    //     data: [0, 0],
    //     xkey: 'period',
    //     ykeys: ['value'],
    //     labels: ['Value'],
    //     pointSize: 2,
    //     hideHover: 'auto',
    //     resize: true
    // });

    requestData(7);
});
