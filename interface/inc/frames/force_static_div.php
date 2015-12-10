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

force_static_div.php
Last Modified: 2015-12-10, Steven Braun

This file specifies the visualization framework for the semantic network.

Note that all animations in this visualization are achieved by stepping through a series of numerically indexed functions that are called in sequence and which initiate each step in the visualization.

 -->

<!-- Style properties for this visualization only --> 
<style>

.force_node_label {
  pointer-events: none;
  font-family: <?php echo $font_default_sans; ?>;
  font-size:0.5em;
  fill: #000;
  text-transform: uppercase;
}

.force_node {
	cursor: pointer;
}

#force_legend {
	position: absolute;
	bottom: 50px;
	left: 50px;
	padding: 10px;
	display: none;
}

.force_legend_row {
	width: 100%;
	margin: 0px;
	padding: 0px;
	display: block;
}

#force_legend_quals {
	width: 20px;
	height: 20px;
	content: "";
	background: #6E9E75;
	display: inline-block;
}
#force_legend_respons {
	width: 20px;
	height: 20px;
	content: "";
	background: #EF8B46;
	display: inline-block;
}
#force_legend_descr {
	width: 20px;
	height: 20px;
	content: "";
	background: #577483;
	display: inline-block;
}

.force_legend_text {
	font-size: 1.0em;
	font-family: <?php echo $font_default_serif; ?>;
	cursor: default;
	display: inline-block;
	vertical-align: middle;
}

#force_text {
	z-index: 100;
	position: absolute;
	bottom: 15px;
	left: 15px;
	width: 30%;
	margin: 0px;
	padding: 20px;
	box-sizing: border-box;
	background-color: rgba(255,255,255,0.9);
	height: auto;
	opacity: 0;
	overflow-y: scroll;
}

span#textblock_pds {
	color: #577483;
}

span#textblock_quals {
	color: #6E9E75;
}
		
span#textblock_respons {
	color: #EF8B46;
}

</style>

<?php

// Get force layout data
$max_nodes = 20;
$maxRadius = 25;
$force_data = array();
$data_sources = array("responsibilities","qualifications","pds");
$data_clusters = array();
$cluster_counter = 1;

foreach($data_sources as $source) {
	$sql = "SELECT token_id, token_label, token_length, token_instance_ct, token_coverage_pct, token_source_type FROM tokens_reduced WHERE token_source_type = '$source' AND token_length = 1 ORDER BY token_instance_ct DESC LIMIT $max_nodes";
	$result = mysqli_query($con,$sql);
	$row_counter = 0;
	while($row = mysqli_fetch_assoc($result)) {
		$token_id = $row['token_id'];
		$token_label = $row['token_label'];
		$token_length = $row['token_length'];
		$token_instance_ct = $row['token_instance_ct'];
		$token_coverage_pct = (float)$row['token_coverage_pct'];
		$radius = $token_coverage_pct * $maxRadius;
		$token_data = array("label" => $token_label,
									   "token_id" => $token_id,
									   "token_length" => $token_length,
									   "instance_ct" => $token_instance_ct,
									   "coverage" => $token_coverage_pct,
									   "source" => $source,
									   "group" => $cluster_counter,
									   "radius" => $radius);
		$force_data[] = $token_data;
		
		$row_counter++;
	}
	$cluster_counter++;
	
	mysqli_free_result($result);
}

// Now get links data
$links_data = array();
$neighbors = array();
$max_weight = 0;
$link_distance_threshold = 3;
foreach($force_data as $i => $token_data) {
	$token_1 = $token_data["token_id"];
	for($j = $i + 1; $j < count($force_data); $j++) {
		$token_2 = $force_data[$j]["token_id"];
				
		// Now get links
		$sql = "SELECT weight FROM links_distance_reduced WHERE (source_id = '$token_1' AND target_id = '$token_2') OR (source_id = '$token_2' AND target_id = '$token_1') AND link_distance_threshold <= $link_distance_threshold";

		$result = mysqli_query($con,$sql);
		$subobj = mysqli_fetch_object($result);
		$link_weight = (int)$subobj->weight;
		if($link_weight > 0) {
			if($link_weight > $max_weight) {	
				$max_weight = $link_weight;
			}
			
			$links_data[] = array("source" => $i,
								  "source_id" => $token_1,
								  "target" => $j,
								  "target_id" => $token_2,
								  "rel_weight" => $link_weight,
								  "type" => "inner");
			$neighbors[$i . "," . $j] = $link_weight;
		}
		mysqli_free_result($result);
		
	}
	
}

