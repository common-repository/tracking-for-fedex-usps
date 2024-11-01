<?php

function tfu_setEndpoint($var){
	if($var == 'changeEndpoint') Return false;
	if($var == 'endpoint') Return 'XXX';
}
function tfu_trackDetails($details, $spacer){
	foreach($details as $key => $value){
		if(is_array($value) || is_object($value)){
        	$newSpacer = $spacer. '&nbsp;&nbsp;&nbsp;&nbsp;';
    		echo '<tr><td>'. esc_html($spacer) . esc_html($key).'</td><td>&nbsp;</td></tr>';
    		tfu_trackDetails($value, $newSpacer);
    	}elseif(empty($value)){
    		echo '<tr><td>'.esc_html($spacer). esc_html($key) .'</td><td>'.$value.'</td></tr>';
    	}else{
    		echo '<tr><td>'.esc_html($spacer). esc_html($key) .'</td><td>'.esc_html($value).'</td></tr>';
    	}
    }
}
?>
