<?php

$cacheDirName = 'cache';
$numFilesProcessed = 0;

// Loop over all sub directories of "cache".
if($outerDirHandle = opendir($cacheDirName)) {
	while(false !== ($innerDirName = readdir($outerDirHandle))){
		if($innerDirName != '.' && $innerDirName != '..' && is_dir($cacheDirName.'/'.$innerDirName)){
			
			// In each subdirectory, loop over all files (pages).
			if($innerDirHandle = opendir($cacheDirName.'/'.$innerDirName)) {
				while(false !== ($fileName = readdir($innerDirHandle))){
					if($fileName != '.' && $fileName != '..' && !is_dir($cacheDirName.'/'.$innerDirName.'/'.$fileName)){
						
						// Process the page.
						++$numFilesProcessed;
						handlePage($fileName, gzinflate(file_get_contents($cacheDirName.'/'.$innerDirName.'/'.$fileName)));
					}
				}
				closedir($innerDirHandle);
			}
		}
	}
	closedir($outerDirHandle);
}


function handlePage($domain, $pageContent){
	
	global $numFilesProcessed;

	// We only bother to look at the first comment of the page.
	$firstComment = extractFirstComment($pageContent);
	
	if($firstComment && isAsciiArt($firstComment)){

		// Log the comment and the domain name to a file.
		$domainAndArt = "\n\n\n".$domain."\n\n".$firstComment;
		file_put_contents('ascii_art.txt', $domainAndArt, FILE_APPEND);
		print("\n".$domainAndArt);
		print("\n\n".'processed '.$numFilesProcessed.' files');
	}
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

	$numLines = count(explode("\n", $comment));

	// Must be at least 5 lines.
	if($numLines < 5)
		return false;

	// Must be less than a page.
	if($numLines > 40)
		return false;

	// Try to block commented-out HTML. Roughly sorted by frequency of occurence as anecdotally spotted in the wild.
	foreach(array(

		// Head element stuff
		'<style',
		'<meta',
		'<![endif]',
		'[if IE',
		'[if lt IE ',
		'[if lte IE ',
		'[if gte IE',

		// Some end-tags
		'</td>',
		'</tr>',
		'</script>',
		'</option>',
		'</div>',
		'</a>',
		'</li>',
		'</ul>',
		'</object>',
		'</p>',
		'</form>',
		'</body>',

		// Some CMS signatures
		'TYPO3',
		'START DEBUG OUTPUT',
		'generated',
		'XT-Commerce',
		'TYPOlight',
		'Contao Open Source CMS',
		'W3 Total Cache',
		'Free CSS Templates',
		'DYNAMIC PAGE-SPECIFIC META TAGS WILL BE PLACED HERE',
		'Unfortunately, Microsoft has added a clever new',
		
		// Misc. garbage
		'<rdf:RDF',
		'src="',
		'Exception]:',
		'DoubleClick',
	) as $codeFragment)
		if(strpos($comment, $codeFragment) !== false)
			return false;
	
	return true;	
}