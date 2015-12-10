<?php

/* ///////////////////////////////////////////////////////////////////////////////////////

clir-data-curation-viz
https://github.com/braunsg/clir-data-curation-viz

Open source code for the CLIR postdoctoral fellowship program in data curation visualization project, 
released as a general framework for creating guided interactive visualizations that can be 
presented over the web

COPYRIGHT (C) 2015 Steven Braun

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
A full copy of the license is included in LICENSE.md.

//////////////////////////////////////////////////////////////////////////////////////////
/////// About this file

mysql-reduce-nodes-links-data.php

Created: 2015-11-1
Modified: 2015-11-05
Steven Braun

This script reduces the node and link data structure for the force layout visualization 
to speed up processing and loading time, pulling data from TABLES tokens and links_distance. 
Specifically, this is accomplished by defining a token distance threshold such that only 
token connections with a distance LESS THAN OR EQUAL TO that threshold are included as 
link records in the reduced network visualization.

/////////////////////////////////////////////////////////////////////////////////////// */

// Initialize some global variables
include("inc/default-config.php");

// Database connection; parameters defined globally in default_config.php
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

// Get force layout data
// Define the maximum number of nodes (tokens) FROM EACH SOURCE to retrieve for force layout
$max_nodes = 50;
$force_data = array();
$data_sources = array("responsibilities","qualifications","pds");

// Truncate TABLES tokens_reduced and links_distance_reduced
if(!mysqli_query($con,"TRUNCATE TABLE tokens_reduced")) {
	die(mysqli_error($con));
}

if(!mysqli_query($con,"TRUNCATE TABLE links_distance_reduced")) {
	die(mysqli_error($con));
}

print "Reducing token data for force layout optimization...\n";
sleep(1);

// Now loop through each text source
foreach($data_sources as $source) {

	// Pull the highest-frequency tokens with LENGTH = 1 from TABLE tokens, returning a maximum of $max_nodes
	$sql = "SELECT token_id, token_label, token_length, token_instance_ct, token_coverage_pct, token_source_type FROM tokens WHERE token_source_type = '$source' AND token_length = 1 ORDER BY token_instance_ct DESC LIMIT $max_nodes";
	$result = mysqli_query($con,$sql);

	// Instantiate array to hold force layout data
	$force_data = array();
	
	// Loop through each returned token
	while($row = mysqli_fetch_assoc($result)) {
		$token_id = $row['token_id'];
		$token_label = $row['token_label'];
		$token_length = $row['token_length'];
		$token_instance_ct = $row['token_instance_ct'];
		$token_coverage_pct = (float)$row['token_coverage_pct'];
		$token_data = array("token_label" => $token_label,
									   "token_id" => $token_id,
									   "token_length" => $token_length,
									   "instance_ct" => $token_instance_ct,
									   "coverage" => $token_coverage_pct,
									   "source" => $source);
									   
		// Insert reduced token record into table
		$insert_sql = "INSERT INTO tokens_reduced(token_label, token_id, token_length, token_instance_ct, token_coverage_pct, token_source_type) " .
					  "VALUES ('" . mysqli_real_escape_string($con, $token_label) . "','$token_id'," . $token_length . "," . $token_instance_ct . "," . $token_coverage_pct . ",'$source')";
		
		if(!mysqli_query($con,$insert_sql)) {
			die(mysqli_error($con));
		} else {
			print "\tADDED TOKEN\t" . $source . "\t" . $token_label . "\n";
		
			$force_data[] = $token_data;
		}
	}
	
	mysqli_free_result($result);
	
	// Now get links data for all tokens in $force_data
	$links_data = array();
	
	// Define a dummy variable to determine maximum link weight for token comparison below
	$max_weight = 0;
	
	// Define maximum link distance threshold -- the maximum distance between two tokens 
	// for which a reduced record will be created in TABLE links_distance_reduced
	$link_distance_threshold = 3;
	
	// Now loop through each token in $force_data
	foreach($force_data as $i => $token_data) {
		$token_1_id = $token_data["token_id"];
		$token_1_label = $token_data["token_label"];
		
		// Compare $token_1 against all other tokens in $force_data
		for($j = $i + 1; $j < count($force_data); $j++) {
		
			// Instantiate array to hold token link distance weights
			$weights_array = array();
			
			// Instantiate array to hold token link data
			$links_array = array();
			$token_2_id = $force_data[$j]["token_id"];
			$token_2_label = $force_data[$j]["token_label"];
				
			// Now get link data for these two tokens
			$sql = "SELECT link_distance, COALESCE(SUM(weight)) AS weight_sum FROM links_distance WHERE (source_id = '$token_1_id' AND target_id = '$token_2_id') OR (source_id = '$token_2_id' AND target_id = '$token_1_id') AND link_distance <= $link_distance_threshold";
			$result = mysqli_query($con,$sql);
			
			// Loop through all returned results
			while($subrow = mysqli_fetch_assoc($result)) {
				$link_distance = $subrow["link_distance"];
				$link_weight = $subrow["weight_sum"];
				
				// NOTE: MySQL function COALESCE() will return 0 for a sum of NULL values;
				// We want only NON-NULL links between tokens, so we'll filter here for link weights 
				// greater than 0 only
				if($link_weight > 0) {
				
					// If $link_weight is greater than $max_weight so far, 
					// increase $max_weight to new value
					if($link_weight > $max_weight) {	
						$max_weight = $link_weight;
					}

					// Add reduced token link record to table
					$insert_sql = "INSERT INTO links_distance_reduced(source_id,target_id,link_distance_threshold,weight,link_source_type) " .
								  "VALUES ('$token_1_id','$token_2_id',$link_distance_threshold,$link_weight,'$source')";
		
					if(!mysqli_query($con,$insert_sql)) {
						die(mysqli_error($con));
					} else {
						print "\tADDED LINK\t" . $token_1_label . "\t" . $token_2_label . "\t" . $link_weight . "\n";
					}

				} // END if($link_weight > 0)
			} // END while($subrow)
			
			mysqli_free_result($result);
		
		} // END for($j)
	} // END foreach($force_data)
} // END foreach($source)

print "Process complete.\n";

?>