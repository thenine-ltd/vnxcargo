var ajax_url = global_new_tyo.ajaxurl;
jQuery(document).ready(function(){

	initmap();
});

function initmap(){
	var directionsService = new google.maps.DirectionsService();
	var directionsDisplay = new google.maps.DirectionsRenderer();
	var map = new google.maps.Map(jQuery('#map')['0'], {
		zoom: 15,
		center: {lat:0,lng:0}
	});
	var mapvalue = jQuery(document).find("#mwb_tyo_google_distance_map").val();
	console.log( mapvalue );
	var mapdata = jQuery.parseJSON(mapvalue);
	directionsDisplay.setMap(map);
	calculateAndDisplayRoute(directionsService, directionsDisplay,mapdata);
}

function calculateAndDisplayRoute(directionsService, directionsDisplay, mapdata)
{
	var waypts = [];
	var checkboxArray = mapdata;
	for (var i = 0; i < checkboxArray.length; i++) {
		waypts.push({
			location: checkboxArray[i],
			stopover: true
		});
	}
	var lats = parseFloat(jQuery(document).find("#start_hidden").val());
	console.log(lats);
	var longs = parseFloat(jQuery(document).find("#end_hidden").val());
	var end = jQuery(document).find("#billing_hidden").val();
	directionsService.route({
		origin: {lat:lats,lng:longs},
		destination: end,
		waypoints: waypts,
		optimizeWaypoints: true,
		travelMode: 'DRIVING'
	}, function(response, status) {
		if (status === 'OK') {
			directionsDisplay.setDirections(response);
			var route = response.routes[0];
			var summaryPanel = jQuery('#directions-panel')['0'];
			summaryPanel.innerHTML = '';
        } else {
        	window.alert('Directions request failed due to ' + status);
        }
    });



}


jQuery(document).ready(function($){
	jQuery(document).on( 'click', '.wps_export', function(e){
		e.preventDefault();

		jQuery.ajax({
			url:ajax_url,
			type:"POST",
			datatType: 'JSON',
			data: {
				action : 'wps_wot_export_my_orders',
				nonce : global_new_tyo.mwb_tyo_nonce,
			},success:function(response){
				var result = JSON.parse(response);
				if( 'success' == result.status ) {
					var filename = result.file_name;
                    var order_data = result.order_data;
					var filename = filename + '.csv';
                    let csvContent = "data:text/csv;charset=utf-8,";
                    order_data.forEach(function(rowArray) {
                       let row = rowArray;
                       csvContent += row + "\r\n";
                             });
                   
                   var encodedUri = encodeURI(csvContent);
                        download(filename, encodedUri);
				}
			
			}	

		});

	} );

	jQuery(document).on( 'click', '.wps_wot_guest_user_export_button', function(e){
		e.preventDefault();
		var email = jQuery(this).parent().find( '.wps_wot_export_email' ).val();
		
		jQuery.ajax({
			url:ajax_url,
			type:"POST",
			datatType: 'JSON',
			data: {
				action : 'wps_wot_export_my_orders_guest_user',
				email  : email,
				nonce : global_new_tyo.mwb_tyo_nonce,
			},success:function(response){
				var result = JSON.parse(response);
				if( 'success' == result.status ) {
					var filename = result.file_name;
                    var order_data = result.order_data;
					var filename = filename + '.csv';
                    let csvContent = "data:text/csv;charset=utf-8,";
                    order_data.forEach(function(rowArray) {
                       let row = rowArray;
                       csvContent += row + "\r\n";
                             });
                   
                   var encodedUri = encodeURI(csvContent);
                        download(filename, encodedUri);
				}
			
			}	

		});

	} );

	function download(filename, text) {
		var element = document.createElement('a');
		element.setAttribute('href', text);
		element.setAttribute('download', filename);
		element.style.display = 'none';
		document.body.appendChild(element);
		// automatically run the click event for anchor tag
		element.click();
   
		document.body.removeChild(element);
		

   }
});