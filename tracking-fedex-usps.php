<?php
/**
*Plugin Name: Tracking for Fedex USPS
*Description: This plugin provides a shortcode that you can insert into any page or post. On the page or post with the inserted shortcode, you will be able to input Fedex and USPS tracking numbers for shipping status update.
*Author: SoftwareElites
*Version: 1.0.0
*Author URI: https://www.software-elites.com/
**/

function tfu_fedex_form_action() {
    require_once(plugin_dir_path(__FILE__).'/library/fedex-common.php5');
    $path_to_wsdl = plugin_dir_path(__FILE__)."/wsdl/TrackService_v18.wsdl";
    ini_set("soap.wsdl_cache_enabled", "0");
    
    $opts = array(
    	  'ssl' => array('verify_peer' => false, 'verify_peer_name' => false)
    	);
    $client = new SoapClient($path_to_wsdl, array('trace' => 1,'stream_context' => stream_context_create($opts)));  // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
    
    $request['WebAuthenticationDetail'] = array(
    	'ParentCredential' => array(
    		'Key' => 'XXX', 
    		'Password' => 'XXX'
    	),
    	'UserCredential' => array(
    		'Key' => 'rgpWGeQbjjPJwbJ8',
    		'Password' => 'RkAfm5j0ruyuxAikEr1ZdpNzt'
    	)
    );

    $request['ClientDetail'] = array(
   	'AccountNumber' => 621564994, 
    	'MeterNumber' => 250408018
    );
    $request['TransactionDetail'] = array('CustomerTransactionId' => '*** Track Request using PHP ***');
    $request['Version'] = array(
    	'ServiceId' => 'trck', 
    	'Major' => '18', 
    	'Intermediate' => '0', 
    	'Minor' => '0'
    );
    $FedexTracking = sanitize_text_field($_POST["Tracking"]);
    $request['SelectionDetails'] = array(
    	'PackageIdentifier' => array(
    		'Type' => 'TRACKING_NUMBER_OR_DOORTAG',
    		'Value' => $FedexTracking
    	)
    );
    
    
    
    try {
    	if(tfu_setEndpoint('changeEndpoint')){
    		$newLocation = $client->__setLocation(tfu_setEndpoint('endpoint'));
    	}
    	
    	$response = $client ->track($request);
    
        if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
    		if($response->HighestSeverity != 'SUCCESS'){
    			echo '<table border="1">';
    			echo '<tr><th>Track Reply</th><th>&nbsp;</th></tr>';
    			tfu_trackDetails($response->Notifications, '');
    			echo '</table>';
    		}else{
    	    	if ($response->CompletedTrackDetails->HighestSeverity != 'SUCCESS'){
    				echo '<table border="1">';
    			    echo '<tr><th>Shipment Level Tracking Details</th><th>&nbsp;</th></tr>';
    			    tfu_trackDetails($response->CompletedTrackDetails, '');
    				echo '</table>';
    			}else{
                              echo '<p>Tracking number: '.esc_html($response->CompletedTrackDetails->TrackDetails->TrackingNumber).'</p>';
                              echo '<p>Summary: '.esc_html($response->CompletedTrackDetails->TrackDetails->StatusDetail->Description).'</p>';
                              echo '<p>Service Type: '.esc_html($response->CompletedTrackDetails->TrackDetails->Service->Description).'</p>';
                              echo '<p>From: '.esc_html($response->CompletedTrackDetails->TrackDetails->ShipperAddress->City).','.esc_html($response->CompletedTrackDetails->TrackDetails->ShipperAddress->StateOrProvinceCode).' '.esc_html($response->CompletedTrackDetails->TrackDetails->ShipperAddress->CountryCode).'</p>';
                              echo '<p>To: '.esc_html($response->CompletedTrackDetails->TrackDetails->DestinationAddress->City).','.esc_html($response->CompletedTrackDetails->TrackDetails->DestinationAddress->StateOrProvinceCode).' '.esc_html($response->CompletedTrackDetails->TrackDetails->DestinationAddress->CountryCode).'</p>';
                              echo '<p>Click <a href="https://www.fedex.com/apps/fedextrack/?tracknumbers='.esc_html($FedexTracking).'"  target="_blank">here</a> if you would like to confirm the status on Fedex website.</p>';
      				//echo '<table border="1">';
    			    //echo '<tr><th>Package Level Tracking Details</th><th>&nbsp;</th></tr>';
    	//		    tfu_trackDetails($response->CompletedTrackDetails->TrackDetails, '');
    			//	echo '</table>';
    			}
    		}
        }else{
        } 
        
    } catch (SoapFault $exception) {
    }

}

function tfu_usps_form_action() {
   #header('Content-type: text/xml');
   $Tracking = sanitize_text_field($_POST["Tracking"]);
   $url = "https://secure.shippingapis.com/ShippingAPI.dll?API=TrackV2&XML=%20%3CTrackRequest%20USERID=%22583HOME07349%22%3E%20%3CTrackID%20ID=%22".$Tracking."%22%3E%3C/TrackID%3E%20%3C/TrackRequest%3E";
   //echo $url. "<br>\n";
   $response = file_get_contents($url);
   //echo $response;
   //echo "\n\n";
   $xml=simplexml_load_string($response) or die("Error: Cannot create object");
   //print_r($xml);
    if (empty($xml)){
        echo "<h1>We are so sorry that something went wrong. Please submit your tracking number again now or at a later time.</h1>";
        return 101;	
    } else {
    //echo json_encode(array('message' => '<h1>Form submited </h1>', 'status' => 1));
    echo "<p>Tracking Number:".esc_html($Tracking)."</p>";
    echo "<p>Summary:".esc_html($xml->TrackInfo->TrackSummary)."</p>";
    foreach ($xml->TrackInfo->TrackDetail as $node) {
        echo "<p>".esc_html($node)."</p>";
    }
    echo '<p>Click <a href="https://tools.usps.com/go/TrackConfirmAction?tLabels='.esc_html($Tracking).'"  target="_blank">here</a> if you would like to confirm the status on USPS website.</p>';
    }
}


function tfu_tracking_form_action() {
    if (isset($_POST['BtnSubmit'])){
        //echo $_POST['Carrier'];
        if ($_POST['Carrier']=='usps'){
            tfu_usps_form_action();
        }
        elseif ($_POST['Carrier']=='fedex'){
            tfu_fedex_form_action();
        }
    }
    $content = '<li>test</li>';
    return $content;
}

function tfu_tracking_html_code(){
    $content = '
	<form style=" display:inline!important;" method="post">
			<label for="pwd">Tracking Number:</label>
			<input type="text" id="Address" name="Tracking">
			<label for="pwd">Carrier:</label>
			<select name="Carrier">
				<option value="usps">USPS</option>
				<option value="fedex">Fedex</option>
			</select>
		<button type="submit" name="BtnSubmit" class="btn btn-default">Submit</button>
	</form>
    ';
    echo $content;
}


function tfu_tracking_shortcode(){
    if ($test !=101){
        tfu_tracking_html_code();
    }
    $test = tfu_tracking_form_action();
}


add_shortcode("tracking-fedex-usps", "tfu_tracking_shortcode"); 
