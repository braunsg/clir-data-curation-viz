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

mysql-inject-fellow-data.php

Created: 2015-09-08
Modified: 2015-09-10
Steven Braun

This script pulls biographical data about CLIR fellows from a file  
and injects it into a MySQL database for easy manipulation and storage.

/////////////////////////////////////////////////////////////////////////////////////// */

// Initialized parameters
ini_set("auto_detect_line_endings",true);
include("inc/default_config.php");

// Database connection; parameters defined globally in default_config.php
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

$file = fopen("[PEOPLE_DATA_FILE].csv","r");
$counter = 0;

// Read through the people data file, one line at a time
while($line = fgetcsv($file)) {
	// Skip header row
	if(++$counter != 1) {

		// Only grab data if line isn't empty
		if(!empty($line[0]) && $line[0] !== "") {
	
			// NOTE CSV FILE STRUCTURE:
			//		FELLOWSHIP_TIME_PERIOD,FELLOW_NAME,FELLOW_PHD,FELLOW_PHD_INSTITUTION,FELLOW_LOCATION_NOTES
			
			// Split fellowship year range into start and end
			$fellowship_time_span = explode("-",trim($line[0]));
			$fellowship_start = $fellowship_time_span[0];
			$fellowship_end = $fellowship_time_span[1];
	
			// Grab and trim fellow name
			$fellow_name = trim($line[1]);
	
			// Grab and trim fellow disciplinary/Ph.D. focus
			$fellow_phd = trim($line[2]);

			// Grab and trim fellow institution
			$fellow_institution = trim($line[3]);
	
			// Grab and trim fellow location notes, if any
			$fellow_loc_notes = trim($line[4]);
	
			// Print out, just for tracking/reporting purposes
			print $fellow_name . "\n";
				print "\tFellowship Start\t" . $fellowship_start . "\n";
				print "\tFellowship End\t" . $fellowship_end . "\n";
				print "\tFellow Ph.D.\t" . $fellow_phd . "\n";
				print "\tFellow Institution\t" . $fellow_institution . "\n";
	
			if(!empty($fellow_loc_notes) && $fellow_loc_notes !== "") {
				$loc_value = true;
				print "\tInstitution Location Notes\t" . $fellow_loc_notes . "\n";
			} else {
				$loc_value = false;
			}
				
			if($loc_value == false) {
				$sql = "INSERT INTO fellows_data (f_name, f_startyear, f_endyear, f_phd, f_institution) " .
					   "VALUES (" . 
							"'" . mysqli_real_escape_string($con,$fellow_name) . "', " .
							"$fellowship_start, " .
							"$fellowship_end, " . 
							"'" . mysqli_real_escape_string($con,$fellow_phd) . "', " . 
							"'" . mysqli_real_escape_string($con,$fellow_institution) . "'" . 
						")";
				   
			} else {
				$sql = "INSERT INTO fellows_data (f_name, f_startyear, f_endyear, f_phd, f_institution, f_location_notes) " .
					   "VALUES (" . 
							"'" . mysqli_real_escape_string($con,$fellow_name) . "', " .
							"$fellowship_start, " .
							"$fellowship_end, " . 
							"'" . mysqli_real_escape_string($con,$fellow_phd) . "', " . 
							"'" . mysqli_real_escape_string($con,$fellow_institution) . "', " . 
							"'" . mysqli_real_escape_string($con,$fellow_loc_notes) . "'" . 
						")";
		
			}
		
			if(!mysqli_query($con,$sql)) {
				die("Error:\t" . mysqli_error($con) . "\n");
			} else {
				print "\tENTRY ADDED\n";
			}
		
			print "\n\n";

		}
	}
}

print "Injection complete.\n";
fclose($file);

?>