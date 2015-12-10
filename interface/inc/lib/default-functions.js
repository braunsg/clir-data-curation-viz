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

default-functions.js

Created: 2015-11-16
Modified: 2015-12-10
Steven Braun

This file defines all site-wide default javascript functions used to control visualization 
animations and navigation.

/////////////////////////////////////////////////////////////////////////////////////// */

// This function handles style changes to the top-level navigation buttons; 
// in the middle of a visualization, the navigation pane is gray, but when a given visualization 
// frame is complete, this pane returns to its normal yellow/gold color
function frame_controller(action) {
	if(action === "freeze") {
		$("#arrow_next").removeClass("arrow_fadein").addClass("arrow_fadeout");
		$("#arrow_next").html("Navigation");
	} else if(action === "unfreeze") {
		$("#arrow_next").removeClass("arrow_fadeout").addClass("arrow_fadein");
		$("#arrow_next").html("Next Viz");
	}
}

// This function controls the display of new text blocks within each visualization
function display_text(text_container) {	
	var text_chunk = $(text_container + " .text_chunk.text_selected");
	if(text_chunk.children(".text_hidden").length == 0) {
		text_chunk.next().addClass("text_selected");
		text_chunk.removeClass("text_selected").css("display","none");
	}
	
	var element_to_display = $(text_container + " .text_chunk.text_selected .text_container").filter(function() {
		return $(this).css("display") === "none";
	}).first();
	
	element_to_display.removeClass("text_hidden").fadeTo(1000,1).animate({"display":"block"},1000);
	
}

// This function resets the sequence of text blocks to be displayed within a given 
// visualization when the user restarts a visualization from the beginning
function restart_text(text_container) {
	var text_chunk = $(text_container + " .text_chunk.text_selected");
	text_chunk.removeClass("text_selected");
	$(text_container + " .text_chunk").css("display","block");
	$(text_container + " .text_chunk").first().addClass("text_selected");
	$(text_container + " .text_chunk .text_container").addClass("text_hidden").css("display","none");
}

$(document).ready(function() {

	$("#nav_flyout_container").fadeTo(500, 1);
	$("body").css("overflow-y","auto");
	
	freeze_frames = 0;
	
	var frames = <?php echo json_encode($frames,true); ?>;
	var frames_index = [];

	/* Invert the frames array for dynamic loading */
	for(var key in frames) {
		var index = frames[key];
		var frame_offset = $("#" + key).offset().top;
		frames_index[index] = {"frame_id": key, "top": frame_offset};
	}	
	var content_increment = 1;
	var loopThrough = 0;
	
	/* D3 prototype function -- shifts svg elements to front of stack */	
	d3.selection.prototype.moveToFront = function() {
		return this.each(function(){
			this.parentNode.appendChild(this);
		});
	};

	$("#nav_flyout_container").on("mouseenter", function() {
		$("#nav_flyout_subcontainer").slideDown(300);
	}).on("mouseleave", function() {
		$("#nav_flyout_subcontainer").slideUp(300);
	});
	
	// This function handles sequential movement through the visualization frames
	function progressContent(content_increment) {
		
		if(content_increment <= frames_index.length - 1) {
			if(content_increment != 1 && content_increment != frames_index.length - 1) {
				$("#arrow_next").removeClass("arrow_fadein").addClass("arrow_fadeout").html("Navigation");
			}
			
			loopThrough = 0;
			$(".nav_item").removeClass("nav_selected").addClass("nav_deselected");
			$("#frame_selector_" + content_increment).removeClass("nav_deselected").addClass("nav_selected");
		
			var next_frame = frames_index[content_increment];
			$("html,body").animate(
				{scrollTop: next_frame["top"]}, "3000", function() {
					document.getElementById(next_frame["frame_id"]).focus();

				});
				
			if(content_increment == frames_index.length - 2 && loopThrough == 1) {
				// If we're returning to the second to last frame from the last frame,
				// bring back the NEXT arrow
				$("#arrow_next").html("Next Viz");
				
			}
			if(content_increment == frames_index.length - 1) {
				$("#arrow_next").removeClass("arrow_fadeout").addClass("arrow_fadein").html("Start Over");
				loopThrough = 1;
			}
		}	
	}

	/* Define click events to shift frames (visualizations);
	transitions within visualizations are handled in their respective files */
	$("#arrow_next").on("click", function() {
	
		if(loopThrough == 1) {
			// Restart all animations/visualizations
			window.fm_initialize(true);
			window.tk_initialize(true);
			window.force_restart();
			$("#arrow_next").removeClass("arrow_fadeout").addClass("arrow_fadein").html("Begin");
			content_increment = 1;
		} else {
			content_increment++;
		}		
		progressContent(content_increment);
	});
	
	$(".nav_item").on("click", function() {
		content_increment = Number($(this).prop("id").replace("frame_selector_",""));
		progressContent(content_increment);
		
	});
});