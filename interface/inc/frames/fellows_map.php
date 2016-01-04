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

fellows_map.php
Last Modified: 2015-12-10, Steven Braun

This file specifies the visualization framework for the map showing fellow locations and collaborations.

Note that all animations in this visualization are achieved by stepping through a series of numerically indexed functions that are called in sequence and which initiate each step in the visualization.

 -->

<?php

// Database connection
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

// Set MySQL character set to UTF-8 to avoid later encoding errors
mysqli_set_charset($con,'utf8mb4');

// Colors
$phd_colors_array = array("AH" => "#4292C6", "SS" => "#238B45", "NS" => "#CB181D");

// Get fellows and Ph. D. institution data
$institution_data = array();
$fellows_data = array();
$fellows_links = array();
$fellows_reference_array = array();
$fellows_reference_counter = 0;

$sql = "SELECT fellows_data.fellow_id, fellows_data.f_name, fellows_data.f_startyear, fellows_data.f_phd, fellows_data.f_phd_class, fellows_data.host_i_id, fellows_data.fellow_dc, institution_data.i_id, institution_data.i_name, institution_data.i_lat, institution_data.i_lon FROM fellows_data INNER JOIN institution_data ON fellows_data.i_id = institution_data.i_id";
$result = mysqli_query($con,$sql);

while($row = mysqli_fetch_assoc($result)) {
	$fid = $row['fellow_id'];
	$f_name = $row['f_name'];
	$f_startyear = $row['f_startyear'];
	$f_phd = $row['f_phd'];
	$f_phd_class = $row['f_phd_class'];
	$f_dc = $row['fellow_dc'];	
	$i_id = $row['i_id'];
	$host_i_id = $row['host_i_id'];
	$i_name = $row['i_name'];
	$i_lat = $row['i_lat'];
	$i_lon = $row['i_lon'];
	if(!array_key_exists($i_id,$institution_data)) {
		$institution_data[$i_id] = array("i_id" => $i_id,
										"i_name" => $i_name,
										 "i_lat" => (float)$i_lat,
										 "i_lon" => (float)$i_lon,
										 "grant_inst_status" => "Y",
										 "host_status" => "N");

	}
	$fellows_data[] = array("fid" => $fid,
							"f_name" => $f_name,
							"f_startyear" => $f_startyear,
							"f_phd" => $f_phd,
							"f_phd_class" => $f_phd_class,
							"f_dc" => $f_dc,
							"i_id" => $i_id,
							"host_i_id" => $host_i_id,
							"pub_count" => 0,
							"pubs" => array());

	$fellows_reference_array[$fid] = $fellows_reference_counter;
	$fellows_reference_counter++;
}

mysqli_free_result($result);

// Now get host institution data
foreach($fellows_data as $i => $fellow) {
	if($fellow["f_dc"] == 1) {
		$host_id = $fellow["host_i_id"];
	
		$sql = "SELECT i_name, i_lat, i_lon FROM institution_data WHERE i_id = '$host_id'";
		$result = mysqli_query($con,$sql);
		$obj = mysqli_fetch_object($result);
		$host_name = $obj->i_name;
		$host_lat = $obj->i_lat;
		$host_lon = $obj->i_lon;
		if(!array_key_exists($host_id,$institution_data)) {
			$institution_data[$host_id] = array("i_id" => $host_id,
											"i_name" => $host_name,
											 "i_lat" => (float)$host_lat,
											 "i_lon" => (float)$host_lon,
											 "grant_inst_status" => "N",
											 "host_status" => "Y");
		} else {
			$institution_data[$host_id]["host_status"] = "Y";
		}
	
		$fellows_data[$i]["host_i_name"] = $host_name;
		
		/* EDIT 2016-01-04
		Moved mysqli_free_result inside loop from original position outside loop;
		was giving error saying could not retrieve mysqli_result outside loop */
		
		mysqli_free_result($result);

	}

}

