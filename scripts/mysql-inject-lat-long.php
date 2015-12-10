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

mysql-inject-lat-long.php

Created: 2015-10-28
Modified: 2015-10-28
Steven Braun

This script injects latitude/longitude data for fellows' host and Ph.D.-granting institutions 
into the MySQL database, TABLE 'institution_data'

///////////////////////////////////////////////////////////////////////////////////////*/

// Initialize some global variables
include("inc/default-config.php");

// Database connection; parameters defined globally in default_config.php
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

// Here, latitude and longitude data were retrieved from a simple CSV file;
// each row represented a record for a single academic institution and included 
// institution name, institution latitude, and institution longitude

$data_file = fopen("[FILE_NAME].csv","r");
print "Injecting institution data...\n";
while($line = fgetcsv($data_file)) {
	// Note CSV file structure: 
	//		[INSTITUTION_NAME],[INSTITUTION_LATITUDE],[INSTITUTION_LONGITUDE]\n\r
	$inst_name = $line[0];
	$lat = $line[1];
	$lon = $line[2];
	$sql = "INSERT IGNORE INTO institution_data (i_name, i_lat, i_lon) VALUES ('" . 
			mysqli_real_escape_string($con,$inst_name) . "','" .
			$lat . "','" . $lon . "')";
	print "\tInjecting\t" . $inst_name . "\n";
	if(!mysqli_query($con,$sql)) {
		die(mysqli_error($con));
	}
}


// After injecting latitude and longitude data, update fellows_data table with institution IDs
print "Update fellows_data table...\n";
$sql = "SELECT fid, f_institution FROM fellows_data";
$result = mysqli_query($con,$sql);

while($row = mysqli_fetch_assoc($result)) {
	$fid = $row['fid'];
	$inst = $row['f_institution'];
	$subsql = "SELECT i_id FROM institution_data WHERE i_name = '$inst'";
	$subresult = mysqli_query($con,$subsql);
	
	if(mysqli_num_rows($subresult) > 0) {
		print "\tUpdating\t" . $fid . "\n";
		$subobj = mysqli_fetch_object($subresult);
		$i_id = $subobj->i_id;
		$inject = "UPDATE fellows_data SET i_id = $i_id WHERE fid = $fid";
		if(!mysqli_query($con,$inject)) {	
			die(mysqli_error($con));
		}
	}
	mysqli_free_result($subresult);
}

print "Process complete.\n";

?>