foreach($force_data as $i => $token_data) {
	$token_1 = $token_data["label"];
	$token_1_id = $token_data["token_id"];
	for($j = $i + 1; $j < count($force_data); $j++) {
		$token_2 = $force_data[$j]["label"];
		$token_2_id = $force_data[$j]["token_id"];
		if($token_1 === $token_2) {
			$links_data[] = array("source" => $i,
								  "source_id" => $token_1_id,
								  "target" => $j,
								  "target_id" => $token_2_id,
								  "rel_weight" => 2,
								  "type" => "cross");
			$neighbors[$i . "," . $j] = $max_weight;
								  
		}
	}
}

$force_data_json = json_encode($force_data,true);
$links_data_json = json_encode($links_data, true);
?>


<div class="content_container">
	<div id="force_text">
		<div class="text_chunk text_selected">
			<div class="text_container text_hidden">We can also view the language used in these fellowship position descriptions in terms of semantic clusters.</div>
		</div>
		<div class="text_chunk">
			<div class="text_container text_hidden">Here, word tokens are mapped according to their relatedness measured by the distance between words. The <span id="textblock_pds">blue circles</span> represent words from fellowship <span id="textblock_pds">descriptions</span>, <span id="textblock_respons">orange circles</span> represent fellowship <span id="textblock_respons">responsibilities</span> provided in position descriptions, and <span id="textblock_quals">green circles</span> represent <span id="textblock_quals">qualifications</span> listed in these descriptions.</div>
		</div>
		<div class="text_chunk">
			<div class="text_container text_hidden">Two words are connected in this network if they appear near each other in their given source text. The closer two words are connected, the more frequently they appear close to each other in the texts.</div>
		</div>
		<div class="text_chunk">
			<div class="text_container text_hidden">CLIR postdoctoral fellowships are intended to provide early-career academics excellent opportunities to cultivate and apply their skills in new ways. Supervisors and fellows alike agree that the signature of a successful fellowship includes evidence of deepened sophistication in many applied realms, including <i>collaboration</i>, <i>project management skills</i>, <i>creativity in problem-solving</i>, and <i>discipline-specific knowledge</i> of research practices that can help <i>bridge communication</i> between faculty and librarians.
			</div>
		</div>
		<div class="text_chunk">
			<div class="text_container text_hidden">By examining clusters in this semantic network, we can see ways in which the importance of these critical skills is incorporated into CLIR fellowships. For example, in the <span id="textblock_quals">qualifications</span> cluster, we can see tight connections around the word <b>communication</b>, highlighted at left.
			</div>
		</div>
		<div class="text_chunk">
			<div class="text_container text_hidden">These semantic connections include words like <b>discipline</b>, <b>science</b>, <b>research</b>, and of course <b>data</b>, suggesting the importance of developing communication skills in such kinds of applied settings.
			</div>
		</div>
		<div class="text_chunk">
			<div class="text_container text_hidden">While words are clustered here according to their source text, we also see many high-frequency words that are shared across these sources, such as "data" and "research." Hover over individual word nodes to explore more semantic connections. Use your trackpad or double-click on words to zoom in or out &ndash; you can also move the network around by clicking and dragging word nodes.
				<div class="viz_inner_restart" id="force_restart">Start Over</div>	
			</div>
		</div>

		<div class="viz_inner_continue_container">
			<div class="viz_inner_continue" id="force_continue">Continue &rarr;</div>
		</div>	
	</div>
	
	<div id="force_legend">
		<div class="force_legend_row">
			<div id="force_legend_quals"></div>
			<div class="force_legend_text">Qualifications</div>
		</div>
		<div class="force_legend_row">
			<div id="force_legend_respons"></div>
			<div class="force_legend_text">Responsibilities</div>
		</div>
		<div class="force_legend_row">
			<div id="force_legend_descr"></div>
			<div class="force_legend_text">Descriptions</div>
		</div>
	</div>

