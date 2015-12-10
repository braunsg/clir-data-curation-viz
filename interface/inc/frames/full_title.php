<!DOCTYPE html>

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

full_title.php
Last Modified: 2015-12-10, Steven Braun

This file specifies the visualization framework for the initial title frame.

Note that all animations in this visualization are achieved by stepping through a series of numerically indexed functions that are called in sequence and which initiate each step in the visualization.

 -->

<!-- Style properties for this visualization only --> 
<style>
#full_title {
	background: #339966;
	color: #fff;
}


#title_block {
	width: 100%;
	position: absolute;
	top: 40%;
	display: none;
	cursor: default;
}

#title_text {
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 4em;
	text-align: center;
	width: 100%;
	color: #FFFFFF;
	margin: auto;
	position: relative;
	display: block;
}

#title_subtext {
	font-family: <?php echo $font_default_serif; ?>;
	font-size: 2em;
	text-align: center;
	width: 100%;
	color: #FFFFFF;
	margin: auto;
	position: relative;
	display: block;
	margin-top: 30px;
	font-style: italic;
}

.divider {
	width: 60%;
	height: 2px;
	content: "";
	background-color: #FFFFFF;
	margin: auto;
	display: block;
}

</style>

<div id="title_block">
	<div id="title_text">Ecologies of Innovation and Discovery</div>
	<div class="divider"></div>
	<div id="title_subtext">A Snapshot of CLIR Postdoctoral Fellowships in Data Curation</div>
</div>

<script>

	$("#title_block").delay(800).fadeIn("slow");
	var title_width = $("#full_title").innerWidth(),
		title_height = $(document).innerHeight();
	
	var title_margin = {top: 200, left: 100, right: 100, bottom: 200};
	
	var title_xScale = d3.scale.linear()
		.range([0, title_width]);
		
	var title_yScale = d3.scale.linear()
		.range([0, title_height]);

	var svg_title = d3.selectAll("#full_title")
		.append('svg')
		.attr("id","svg_title")
		.attr('width', title_width)
		.attr('height', title_height)
			.append('g')
			.attr('width',title_width - title_margin.left - title_margin.right)
			.attr('height',title_height - title_margin.top - title_margin.bottom)
			.attr("transform","translate(" + title_margin.left + "," + title_margin.top + ")");

	var title_colors = ["#f7fcf0","#e0f3db","#ccebc5","#a8ddb5","#7bccc4","#4eb3d3","#2b8cbe","#0868ac","#084081"];

	var random_coord_scale = d3.scale.linear()
		.domain([0,1])
		.range([title_margin.left,title_width-title_margin.right]);

	var random_dev_scale = d3.scale.linear()
		.domain([0,1])
		.range([-200,200]);
	
	var title_data = []; 
	var title_max_points = 80;
	var title_max_radius = 50;
	for (var i = 1; i <= title_max_points; i++) {       
		var xVal = random_coord_scale(Math.random()) + random_dev_scale(Math.random()); 
		var yVal = (title_height-xVal) + random_dev_scale(Math.random());
		title_data.push({x: xVal, y: yVal});
	}


	svg_title.selectAll("circle")
		.data(title_data)
		.enter().append("circle")
		.attr("fill","#CECECE")
		.transition()
		.duration(1000)
		.delay(function(d,i) {
			return 500+i*20;
		})
		.attr("r", function() { 
			return Math.floor(Math.random()*title_max_radius + 1);
		})
		.style("opacity",function() {
			return Math.random()*0.6;
		})
		.attr("fill", function() {
			return title_colors[Math.floor(Math.random()*title_colors.length - 1)];
		})
		.attr("cx", function(d) { return d.x; })
		.attr("cy", function(d) { return d.y; });

</script>
</html>