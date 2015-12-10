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

mysql-token-links.php

Created: 2015-10-31
Modified: 2015-11-05
Steven Braun

This script calculates links between tokens as a measure of token distance,
where token distance is defined as the number of words between two tokens of interest.

This distance is defined by token index such that token adjacencies have a distance of ONE, 
NOT ZERO.

For example, in the sentence

	"Data management is an important part of research"
	
the token distance between "data" and "management" is 1, while the distance between 
"data" and "research" is 7.

/////////////////////////////////////////////////////////////////////////////////////// */

// Initialize some global variables
include("inc/default-config.php");

// Database connection; parameters defined globally in default_config.php
$con = mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);

// Define token exceptions (stopwords) -- words that should not be included in analysis
// Taken from http://www.ranks.nl/stopwords/ (Default English stopwords list)
$stopwords_filename = "inc/stopwords.txt";
$stopwords_file = fopen($stopwords_filename,"r");
$stopwords = fread($stopwords_file,filesize($stopwords_filename));

// Explode list of stopwords into an array
$token_exceptions = explode("\n",$stopwords);

// Define a special list of exceptions based on this set of data -- 
// For example, words like "university" and "Ph.D." should be ignored for token analyses
$special_exceptions = array("university","phd","ph.d.","will");
$token_exceptions = array_merge($token_exceptions,$special_exceptions);

// Define minimum and maximum token search lengths for below
$min_token_length = 1;
$max_token_length = 3;

// Specify what we're searching; 'qualifications' (position qualifications), 'responsibilities' (position responsibilities), or 'pds' (position description main body text)
$sources = array("pds","qualifications","responsibilities");

// Truncate TABLE links_distance, if desired 
// (for example, when redoing analyses)
mysqli_query($con,"TRUNCATE TABLE links_distance");

