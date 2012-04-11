<?php

$dir = 1;



function handlePage($domain, $pageContent){

// Method A: Collect all comments.
//	$comments = extractComments($pageContent);
//	$asciiArts = array_filter($comments, 'isAsciiArt');
//
//	// Save found art.
//	if($asciiArts){
//		$domainAndArt = "\n\n\n".$domain."\n\n".implode("\n\n", $asciiArts);
//		file_put_contents('ascii_art.txt', $domainAndArt, FILE_APPEND);
//		print('<pre>'.htmlspecialchars($domainAndArt).'</pre>');
//	}

// Method B: Just pick the first comment on the page. (Probably where any ascii art will be placed.)
	$firstComment = extractFirstComment($pageContent);
	if($firstComment && isAsciiArt($firstComment)){
		$domainAndArt = "\n\n\n".$domain."\n\n".$firstComment;
		file_put_contents('ascii_art.txt', $domainAndArt, FILE_APPEND);
		print('<pre>'.htmlspecialchars($domainAndArt).'</pre>');
	}
}


function extractComments($pageContent){

	$tidy = tidy_parse_string($pageContent, array(), 'ascii');

	return tidyComments($tidy->root());
}


function tidyComments($tinyNode){

	// Found value.
	if($tinyNode->isComment())
		return array($tinyNode->value);
		

	// Recurse.
	$comments = array();
	if($tinyNode->hasChildren())
		foreach($tinyNode->child as $child)
			$comments = array_merge($comments, tidyComments($child));
	return $comments;

}


function extractFirstComment($pageContent){

	$tidy = tidy_parse_string($pageContent, array(), 'ascii');

	return firstTidyComment($tidy->root());
}


function firstTidyComment($tinyNode){

	// Found value.
	if($tinyNode->isComment())
		return $tinyNode->value;

	// Recurse.
	if($tinyNode->hasChildren())
		foreach($tinyNode->child as $child){
			$possiblyComent = firstTidyComment($child);
			if($possiblyComent)
				// Got one!
				return $possiblyComent;
		}

	// No comment found.
	return false;
}


function isAsciiArt($comment){

	$numChars = strlen($comment);

	// No huge chunks of text.
	if($numChars > 2000)
		return false;

	// Try to block commented-out HTML. Roughly sorted by frequency of occurence as anecdotally spotted in the wild.
	foreach(array(

		// Head element stuff.
		'<style',
		'<meta',
		'<![endif]',
		'[if IE',
		'[if lt IE ',
		'[if lte IE ',
		'[if gte IE',

		// Some end-tags.
		'</td>',
		'</tr>',
		'</script>',
		'</option>',
		'</div>',
		'</a>',
		'</li>',
		'</ul>',
		'</object>',

		// Some CMS signatures.
		'TYPO3',
		'START DEBUG OUTPUT',
		'generated',
		
		'<rdf:RDF',
		'src="',
//		'href="',
//		'bgcolor="',
//		'target="',
//		'javascript',
//		'function(',
//		'<tr',
//		'<td',
//		'<div',
	) as $codeFragment)
		if(strpos($comment, $codeFragment) !== false)
			return false;

	$numLines = count(explode("\n", $comment));

	// Must be at least 5 lines.
	if($numLines < 5)
		return false;

	// Must be less than a page.
	if($numLines > 40)
		return false;

	// Must have more than 3 consecutive of the same symbol.
	if(!preg_match('/(.)\1{3}/', $comment))
		return false;

//	$funnyChars = array('/', '\\', '|', '*', '+', '_', '(', ')', '#', '=', '´', '`', '\'', '^', '©', '@', '[', ']', '≈', '†', '»', '«', '.', ',');
//	$numFunny = $numChars - strlen(str_replace($funnyChars, array_fill(0, count($funnyChars), ''), $comment));
//	$numAlphaNumeric = preg_match_all('/[A-Za-z0-9]/', $comment, $matches);
//
//	// The ratio of "funny" symbols to alphanumerical should be 5.
//	if(!($numFunny > $numAlphaNumeric*5))
//		return false;
	
	return true;	
}