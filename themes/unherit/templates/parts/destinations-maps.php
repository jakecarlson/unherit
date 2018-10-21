<?php
/**
* Map in Hero (headers)
*/

// Map Styles
$add_map_style = 'position: absolute; bottom: 0; left: 0; width: 100%; height: 100%; -webkit-transition:all 0s linear; -moz-transition:all 0s linear; transition:all 0s linear; z-index:0;';

?>

<div id="gmap_wrapper" style="<?php echo  $add_map_style; // no escaping needed (see above) ?>" >
	<div id="map-canvas"  style="width: 100%; height: 100%;"></div>
</div>