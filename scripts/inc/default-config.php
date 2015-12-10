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

default-config.php

Created: 2015-09-10
Modified: 2015-11-05
Steven Braun

This file stores default parameters for data analytics processes in the CLIR fellows project

/////////////////////////////////////////////////////////////////////////////////////// */

// Specify any global parameters
ini_set("auto_detect_line_endings",true);

// This variable defines the MAXIMUM number of tokens on which analyses will be performed;
// this includes maxima referenced across all scripts
$global_max_token_count = 200;

// This variable defines the default qualifier for which fellows should be included in analyses; 
// For this project, scope was limited to only DATA CURATION TRACK fellows with a start year 
// of 2012 OR LATER
$global_pd_sql_qualifier = "WHERE pd_dc = 1 AND pd_startyear >= 2012";

// Specify default database connection parameters
$dbhost = "[DATABASE_SERVER_HOST]";
$dbuser = "[DATABASE_USER]";
$dbpw = "[DATABASE_PASSWORD]";
$dbname = "[DATABASE_NAME]";


// Specify any default functions for token analysis processes

// This function standardizes the way stop words are encoded in token analysis processes;
// Stop words are indicated by [!STOP_WORD], indicated by $stop_word_delimiter below
function encodeStopWord($word) {
	return "[!" . $word . "]";
}

$stop_word_delimiter = "[!";

?>