// Now start looping through text sources
foreach($sources as $search_content) {

	// This will be an array of the most frequent tokens calculated for this data source
	$tokens_array = array();
	print "Calculating token links for source " . $search_content . "...\n";
	sleep(1);
	
	// NOTE: here, we're limiting our measure of distances between tokens of LENGTH 1!
	// This EXCLUDES any compound tokens (e.g., "data management") with the assumption that 
	// compounds will be detected by token distances of 1. 
	// Also, we're grabbing the MOST FREQUENT tokens calculated via mysql-token-analysis.php, 
	// limited in count by $global_max_token_count
	$sql = "SELECT token_id, token_label FROM tokens WHERE token_source_type='$search_content' AND token_length = 1 ORDER BY token_instance_ct DESC LIMIT $global_max_token_count";
	$result = mysqli_query($con,$sql);

	while($row = mysqli_fetch_assoc($result)) {
		$token_id = $row['token_id'];
		$token_label = $row['token_label'];
		$tokens_array[$token_label] = array("token_id" => $token_id,
								"token_label" => $token_label);

	}
	
	mysqli_free_result($result);
	
	print "Looping through texts...\n";
	sleep(1);

	// Now split search based on content variable defined above;
	// Note that we're only pulling text from position descriptions for
	// DATA CURATION track fellowships, indicated by position_descriptions.pd_dc = 1
	// and for position descriptions with pd_startyear >= 2012

	// $sql_qualifier is defined in default-config.php
	$sql_qualifier = $global_pd_sql_qualifier;
	
	switch($search_content) {
		case "pds":
			// Specify what data fields to query
			$text_sql = "SELECT pd_id, pd_description, pd_startyear FROM position_descriptions " . $sql_qualifier;

			// Specify what data field to use for applied analyses
			$search_field = "pd_description";

			// Specify the name of the field used to indicate the ID of the entity (data object) being analysed
			$entity_id_pointer = "pd_id";
			
			// Specify a semantic descriptor of data object, for tracking purposes below
			$counter_label = "PD";
			break;
		case "qualifications":
			// Specify what data fields to query
			$text_sql = "SELECT pd_id, pd_qualifications_min, pd_qualifications_preferred, pd_startyear FROM position_descriptions " . $sql_qualifier;

			// Specify what data field to use for applied analyses
			$search_field = "pd_qualifications";

			// Specify the name of the field used to indicate the ID of the entity (data object) being analysed
			$entity_id_pointer = "pd_id";

			// Specify a semantic descriptor of data object, for tracking purposes below
			$counter_label = "PD QUALIFICATIONS";
			break;
		case "responsibilities":
			// Specify what data fields to query
			$text_sql = "SELECT pd_id, pd_responsibilities, pd_startyear FROM position_descriptions " . $sql_qualifier;

			// Specify what data field to use for applied analyses
			$search_field = "pd_responsibilities";

			// Specify the name of the field used to indicate the ID of the entity (data object) being analysed
			$entity_id_pointer = "pd_id";

			// Specify a semantic descriptor of data object, for tracking purposes below
			$counter_label = "PD RESPONSIBILITIES";
			break;
	}	
	
	
	// Execute query defined above
	$result = mysqli_query($con,$text_sql);

	// Loop through returned results
	while($row = mysqli_fetch_assoc($result)) {
	
		if($search_content === "qualifications") {
	
			// If searching qualifications, then concatenate mininimum and preferred qualifications fields 
			// to analyze chunked text
			$search_text = $row["pd_qualifications_min"] . " " . $row["pd_qualifications_preferred"];

		} else {

			$search_text = $row[$search_field];

		}
		
		// Grab the entity ID for the returned result (position description)
		$entity_id = $row[$entity_id_pointer];

		
		//////////////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////////////
		/*
		
		Note: here we're going to be performing essentially the same analyses completed in
		mysql-token-analysis.php; this time, though, we're just searching for and counting 
		distances between KNOWN tokens rather than defining new ones

		*/
		////////////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////////////

		// Break out search text into sentences (split by period)
		// But first, take out "Ph.D." to avoid weird period splitting results
		$search_text = str_ireplace(array("Ph.D.","Ph.D"),"PhD",$search_text);

		// Convert any non-stopping line endings (carriage returns) to periods so that lines can be 
		// split properly below
		$search_text = str_replace("\n",". ",$search_text);

		// Now split text by periods
		// Note here we're splitting by periods followed by at least 1 space so we don't
		// accidentally split by other unexpected uses of periods within words	
		$search_text = explode(". ",$search_text);
		
		// Now loop through sentences
		foreach($search_text as $sentence => $sentence_text) {

			if($sentence_text === '' || is_null($sentence_text)) {
				continue;
			}
	
			// Break out unnecessary punctuation in the text to reduce tokens to individual words
			$search_text = preg_replace('~[(),:;\'?!/&]~','',$sentence_text);
	
			// Special punctuation exception: hyphens (-) are converted to spaces
			$search_text = str_ireplace("-"," ",$search_text);		

			// Split the search text into space-delimited tokens (words)
			$original_tokens = preg_split('/\s+/',$search_text);

			// Delete all token exceptions from search text and transform all
			// allowed tokens to lowercase

			// $cleaned_tokens is an array of CLEANED tokens resulting from process below
			$cleaned_tokens = array();	

			// Note: in $cleaned_tokens, we use $i to record the original token index
			// of each word in the sentence from which it has been retrieved
			foreach($original_tokens as $i => $word) {

				// Transform token to lowercase and trim
				$check_word = strtolower(trim($word));
	
				// If transformed token is not in list of exceptions AND
				// if non-null, then add to $cleaned_tokens array
				if($check_word == '') {
					continue;
				} else if(!in_array($check_word,$token_exceptions)) {
					$cleaned_tokens[$i] = $check_word;
				} else {
					// IF token IS in list of stop words, record a spacer in the $cleaned_tokens array
					$cleaned_tokens[$i] = encodeStopWord($check_word);
				}
			}

			// Get total number of tokens
			$total_tokens = count($cleaned_tokens);

			// Now scan through array of tokens at different token lengths
			foreach($cleaned_tokens as $token_number => $token_1) {
			
				// If current token from original text source is defined in $tokens_array 
				// (i.e., the top 200 high-frequency tokens retrieved from TABLE tokens), 
				// then proceed with analysis
				if(array_key_exists($token_1,$tokens_array)) {
					$token_1_id = $tokens_array[$token_1]["token_id"];
					
					// Now calculate distance between current token and all other tokens in sentence
					for($j = $token_number + 1; $j < count($cleaned_tokens); $j++) {
						$token_2 = $cleaned_tokens[$j];
						
						// Only do distance calculation if comparison token is ALSO in $tokens_array (see above)
						if(array_key_exists($token_2,$tokens_array)) {
							$token_2_id = $tokens_array[$token_2]["token_id"];
							
							// Calculate the distance between the tokens based on their index values
							$distance = $j - $token_number;
							
							// Check TABLE links_distance for existing record of distance between these 2 tokens;
							// If a record exists, "source_id" may be $token_1_id and "target_id" may be $token_2_id or vice versa --
							// A unique record already exists if there is a row that matches both token IDs with the token distance defined by $distance above
							$check_for_link = "SELECT token_link_row_index FROM links_distance WHERE (source_id = '$token_1_id' AND target_id = '$token_2_id') OR (source_id = '$token_2_id' AND target_id = '$token_1_id') AND link_distance = $distance";
							$check_result = mysqli_query($con,$check_for_link);
							$results_count = mysqli_num_rows($check_result);

							// If there are no records in TABLE links_distance for combination of $token_1_id, $token_2_id, and $distance above, 
							// then add record to table
							if($results_count == 0) {
								$update_sql = "INSERT INTO links_distance (source_id,target_id,link_distance,weight,link_source_type) " .
										  "VALUES ('$token_1_id','$token_2_id',$distance,1,'$search_content')";
								$output = "\tADDED LINK\t" . $token_1 . "\t" . $token_2 . "\t" . $distance . "\n";
								
							} else {
							
								// Otherwise, if record already exists, then increment link weight by 1 in table
								$check_obj = mysqli_fetch_object($check_result);
								$link_id = $check_obj->token_link_row_index;
								$update_sql = "UPDATE links_distance SET weight = weight + 1 WHERE token_link_row_index = $link_id";
								$output = "\tUPDATED LINK\t" . $token_1 . "\t" . $token_2 . "\n";

							}
							
							if(!mysqli_query($con,$update_sql)) {
								die(mysqli_error($con));
							} else {
								print $output;
							}
							
							mysqli_free_result($check_result);
						} // END if(array_key_exists($token_2))
					} // END for($j = $token_number + 1...)
				} // END if(array_key_exists($token_1)						  				
			} // End foreach($cleaned_tokens)
		} // End foreach($search_text)
	} // End while($row)

	mysqli_free_result($result);
	
} // end foreach($search_content)

print "Total process complete.\n";

?>