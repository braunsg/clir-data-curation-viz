/*

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

style_layout.php

Created: 2015-11-16
Modified: 2015-12-10
Steven Braun

This file specifies site-wide style parameters. Some redundancies may be present.

*/


html {
	width: 100%;
	height: 100%;
}

body {
	width: 100%;
	height: 100%;
	margin:0px;
	padding: 0px;
	overflow-x: hidden;
	background-color: #F2F2F2;
	overflow-y: hidden;
}

#body_container {
	width:100%;
	height: 100%;	
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	flex-direction: column;
	flex: 1;
}		

/* Frames */

.frame {
	width: 100%;
	height: 100%;
	min-height: 100%;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;	
	margin: 0px;
	padding: 0px;
	position: relative;
	flex: 1;
}



.content_container {
	width: 100%;
	height: 100%;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;		
	position: relative;
	display: inline-flex;
}

.container_right {
	min-width: 0;
	height: 100%;
	flex: 3;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;	
}

.container_left {
	flex: 2;
	min-width: 0;
	height: 100%;
	padding: 25px;
	box-sizing: border-box;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;	
}


.data_block .data_placeholder {
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 1em;
	text-align: center;
	font-style: italic;
	margin-top: 100px;
}

.section_header {
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 1.4em;
	color: steelblue;
	width: 100%;
	margin-bottom: 15px;
}

#previous_frame {
	margin: 0px;
	padding: 0px;
	position: fixed;
	z-index: 100;
	top: 0;
	left: 0;
	width: 100%;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;	
}

#next_frame {
	position: fixed;
	z-index: 100;
	bottom: 0;
	left: 0;
	width: 100%;
	margin: 0px;
	padding: 0px;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
}

#nav_flyout_container {
	position: relative;
	width: 250px;
	margin: auto;
	padding: 0px;
	font-family: <?php echo $font_default_sans; ?>;
	color: #FFFFFF;
	font-size:0.9em;
	text-align: center;
	letter-spacing: 2px;
	text-transform: uppercase;
	font-variant: small-caps;
	opacity: 0;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	display: flex;
	flex-flow: column;
}

#nav_flyout_subcontainer {
	z-index: 0;
	width: 100%;
	margin: 0px;
	padding: 0px;
	display: none;
}

.nav_item {
	width: 100%;
	padding: 5px;
	flex: 1;
	background-color: #CCCCCC;
	color: #000000;
	cursor: pointer;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
}

.nav_deselected {
	color: #000000;
}

.nav_selected {
	color: #FFF5EE;
}

.nav_item:hover {
	background-color: #666666;
	color: #FFFFFF;
}

#arrow_previous,
#arrow_next {
	z-index: 100;
	width: 100%;
	padding: 5px;
	cursor: pointer;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
}

#arrow_next:hover {
	font-weight: bold;
}

.arrow_fadein {
	background-color: rgba(255,204,102,0.8);
}

.arrow_fadeout {
	background-color: rgba(153,153,153,0.8);
}


.viz_inner_continue_container {
	width: 100%;
	box-sizing: border-box;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;	
}

.viz_inner_continue,
.viz_inner_restart {
	width: 200px;
	margin: 20px auto 10px auto;
	font-family: <?php echo $font_default_sans; ?>;
	color: #FFFFFF;
	background-color: rgba(255,204,102,0.8);
	cursor: pointer;
	padding: 5px;
	text-align: center;
	text-transform: uppercase;
	letter-spacing: 2px;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;	
}

.viz_inner_continue:hover,
.viz_inner_restart:hover {
	font-weight: bold;
	background-color: rgba(255,153,0,0.8);
}

.text_container {
	width: 100%;
	margin: 0px 0px 10px 0px;
	padding: 0px;
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 1.0em;
	color: #333333;
	display: none;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;	
	cursor: default;	
}

#nav_flyout {
	position: fixed;
	z-index: 200;
	left: 0px;
	top: 50%;
	width: 250px;
	height: 200px;
	margin: -100px 0px 0px -225px;
	padding: 5px;
	background-color: rgba(255,255,255,0.8);
	content: "";
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	flex-direction: column;
}

.nav_flyout_item {
	flex: 1;
	width: 100%;
}	
