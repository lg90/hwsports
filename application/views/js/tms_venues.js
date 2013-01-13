// a global variable to access the map
var map;
var centre_marker;
var centre_pos  = new google.maps.LatLng( jQuery('input[name="lat"]').val(), jQuery('input[name="lng"]').val() );

function initialize(){
	map = new google.maps.Map(document.getElementById('map'), {
		zoom: 15,
		center: centre_pos,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	});
	
	centre_marker = new google.maps.Marker({ position: centre_pos, map: map, title: "New Venue Location" });

	google.maps.event.addListener(map, 'center_changed', function() {
		var newcentre = map.getCenter();
		centre_marker.setPosition(newcentre);
		jQuery('input[name=lat]').val(newcentre.lat());
		jQuery('input[name=lng]').val(newcentre.lng());
	});
}

google.maps.event.addDomListener(window, 'load', initialize);

/******     The data table section      ******/

jQuery('#venuesTable').jTPS( {perPages:[5,10,15,50,'ALL']} );

jQuery('#createVenue').submit(function(e){//
	e.preventDefault();
    alert("HAHAHAHAHAHHAH");

    var form_data = {
		name : jQuery('[name="name"]').val(),
		description : jQuery('[name="directions"]').val(),
		directions : jQuery('[name="directions"]').val(),
		lat : jQuery('[name="lat"]').val(),
		lng : jQuery('[name="lng"]').val()
	};
	jQuery.ajax({
			url: "/db_venues/insert_venue/",
			type: 'POST',
			async : false,
			data: form_data,
			success: function(msg) {
				alert(msg);
				jQuery('#message').html(msg);
			}
		});
    /*$.ajax({
        type: "POST", 
        async: false, 
        url: base_url+"register/registration_val",   
        data: "register_first_name="+first_name,
        success: function(data){
            $('#inferiz').html(data);
        },
        error: function(){alert('error');}
    }); */    
});
/*jQuery('#submit').submit(function(e){//
	e.preventDefault();
	alert("HAHAHAHAHAHHAH");
		/*var form_data = {
			name : $('[name="name"]').val(),
			description : $('[name="directions"]').val(),
			directions : $('[name="directions"]').val(),
			lat : $('[name="lat"]').val(),
			lng : $('[name="lng"]').val(),
			ajax : '1'
		};
		$.ajax({
			url: "<?php echo site_url(''); ?>",
			type: 'POST',
			async : false,
			data: form_data,
			success: function(msg) {
				$('#message').html(msg);
			}
		});
		return false;*/
	//});
	//return false;
//});*/