</div>
<script>

	force_data = <?php echo $force_data_json; ?>;
	links_data = <?php echo $links_data_json; ?>;
	force_max_weight = <?php echo $max_weight; ?>;
	force_linkedByIndex = <?php echo json_encode($neighbors,true); ?>;

	//////////////////////////////////////////////////////////
	// Define functions /////////////////////////////////////
	////////////////////////////////////////////////////////

	function redraw(this_translate,this_scale) {
		if(this_translate) {
			var translate = this_translate;
		} else {
			var translate = d3.event.translate;
		}
		
		if(this_scale) {
			var scale = this_scale;
		} else {
			var scale = d3.event.scale;
		}
		
		svg_force.attr("transform","translate(" + translate + ")" + " scale(" + scale + ")");
		force_labels.attr("font-size",(1/scale) * 100 + "%");
	}	

	function neighboring(a, b) {
		if(force_linkedByIndex[a.index + "," + b.index] || force_linkedByIndex[b.index + "," + a.index]) {
			return true;
		}
	}

	function get_link_color(a, b) {
		if(force_linkedByIndex[a.index + "," + b.index]) {
			var weight = force_linkedByIndex[a.index + "," + b.index];
		} else if(force_linkedByIndex[b.index + "," + a.index]) {
			var weight = force_linkedByIndex[b.index + "," + a.index];			
		}

		return force_link_color_scale(weight);
	
	}

	function force_group_color(group) {
		switch(group) {
			case "qualifications":
				return "#6E9E75"; // green
				break;
			case "pds":
				return "#577483"; // blue
				break;
			case "responsibilities":
				return "#EF8B46"; // orange
				break;
		}
	}

	d3.select("#force_continue").on("click", function() {
		if(typeof force_transition_done === 'undefined') {
			force_transition_done = 1;
		}
		if(force_keyPress < force_transitions.length && force_transition_done == 1) {
			force_transition_done = 0;			
			svg_force.call(force_transitions[force_keyPress]);
		}
	});
	
	function cluster(alpha) {
	  return function(d) {
		var cluster = force_clusters[d.group];
		if (cluster.token_id === d.token_id) {
			switch(d.group) {
				case 1:
					cluster.x = 250;
					cluster.y = 250;
					break;
				case 2:
					cluster.x = force_width/2;
					cluster.y = force_height/2
					break;
				case 3:
					cluster.x = force_width-250;
					cluster.y = force_height-250;
					break;
			}
			return;
		}

		var x = d.x - cluster.x,
			y = d.y - cluster.y,
			l = Math.sqrt(x * x + y * y),
			r = d.radius + cluster.radius;

		if (l != r) {
		  l = (l - r) / l * alpha;
		  d.x -= x *= l;
		  d.y -= y *= l;
		  cluster.x += x;
		  cluster.y += y;
		}
	  };
	}

	// Resolves collisions between d and all other circles.
	function collide(alpha) {
	  var quadtree = d3.geom.quadtree(force_data);
	  return function(d) {
		var r = d.radius + force_maxRadius + Math.max(force_padding, force_clusterPadding),
			nx1 = d.x - r,
			nx2 = d.x + r,
			ny1 = d.y - r,
			ny2 = d.y + r;
		quadtree.visit(function(quad, x1, y1, x2, y2) {
		  if (quad.point && (quad.point !== d)) {
			var x = d.x - quad.point.x,
				y = d.y - quad.point.y,
				l = Math.sqrt(x * x + y * y),
				r = d.radius + quad.point.radius + (d.group === quad.point.group ? force_padding : force_clusterPadding);
			if (l < r) {
			  l = (l - r) / l * alpha;
			  d.x -= x *= l;
			  d.y -= y *= l;
			  quad.point.x += x;
			  quad.point.y += y;
			}
		  }
		  return x1 > nx2 || x2 < nx1 || y1 > ny2 || y2 < ny1;
		});
	  };
	}

	//////////////////////////////////////////////////////////
	// Define transitions ///////////////////////////////////
	////////////////////////////////////////////////////////
	
	force_transitions = [force_transition_0, force_transition_1, force_transition_2, force_transition_3, force_transition_4, force_transition_5, force_transition_6];
	
	function force_transition_0() {	
		$("#force_text").fadeTo(1000, 0.8);
		display_text("#force_text");
		$("#force_continue").css({"display":"block"}); 			
		force_transition_done = 1;
		force_keyPress++;
	}

	function force_transition_1() {	
		display_text("#force_text");	
		force_transition_done = 1;
		force_keyPress++;
	}

	function force_transition_2() {	
		$("#force_text").fadeOut(500, function() {
			display_text("#force_text");
			$("#force_text").css({"left":"auto","bottom":"auto","right": 15, "top": 15, "height": "auto"}).fadeIn(500, function() {
				$("#force_legend").fadeIn("fast");
				force_transition_done = 1;
				force_keyPress++;
			
			})
		});


	}
	
	function force_transition_3() {	
		display_text("#force_text");	
		force_transition_done = 1;
		force_keyPress++;
	}

	function force_transition_4() {	
		display_text("#force_text");	
		
		// Zoom in and highlight qualifications.communication node

		var highlight_node = force_node.filter(function(d) {
			return d.token_id === "token_0009441";
		}).datum();
		highlightNode(highlight_node,true);
		force_freeze_behavior = 1;
		force_transition_done = 1;
		force_keyPress++;
	}

	function force_transition_5() {	
		display_text("#force_text");	
		force_transition_done = 1;
		force_keyPress++;
	}

	function force_transition_6() {	
		display_text("#force_text");			
		$("#force_continue").css({"display":"none"}); 
		$("#force_restart")
			.click(function() { force_restart(); });			
		force_transition_done = 1;     		
		force_freeze_behavior = 0;
		frame_controller("unfreeze");
		$("#arrow_next").html("Conclude");
	}


	function highlightNode(d,focus) {
		if(force_freeze_behavior == 0) {
			force_links.attr("opacity",0.2);
				force_links.attr("opacity",0.2);
				force_links.filter(function(l) {
					return (d === l.source || d === l.target);
				}).attr("opacity",1)
				.attr("stroke-width",2)
				.attr("stroke","#FFCC33");

				force_node.attr("opacity",0.2);
				force_node.filter(function(n) {
						if(n === d || neighboring(n,d)) {
							return true;
						} 
					}).attr("opacity",1)
					.moveToFront()
					.selectAll("circle")
					.attr("fill", function(n) {
						if(n === d) {
							return "#FFCC33";
						} else {
							return get_link_color(n,d);
						}
					});		
		
			if(focus) {
				t_x = d3.transform(svg_force.attr("transform")).translate[0];
				t_y = d3.transform(svg_force.attr("transform")).translate[1];
				cx = d.x;
				cy = d.y;
				dx = force_width/3 - cx;
				dy = force_height/3 - cy ;
				var translate = "translate(" + dx + "," + dy + ")";
				var scale_factor = 1.3;
				var scale = "scale(" + scale_factor + ")";
				svg_force.transition()
						.duration(700)
						.attr("transform",scale + translate);
				force_labels.attr("font-size",(1/scale_factor) * 100 + "%");				
			}
		}
	}
	
	function unhighlightNode() {
		if(force_freeze_behavior == 0) {
			force_node.attr("opacity",1);
			force_circle.attr("fill", function(d) { return force_group_color(d.source); });
			force_links.attr("opacity",1)
				.attr("stroke-width",1)
				.attr("stroke",function(l) {
					if(l.type === "inner") {
						return "#CCCCCC";
					} else if(l.type === "cross") {
						return "#FFCC33";
					}
				});
		}
	}
		
	//////////////////////////////////////////////////////////
	// Define global variables //////////////////////////////
	////////////////////////////////////////////////////////


	force_link_color_scale = d3.scale.linear()
		.domain([1,force_max_weight])
		.range(["#CECECE","#FFCC33"]);
	
	force_clusters = new Array(4);

	var force_width = $("#force_static_div .content_container").innerWidth(),
		force_height = $(window).innerHeight();

	force_data.forEach(function(f) {
		var group = f.group;
		if(!force_clusters[group] || f.radius > force_clusters[group]["radius"]) {
			force_clusters[group] = f;
		}
	});

	var force_n = force_data.length;
	var force_m = 4;
	var force_padding = 1.5,
	force_clusterPadding = 6,
	force_maxRadius = 25;
	
	var force_freeze_behavior = 0;

	
	//////////////////////////////////////////////////////////
	// Initialize force layout //////////////////////////////
	////////////////////////////////////////////////////////

	var force = d3.layout.force()
		.size([force_width-200, force_height-200])
		.nodes(force_data)
		.links(links_data)
		.linkDistance(function(d) {
			return force_max_weight/Number(d.rel_weight)*4;
		})
		.gravity(0.03)
		.charge(-120)
		.start();


	var svg_force = d3.select("#force_static_div")
		.select(".content_container").append("svg")
		.attr("width", force_width)
		.attr("height", force_height)
		.attr("pointer-events", "all")
		.append('svg:g')
		.call(d3.behavior.zoom().on("zoom", redraw))
		.append('svg:g');

	var force_links = svg_force.selectAll(".force_link")
		.data(links_data)
		.enter().append("line")
		.attr("class","force_link")
		.attr("stroke",function(l) {
			if(l.type === "inner") {
				return "#CCCCCC";
			} else if(l.type === "cross") {
				return "#FFCC33";
			}
		});

	var force_node = svg_force.selectAll(".force_node")
		.data(force_data)
		.enter().append("g")
		.attr("class","force_node")
		.call(force.drag);

	var force_circle = force_node.append("circle")
		.attr("r", function(d) { return d.radius; })
		.attr("cx", function(d) { return d.cx; })
		.attr("cy", function(d) { return d.cy; })
		.attr("fill", function(d) { return force_group_color(d.source); });


	force_labels = force_node.append("text")
		.attr("text-anchor","middle")
		.attr("class","force_node_label")
		.attr("dy",3)
		.text(function(d) { return d.label });

	force_node.on("mouseover", function(d) { highlightNode(d);})
		.on("mouseout", function() { unhighlightNode(); });

	function force_restart() {
		redraw("0,0",1);
		frame_controller("freeze");
		restart_text("#force_text");
		force_freeze_behavior = 0;
		unhighlightNode();
		$("#force_legend").fadeOut("fast");
		$("#force_text").fadeTo(100, 0, function() {
			$("#force_text").css({"left":"15px","bottom":"15px","right": "auto", "top": "auto", "height": "auto"});
		});
		force_keyPress = 0;
		svg_force.call(force_transitions[force_keyPress]);
	}


	// Call first transition
	force_transition_done = 0;
	var force_keyPress = 0;		
	svg_force.call(force_transitions[force_keyPress]);

	force.on("tick", function(e) {	
		force_links.attr('x1', function(d) { return d.source.x; })
			.attr('y1', function(d) { return d.source.y; })
			.attr('x2', function(d) { return d.target.x; })
			.attr('y2', function(d) { return d.target.y; });
		force_node
			.each(cluster(5 * e.alpha * e.alpha))
			.each(collide(.5))
			.attr("transform", function(d) { return "translate(" + d.x + "," + d.y +")"; });
	});

</script>