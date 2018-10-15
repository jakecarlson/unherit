var loc, map, used_markers;
var markers = jQuery.parseJSON(destination_map_options.markers) || [];
var pin_image = destination_map_options.path+'/'+destination_map_options.pin_images;
var pin_directory_item = destination_map_options.pin_directory_item;
var pin_current_dest_img = destination_map_options.path+'/'+destination_map_options.pin_current_dest_img;
var pin_current_dest = destination_map_options.pin_current_dest;
var close_image = destination_map_options.path+'/close.png';
loc = new google.maps.LatLng(destination_map_options.general_latitude, destination_map_options.general_longitude);
destInfoBoxes = newInfoBox();

function initializeDestinationMaps() {
	var used_markers = [];
	var mapOptions = {
		flat:false,
		noClear:false,
		zoom: parseInt(destination_map_options.page_custom_zoom),
		zoomControl: destination_map_options.zoom_control,
		scrollwheel: destination_map_options.zoom_scrollwheel,
		draggable: true,
		disableDoubleClickZoom: true,
		center: loc,
		mapTypeId: destination_map_options.type.toLowerCase(),
		streetViewControl:false,
		disableDefaultUI: true,
	};
	map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);


	if (destination_map_options.map_style !==''){
	   var styles = JSON.parse ( destination_map_options.map_style );
	   map.setOptions({styles: styles});
	}

	infoBox = destInfoBoxes; // newInfoBox();

	// Add markers and info boxes
	jQuery.each(markers, function( index, value ) {

		pinIcon = destination_map_options.path+'/'+value.pin_img;

		used_markers[index] = new google.maps.Marker({
			map: map,
			position: new google.maps.LatLng( value.latitude, value.longitude ),
			visible: true,
			icon: pinIcon
		});

		used_markers[index].destInfoBoxID = index; // set the infobox ID

		// if(index == pin_directory_item) {
		// 	loadInfoBox(used_markers[index], true);
		// } else if(index == pin_current_dest) {
		// 	loadInfoBox(used_markers[index], false);
		// }

		// Assign click behavior to show infoBox
		if (destination_map_options.info_on_click) {
			google.maps.event.addListener(used_markers[index], 'click', function() {
				loadInfoBox(this, true);
			});
		}
	});

	// Save the marker data
	jQuery('body').data('map_markers', used_markers);

	// Trigger extras
	jQuery('body').trigger('after_destination_maps');

}

// Load the maps
if (typeof google === 'object' && typeof google.maps === 'object') {
	google.maps.event.addDomListener(window, 'load', initializeDestinationMaps);
}


// Map helper functions
// --------------------------------------------------------

// Create infobox object
function newInfoBox() {
	var myOptions = {
		disableAutoPan: true,
		maxWidth: 0,
		boxClass: "infobox-destination",
		alignBottom: false,
		pixelOffset: new google.maps.Size(32, -88),
		zIndex: null,
		closeBoxMargin: "-6px -6px -10px 0",
		closeBoxURL: close_image,
		infoBoxClearance: new google.maps.Size(1, 1),
		isHidden: false,
		pane: "floatPane",
		enableEventPropagation: false
	};

	// Create infoBox object
	var infoBox = new InfoBox(myOptions);

	return infoBox;
}

// Display infoBox
function loadInfoBox(element, show) {

	infoBox = destInfoBoxes;

	id = element.destInfoBoxID;
	map.panTo(element.getPosition());
	infoBox.setContent(document.getElementById("infobox-destination["+id+"]").innerHTML);
	if (show) {
		infoBox.open(map, element);
	}
}