// Now get collaborative links between fellows
$sql = "SELECT pub_id, pub_citation, pub_title, pub_type, pub_link, contributor_fids FROM publications_projects WHERE pub_display = 1 ORDER BY pub_year DESC";
$result = mysqli_query($con,$sql);
while($row = mysqli_fetch_assoc($result)) {
	$pub_id = $row['pub_id'];
	$citation = $row['pub_citation'];
	
	// Grab title for publication/project -- specify unescaped unicode
	// or PHP will throw bool(false)
	$pub_title = json_encode(($row['pub_title']),JSON_UNESCAPED_UNICODE);
	$pub_type = $row['pub_type'];
	$pub_link = $row['pub_link'];
	$contributor_fids = explode(",",$row['contributor_fids']);
	$citation_array = array("pub_title" => $pub_title, "pub_type" => $pub_type, "pub_link" => $pub_link);
	foreach($contributor_fids as $i => $fid) {
		$fellow_reference_index = $fellows_reference_array[$fid];
		if(!is_null($fellow_reference_index)) {
			// If there's a reference array for the fellow, add project data --
			// this catch is here to compensate for collaborating fellows whose cohort year
			// is before 2012
			
			$fellow_reference_dc = $fellows_data[$fellow_reference_index]["f_dc"];
			$fellow_pubs_data = $fellows_data[$fellow_reference_index]["pubs"];
			if(!array_key_exists($pub_id,$fellow_pubs_data)) {
				$fellows_data[$fellow_reference_index]["pub_count"]++;
				$fellows_data[$fellow_reference_index]["pubs"][$pub_id] = $citation_array;
			}
		
			// When generate links, only calculate UNIQUE links (and no self-linking)
			// Also, no linking to pre-2012 fellows
			for($k = $i + 1; $k < count($contributor_fids); $k++) {
				$link_fid = $contributor_fids[$k];
				// Also, no linking to pre-2012 fellows
				if(!is_null($fellows_reference_array[$link_fid])) {
					$link_f_dc = $fellows_data[$fellows_reference_array[$link_fid]]["f_dc"];
					
					// Also, only link between data curation fellows (only ones finally visible)
					if($link_f_dc == 1 && $fellow_reference_dc == 1) {
						$fellows_links[] = array("source_id" => $fid, "source_index" => $i, "target_id" => $link_fid, "target_index" => $k, "pub_id" => $pub_id);
					}
				}
			}
		}
	}
}

mysqli_free_result($result);
?>

<!-- Style properties for this visualization only -->

<style>

#svg_fellows {
	z-index: 0;
	position: relative;
	top: 0px;
	left: 0px;
	background-color: #6BADC9;
	border: none;
}

#svg_fellows_path {
	fill: #E5E5E5;
	stroke: #6BADC9;
	stroke-width: 1px;
}

.fellow_node_circle {
	cursor: default;
}

#map_text {
	z-index: 50;
	position: absolute;
	top: 100px;
	right: 30%;
	width: 20%;
	margin: 0px;
	padding: 20px 20px 5px 20px;
	box-sizing: border-box;
	background-color: #FFFFFF;
	opacity: 0;
	overflow-y: hidden;
}


span#textblock_ss {
	color: <?php echo $phd_colors_array["SS"]; ?>;
}

span#textblock_ah {
	color: <?php echo $phd_colors_array["AH"]; ?>;
}
		
span#textblock_ns {
	color: <?php echo $phd_colors_array["NS"]; ?>;
}

#fellow_node_tip {
	position: absolute;
	top: 100px;
	left: 50px;
	padding: 7px;
	width: 250px;
	max-height: 450px;
	background-color: rgba(255,255,255,0.80);
	opacity: 0;
	overflow-y: scroll;		
}

.fellow_node_name {
	font-size: 1em;
	font-family: <?php echo $font_default_serif; ?>;
	margin: 0px 0px 4px 0px;
	display: inline-block;
	vertical-align: text-top;
}

.i_name_label {
	font-family: <?php echo $font_default_sans; ?>;
	font-size: 1.0em;
	fill: #565656;
	font-weight: bold;
	cursor: default;
}

