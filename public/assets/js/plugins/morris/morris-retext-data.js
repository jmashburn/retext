$(function() {

    function requestData(day) {
        $.ajax({
            type: "GET",
            dataType: 'json',
            url: "/api/retext/message/chart", // This is the URL to the API
            data: { days: day } // Passing a parameter to the API to specify number of days
        })
        .done(function( data ) {
            // When the response to the AJAX request comes back render the chart with new data
            area.setData(data.messages);
            donut.setData(data.codes);
        })
        .fail(function() {
          // If there is no communication between the server, show an error
            alert( "Error Loading Data" );
        });
    }

    var donut = Morris.Donut({
        element: 'retext-donut-chart',
        data: [0,0],
        resize: true
    });

   var area = Morris.Area({
        element: 'retext-area-chart',
        data: [{
            "period":"2014-02-05",
            "messages": 1
        }],
        xkey: 'period',
        ykeys: ['messages'],
        labels: ['Messages'],
        pointSize: 2,
        hideHover: 'auto',
        resize: true
    });

    requestData(7);
});
