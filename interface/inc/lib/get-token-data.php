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

get-token-data.php

Created: 2015-11-09
Modified: 2015-12-10
Steven Braun

This script grabs token instance data for selected tokens in frames/token_count_descriptions.php 
via a jQuery $.post() call

/////////////////////////////////////////////////////////////////////////////////////// */

// Load default configuration parameters
include("../config/default-config.php");

// Database connection
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);


$token_id = $_POST["id"];
$token_type = $_POST["token_type"];

$sql = "SELECT tokens.token_label, token_instances.token_pretext, token_instances.token_posttext, token_instances.entity_id FROM tokens INNER JOIN token_instances ON tokens.token_id = token_instances.token_id WHERE tokens.token_id = '$token_id' AND token_instances.entity_type = '$token_type'";
$result = mysqli_query($con,$sql);

while($row = mysqli_fetch_assoc($result)) {
	$token = utf8_encode($row['token_label']);
	$pretext = utf8_encode($row['token_pretext']);
	$posttext = utf8_encode($row['token_posttext']);
	$entity = $row['entity_id'];
	print "<div class='concordance_row'><div class='concordance_pretext'>" . $pretext . "</div><div class='concordance_token'>" . $token . "</div><div class='concordance_posttext'>" . $posttext . "</div></div>";
}




?>