.fellow_node_year {
	margin: 0px 5px 0px 0px;
	padding: 4px;
	background-color: #CC3333;
	color: #FFFFFF;
	font-family: <?php echo $font_default_sans; ?>;
	font-size: 0.8em;
	display: inline-block;
	line-height: 100%;
	border-radius: 4px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
}

	
ul.pub_list {
	list-style-type: square;
	list-style-position: outside;
	padding: 0px 0px 0px 20px;
	margin: 12px 0px 0px 0px;
	font-family: <?php echo $font_default_sans; ?>;
	font-size: 0.8em;
}

li.pub_row {
	margin-bottom: 5px;
}

.pub_row a:link, .pub_row a:visited {
	color: steelblue;
	text-decoration: none;
}

.pub_row a:hover {
	text-decoration: underline;
}

.pub_row .link_icon {
	margin-right:8px;
}

.pub_row .link_icon img {
	height: 15px;
}
	
		
#fellows_legend {
	z-index: 100;
	position: absolute;
	bottom: 50px;
	right: 50px;
	padding: 10px;
	background-color: rgba(255,255,255,0.8);
	display: none;
}

.fellows_legend_row {
	width: 100%;
	margin: 0px;
	padding: 0px;
	display: block;
}

#fellows_legend_ah {
	width: 20px;
	height: 20px;
	content: "";
	background: #4292C6;
	display: inline-block;
}
#fellows_legend_ss {
	width: 20px;
	height: 20px;
	content: "";
	background: #238B45;
	display: inline-block;
}
#fellows_legend_ns {
	width: 20px;
	height: 20px;
	content: "";
	background: #CB181D;
	display: inline-block;
}

.fellows_legend_text {
	font-size: 1.0em;
	font-family: <?php echo $font_default_serif; ?>;
	cursor: default;
	display: inline-block;
	vertical-align: text-bottom;
}

</style>


<script>
	var institution_data = <?php echo json_encode($institution_data,true); ?>;
	var fellows_data = <?php echo json_encode($fellows_data,true); ?>;
	var fellows_links_data = <?php echo json_encode($fellows_links,true); ?>;
</script>

<div id="map_text">
	<div class="text_chunk text_selected">
		<div class="text_container text_hidden">Established in 2004, the CLIR Postdoctoral Fellowship Program has supported 130 fellows from Ph.D.-granting institutions across the United States, Canada, and England. This program is preparing a new generation of librarians and scholars for work at the intersections of scholarship, teaching, and librarianship in the emerging research environment.</div>
		<div class="text_container text_hidden">Since 2012, the program has expanded notably with support from the Sloan and Mellon Foundations to provide 42 of these fellows opportunities for training specifically in <b>data curation</b>.</div>
	</div>
	<div class="text_chunk">
		<div class="text_container text_hidden">Data curation fellows are newly-minted PhDs with disciplinary expertise across the <span id="textblock_ah">arts and humanities</span>, <span id="textblock_ss">social sciences</span>, and <span id="textblock_ns">natural sciences</span> and have served at 32 different institutions across the United States and Canada.</div>
	</div>
	<div class="text_chunk">
		<div class="text_container text_hidden">Data curation fellows are scholarly practitioners who understand not only the nature and processes of the disciplines broadly defined but also how research data are organized, transmitted, manipulated, reused, and sustained. Together, they have collaborated on presentations, publications, and other projects.</div>
	</div>
	<div class="text_chunk">
		<div class="text_container text_hidden">Hover over the circles to see some examples of projects that have come from these collaborations.
			<div class="viz_inner_restart" id="fellows_map_restart">Start Over</div>	
		</div>
		
	</div>
	<div class="viz_inner_continue_container">
		<div class="viz_inner_continue" id="fellows_map_continue">Continue &rarr;</div>
	</div>
</div>

<div id="fellows_legend">
	<div class="fellows_legend_row">
		<div id="fellows_legend_ah"></div>
		<div class="fellows_legend_text">Arts & Humanities</div>
	</div>
	<div class="fellows_legend_row">
		<div id="fellows_legend_ss"></div>
		<div class="fellows_legend_text">Social Sciences</div>
	</div>
	<div class="fellows_legend_row">
		<div id="fellows_legend_ns"></div>
		<div class="fellows_legend_text">Natural Sciences</div>
	</div>
</div>

