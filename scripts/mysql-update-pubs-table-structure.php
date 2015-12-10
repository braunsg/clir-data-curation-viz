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

mysql-update-pubs-table-structure.php

Created: 2015-11-05
Modified: 2015-11-05
Steven Braun

This scripts simply updates the structure of TABLE publications_projects, specifically by 
instantiating zero-padded IDs for pub_id and updating fellow IDs in contributor_fids

/////////////////////////////////////////////////////////////////////////////////////// */

// Initialize some global variables
include("inc/default-config.php");

// Database connection; parameters defined globally in default_config.php
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

print "Updating table structure for publications_projects...\n";
sleep(1);

$sql = "SELECT pub_row_index, contributor_fids FROM publications_projects";
$result = mysqli_query($con,$sql);

while($row = mysqli_fetch_assoc($result)) {
	$pub_row_index = $row['pub_row_index'];
	$contributor_fids = explode(',',$row['contributor_fids']);
	$cleaned_fids = array();
	foreach($contributor_fids as $fid) {
		$cleaned_fids[] = "f_" . str_pad($fid,5,'0',STR_PAD_LEFT);
	}
	
	$contributor_fids_string = implode(',',$cleaned_fids);
	$pub_id = "pub_" . str_pad($pub_row_index,5,'0',STR_PAD_LEFT);
	
	// Now update record
	
	$update_sql = "UPDATE publications_projects SET pub_id = '$pub_id', contributor_fids = '$contributor_fids_string' WHERE pub_row_index = $pub_row_index";
	if(!mysqli_query($con,$update_sql)) {
		print $update_sql . "\n";
		die(mysqli_error($con));
	}
}

print "Done.\n";
	
?>