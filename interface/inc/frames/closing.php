<!-- 

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

closing.php
Last Modified: 2015-12-10, Steven Braun

This file specifies the layout for the closing/acknowledgment frame.

 -->

<!-- Style properties for this visualization only --> 
<style>
#closing {
	background: #85273E;
}

#closing_text_block {
	width: 60%;
	margin: 6% auto 35px auto;
	padding: 15px;
	background: #FFFFFF;
	cursor: default;
	display: flex;
	border: 8px double #85273E;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	line-height: 1.5;
}

#closing_logo {
	flex: 1;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
    background-image: url("inc/img/clir_dlf_logos.png");
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center center;	
}


#closing_text {
	flex: 2;
	padding: 5px;
	margin: 0px;
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 1em;
	text-align: justify;
}

#closing_link_block,
#closing_acknowledgments {
	width: 60%;
	margin: 0 auto 0 auto;
	padding: 15px;
	background: #FFFFFF;
	cursor: default;
	border: 8px double #85273E;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 1em;
	text-align: center;
	line-height: 1.5;
}

#closing a:link,
#closing a:visited {
	font-weight: bold;
	color: #85273E;
	text-decoration: none;
}

#closing a:hover {
	text-decoration: underline;
}

#closing_acknowledgments_header {
	width: auto;
	margin: 25px auto 25px auto;
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 1.1em;
	text-align: center;
	text-transform: uppercase;
	color: #FFFFFF;
	letter-spacing: 2px;
}

</style>

<div id="closing_text_block">
	<div id="closing_logo"></div>
	<div id="closing_text">The Postdoctoral Fellowship Program will continue to focus on data curation and preparing a new generation of knowledgeable scholarly professionals to take leading roles in the development of sustainable approaches to research data curation across institutional, national, and disciplinary boundaries. In 2016 &ndash; 2018, fellows will work in data curation, particularly software curation, for the sciences and social sciences, and for Medieval Studies.</div>
</div>
<div id="closing_link_block">To learn more about the CLIR Postdoctoral Fellowship Program, visit our website at<br><a target="_blank" href="http://www.clir.org/fellowships/postdoc">http://www.clir.org/fellowships/postdoc</a>.</div>
<div id="closing_acknowledgments_header">Acknowledgments</div>
<div id="closing_acknowledgments">This project was a collaborative effort between Alice Bishop (<i>CLIR</i>), Steven Braun (<i>Northeastern University Libraries</i>), Justin Schell (<i>University of Michigan Libraries</i>), and Rita Van Duinen (<i>CLIR</i>). Data analysis and the engineering of the visualizations above were performed by <a target="_blank" href="http://www.stevengbraun.com">Steven Braun</a>.
</div>