<script src="inc/lib/topojson.v1.min.js"></script>
<script>

	function fm_initialize(restart) {
		if(restart) {
			frame_controller("freeze");
			$("#fellows_legend").fadeOut("fast");			
			$("#map_text").css({"top": "100px", "right": "30%", "left":"auto","bottom":"auto"});
			restart_text("#map_text");			
			d3.select("#fellows_map").selectAll("svg").remove();
		}
		
		projection = d3.geo.mercator()
			.rotate([rotate,0])
			.translate([fellows_map_width/2, fellows_map_height/2])
			.center([10,40])
			.scale(1);

		// set up the scale extent and initial scale for the projection
		b = mercatorBounds(projection, maxlat),
			s = fellows_map_width/(b[1][0]-b[0][0]),
			scaleExtent = [s, 10*s];

		projection.scale(s*2);
		
		svg_fellows_path = d3.geo.path()
			.projection(projection);

		svg_fellows = d3.selectAll("#fellows_map")
			.append('svg')
			.attr("id","svg_fellows")
			.attr('width', fellows_map_width)
			.attr('height', fellows_map_height)
				.append('g')
				.attr('width',fellows_map_width)
				.attr('height',fellows_map_height);
		
		svg_fellows.selectAll('path')
			.data(topojson.feature(world, world.objects.countries).features)
			.enter().append('path')
			.attr("id","svg_fellows_path")
			.attr("d",svg_fellows_path);

		fellow_links = svg_fellows.selectAll("path.link")
			.data(fellows_links_data)
			.enter()
			.append("line")
			.attr("stroke","orange")
			.attr("stroke-width",1)
			.attr("fill","none")
			.attr("opacity",0);

		fellow_nodes = svg_fellows.selectAll(".fellow_node")
		   .data(fellows_data)
		   .enter()
		   .append("g")
		   .attr("class","fellow_node")
		   .attr("transform", function(d) {
				var grant_inst_data = institution_data[d.i_id];
				var dev = dev_scale(Math.random())*max_deviation;
				var cx = projection([grant_inst_data.i_lon,grant_inst_data.i_lat])[0] + dev;
				var cy = projection([grant_inst_data.i_lon,grant_inst_data.i_lat])[1] + dev;
				return "translate(" + cx + "," + cy + ")";
			});
			
		fellow_nodes_circles = fellow_nodes.append("circle")
		   .attr("class","fellow_node_circle")
		   .attr("r", 0)
		   .attr("opacity",0.9)
		   .attr("fill", "#666666");
		
			// Call first transition
			fm_keyPress = 0;
			fm_transition_ct = 0;
			fm_transition_done = 0;
			svg_fellows.call(fm_transitions[fm_keyPress]);

	fellow_node_tip = d3.select("#fellows_map").append("div")
		.attr("id","fellow_node_tip")
		.attr("opacity",0);
		
	}


	//////////////////////////////////////////////////////////
	// Define functions /////////////////////////////////////
	////////////////////////////////////////////////////////

	phd_colors = <?php echo json_encode($phd_colors_array,true); ?>;

	function phd_group_color(group) {
		return phd_colors[group];
	}

	function node_detail(fellow_data) {
		var pubs = fellow_data["pubs"];
		var return_text = new String();
		var link_img = "<img src='inc/img/link_icon.png' class='link_icon'>";
		for(var pub_id in pubs) {
			var this_citation = pubs[pub_id];
			var title = this_citation["pub_title"];
			var type = this_citation["pub_type"];
			var link = this_citation["pub_link"];
			return_text += "<div class='pub_row'>" + link_img + title + "</div>";

		}
		return return_text;
	}
	
	// find the top left and bottom right of current projection
	function mercatorBounds(projection, maxlat) {
		var yaw = projection.rotate()[0],
			xymax = projection([-yaw+180-1e-6,-maxlat]),
			xymin = projection([-yaw-180+1e-6, maxlat]);

		return [xymin,xymax];
	}	

	// An attrTween function for moving fellows on map
	function moveFellow(g,m) {
		var this_node = fellow_nodes_dc.filter(function(node,node_index) {
			return node_index == m;
		});

		var cx = d3.transform(this_node.attr("transform")).translate[0];
		var cy = d3.transform(this_node.attr("transform")).translate[1];		
		var path = svg_fellows.selectAll("#path_" + g.fid)
			.data([g])
			.enter()
			.append("path")
				.attr("stroke",function(d) {
					return phd_group_color(d.f_phd_class);
				})
				.attr("stroke-width",1)
				.attr("opacity",0.5)
				.attr("fill","none")
				.attr("id","path_" + g.fid)
				.attr("class","path_tween")
				.attr("d", function() {
					var host_inst_data = institution_data[g.host_i_id];
					var dev = dev_scale(Math.random())*max_deviation;					
					var new_x = projection([host_inst_data.i_lon,host_inst_data.i_lat])[0] + dev;
					var new_y = projection([host_inst_data.i_lon,host_inst_data.i_lat])[1] + dev;

					var dx = new_x - cx,
					dy = new_y - cy,
					dr = Math.sqrt(dx * dx + dy * dy);

					return "M" + cx + "," + cy + "A" + dr + "," + dr + " 0 0,1 " + new_x + "," + new_y;
				});

		var l = path.node().getTotalLength();

		path.attr("stroke-dasharray",l)
			.attr("stroke-dashoffset",l);
			
		return function(t) {
			var p = path.node().getPointAtLength(t * l);
			path.attr("stroke-dashoffset",l - (t*l));
			return "translate(" + p.x + "," + p.y + ")";

		}

	}

	d3.select("#fellows_map_continue").on("click", function() {
		if(typeof fm_transition_done === 'undefined') {
			fm_transition_done = 1;
		}
		if(fm_keyPress < fm_transitions.length && fm_transition_done == 1) {
			$("#fellows_map_continue").fadeTo(200,0);
			fm_transition_done = 0;			
			svg_fellows.call(fm_transitions[fm_keyPress]);
		}
	});

	function fm_resume() {
		fm_transition_done = 1;
		$("#fellows_map_continue").fadeTo(200,1);
		fm_keyPress++;
	}

	//////////////////////////////////////////////////////////
	// Define transitions ///////////////////////////////////
	////////////////////////////////////////////////////////
	
	var fm_transitions = [fm_transition_0, fm_transition_1, fm_transition_2, fm_transition_3, fm_transition_4];
	
	function fm_transition_0() {	
		$("#map_text").fadeTo(1000, 0.8);
		display_text("#map_text");
		fellow_nodes_circles.transition()
			.duration(200)
			.ease("linear")
			.delay(function(d,i) {
				return i*10;
			})
			.attr("r",6)
			.each("start", function() { fm_transition_ct++; })
			.each("end", function(d,i) {
				if(--fm_transition_ct == 0) {
					fm_resume();
				}
			});
	}

	function fm_transition_1() {
		frame_controller("freeze");		
		display_text("#map_text");	
		fellow_nodes_circles.filter(function(d) {
			return d.f_dc == 1;
		}).transition()
			.duration(400)
			.attr("fill","#FFCC33");

		fellow_nodes_circles.filter(function(d) {
			return d.f_dc == 0;
		}).transition()
			.duration(1000)
			.delay(500)
			.attr("opacity",0)
			.each("start", function() { fm_transition_ct++; })
			.each("end", function() {
				if(--fm_transition_ct == 0) {
					fm_resume();
				}
			});
			
		// Update fellow_nodes to limit only to data curation fellows for future animations
		fellow_nodes_dc = fellow_nodes.filter(function(d) { return d.f_dc == 1; });
	}

	function fm_transition_2() {

		$("#map_text").fadeOut(1000, function() {
			display_text("#map_text");
			$("#map_text").css({"left": 150, "top": 100}).fadeIn(1000, function() {
				$("#fellows_legend").fadeIn("medium");
			});
		});

		fellow_nodes_circles.transition()
			.transition()
				.duration(400)
				.ease("quad")
				.delay(function(d,i) {
					return 1500 + i*25;
				})
				.attr("fill", function(d) {
					return phd_group_color(d.f_phd_class);
				});


		var new_center = projection([-95.712891,37.09024]);
		var translate_x = (-1*new_center[0]+projection.translate()[0]);
		var translate_y = (-1*new_center[1]+projection.translate()[1]);
		svg_fellows.transition()
			.duration(750)
			.delay(1500)
			.attr("transform","translate(" + translate_x + "," + translate_y + ")");      
					

		fellow_nodes_dc.transition()
			.duration(3000)
			.delay(function(d,i) { 
				return 5500 + i*75;
			})
			.attrTween("transform", function(g,m) { return moveFellow(g,m); })
			.each("start", function() { 
				d3.select(this).select("circle")
					.transition()
					.duration(100)
					.attr("r",3);
				fm_transition_ct++; 
			})
			.each("end", function(d,i) {
				d3.select(this).select("circle")
					.transition()
					.duration(100)
					.attr("r",6);
					
				if(i_labels_array.indexOf(d.host_i_id) >= 0) {
					d3.select(this).append("text")
						.attr("dx","11px")
						.attr("dy","0.35em")
						.attr("class","i_name_label")
						.text(function(d) { return d.host_i_name; })
						.transition()
							.duration(200)
							.attr("opacity",1)
								.transition()
								.duration(200)
								.delay(500)
								.attr("opacity",0);
				}
								
				svg_fellows.selectAll("path.path_tween")
					.filter(function(l,k) {
						return k == i;
					})
					.transition()
					.duration(100)
					.delay(25)
					.attr("opacity",0)
					.each("end", function() {									
						if(--fm_transition_ct == 0) {
							fellow_nodes.selectAll("text").remove();
							fm_resume();
						}
					});
					
			});

		fellow_nodes_dc
			.on("mouseover", function(d,i) {
				fellow_nodes_dc.selectAll("circle").attr("opacity",0.1);
				d3.select(this).moveToFront();
				d3.select(this).select("circle")
					.attr("opacity",0.9)
					.attr("stroke","#FFCC33")
					.attr("stroke-width",2);

				d3.select(this).append("text")
						.attr("dx","11px")
						.attr("dy","0.35em")
						.attr("class","i_name_label")
						.attr("opacity",1)
						.text(function(d) { return d.host_i_name; });

			}).on("mouseout", function(d,i) {
				fellow_nodes_dc.selectAll("circle").attr("stroke-width",0)
					.attr("opacity",0.9)
					.moveToFront();
				d3.select(this).selectAll("text").remove();
			});
	}



	function fm_transition_3() {

		display_text("#map_text");
		fellow_nodes_dc.on("mouseover",null)
			.on("mouseout",null);
			
		fellow_links.attr("x1", function(d) {
				var cx1 = fellow_nodes_dc.filter(function(c) {
					return c.fid === d.source_id;
				});
				transform_cx1 = d3.transform(cx1.attr("transform")).translate[0];
				return Number(cx1.attr("cx")) + transform_cx1;
			})
			.attr("y1", function(d) {
				var cy1 = fellow_nodes_dc.filter(function(c) {
					return c.fid === d.source_id;
				});
				transform_cy1 = d3.transform(cy1.attr("transform")).translate[1];

				return Number(cy1.attr("cy")) + transform_cy1;
			})
			.attr("x2", function(d) {
				var cx2 = fellow_nodes_dc.filter(function(c) {
					return c.fid === d.target_id;
				});
				transform_cx2 = d3.transform(cx2.attr("transform")).translate[0];
				return Number(cx2.attr("cx")) + transform_cx2;
			})
			.attr("y2", function(d) {
				var cy2 = fellow_nodes_dc.filter(function(c) {
					return c.fid === d.target_id;
				});
				transform_cy2 = d3.transform(cy2.attr("transform")).translate[1];
				return Number(cy2.attr("cy")) + transform_cy2;
			}).transition()
				.duration(400)
				.attr("opacity",1)
				.each("start",function() { fm_transition_ct++; })
				.each("end", function() {
					if(--fm_transition_ct == 0) {
						fm_resume();
					}
				});

		
	}		

	function fm_transition_4() {
		$("#map_text").fadeOut(500, function() {
			display_text("#map_text");
			$("#map_text").css({"left":"auto","right": 150, "top": 100}).fadeIn(500);
		});

		svg_fellows.transition()
			.duration(750)
			.attr("transform",svg_fellows.attr("transform") + " scale(1.4)");
		fellow_nodes_dc.filter(function(c) {
			return c.pub_count == 0;
		}).transition()
			.duration(500)
			.delay(1000)
			.ease("quad")
			.attr("opacity",0);				
		
		var svg_transform = d3.transform(svg_fellows.attr("transform"));
		var svg_translate_x = svg_transform.translate[0];
		var svg_translate_y = svg_transform.translate[1];
		var svg_scale_x = svg_transform.scale[0];
		var svg_scale_y = svg_transform.scale[1];

		fellow_nodes_dc.select("circle").filter(function(c) {
			return c.pub_count > 0;
		}).each(function() {
			d3.select(this).moveToFront();
		})
	   .on("mouseover", function(d) {
			fellow_links.attr("opacity",0.1);
			fellow_links.filter(function(f_l) {
				return f_l.source_id === d.fid || f_l.target_id === d.fid;
			}).attr("opacity",1);		   		

			fellow_node_tip.selectAll("*").remove();
			if(d.pub_count > 0) {
				// Remove all existing node highlights and highlight this node only
				fellow_nodes_circles.attr("stroke-width",0);
				d3.select(this).attr("stroke","#FFCC33").attr("stroke-width",2);

				fellow_node_tip
					.style("opacity",1);
				fellow_node_tip.append("div")
					.attr("class","fellow_node_year")
					.html(d.f_startyear);
				fellow_node_tip.append("div")
					.attr("class","fellow_node_name")
					.html(d.f_name);
				
				var display_counter = 0;
				var fellow_node_ul = fellow_node_tip.append("ul")
					.attr("class","pub_list");

				for(var pub_id in d.pubs) {
					// Limit the number of results to display to avoid overflow on smaller resolutions
					if(++display_counter <= 7) {
						var title = d.pubs[pub_id]["pub_title"];
						var pub_link = d.pubs[pub_id]["pub_link"];
						if(pub_link !== null) {
							fellow_node_ul
								.append("li")
								.attr("class","pub_row")
								.html("<a target='_blank' href='" + pub_link + "'>" + title + "</a>");
						} else {
							fellow_node_ul
								.append("li")
								.attr("class","pub_row")
								.html(title);
						
						}
					}
				}
			}
		}).on("mouseout", function() {
			fellow_links.attr("opacity",1);
		});
		$("#fellows_map_continue").css({"display":"none"});
		$("#fellows_map_restart")
			.click(function() { fm_initialize(true); });

		fm_transition_done = 1;
		frame_controller("unfreeze");
	}
	
	//////////////////////////////////////////////////////////
	// Set global variables /////////////////////////////////
	////////////////////////////////////////////////////////

	// Define an array to hold IDs of institutions that will be labeled during animation
	var i_labels_array = ["i_00005", // UMN
						  "i_00009", // Stanford
						  "i_00091", // Folger Shakespeare Library
						  "i_00087", // University of Alberta
						  "i_00094", // University of Miami
						  "i_00049", // University of Texas at Austin
						  "i_00076", // Purdue University
						  "i_00061", // Vanderbilt
						  "i_00081" // UC-Davis		
						 ];
	
	
	var fm_keyPress = 0;
		
	var max_deviation = 15;

	var maxCount;
	var minCount;
	var dev_scale = d3.scale.linear()
		.domain([0, 1])
		.range([-1, 1]);
		
	var fellows_map_width = $(window).innerWidth(),
		fellows_map_height = $(window).innerHeight(),
		rotate = 60,        // so that [-60, 0] becomes initial center of projection
		maxlat = 75;        // clip northern and southern poles (infinite in mercator)

	//////////////////////////////////////////////////////////
	// Place world map //////////////////////////////////////
	////////////////////////////////////////////////////////
	
	var projection,
		b,
		s,
		scaleExtent,
		svg_fellows_path,
		svg_fellows,
		world,
		fellow_nodes_dc;

	d3.json("inc/data/world_110m2.json", function ready(error, world_data) {
		
		// Load map data
		world = world_data;
		
		// Initialize the visualization
		fm_initialize();	
		
	});
	
</script>
