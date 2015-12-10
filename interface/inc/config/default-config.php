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

Created: 2015-11-11
Modified: 2015-12-10
Steven Braun

This file defines site-wide configuration parameters including database connection parameters 
and visualization frame order.

/////////////////////////////////////////////////////////////////////////////////////// */

// Defines default variables used sitewide
ini_set("auto_detect_line_endings",true);
date_default_timezone_set("America/Chicago");

// Define database parameters
$dbhost = "[DATABASE_SERVER_HOST]";
$dbuser = "[DATABASE_USER]";
$dbpw = "[DATABASE_PASSWORD]";
$dbname = "[DATABASE_NAME]";


// Style defaults

$font_default_serif = 'Constantia, "Lucida Bright", Lucidabright, "Lucida Serif", Lucida, "DejaVu Serif", "Bitstream Vera Serif", "Liberation Serif", Georgia, serif';
$font_default_serif = "Georgia,Times,'Times New Roman',serif";
$font_default_sans = '"Franklin Gothic","ITC Franklin Gothic",Arial,sans-serif';
	
// Frame order

$frames = array("full_title" => 1,
				"fellows_map" => 2,
				"token_count_descriptions" => 3,
				"force_static_div" => 4,
				"closing" => 5);

// Now sort frames in ascending number order				
asort($frames);

?>