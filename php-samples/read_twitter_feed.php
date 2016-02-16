<?php

// Read twitter feed and write contents to XML file
$twitter = $_POST['twitter'];

$xml = simplexml_load_file("http://search.twitter.com/search.atom?q=%23veikkausliiga");

for($i=0; $i<3; $i++) {
	$publishedMod = str_replace("T", " ", $xml->entry[$i]->published);
	$publishedMod = str_replace("Z", "", $publishedMod);
	$published[] = $publishedMod;

	$authorName = explode("(", $xml->entry[$i]->author->name);
	$authorNameLen = strlen($authorName[1]) - 1;
	$authorString = substr($authorName[1], 0, $authorNameLen);
	$authorString = str_replace("Ã¤", "ä", $authorString);
	$authorString = str_replace("Ã¶", "ö", $authorString);
	$author[] = $authorString;

	$twitterName[] = trim($authorName[0]);

	$titleString = trim($xml->entry[$i]->title);
	$titleString = str_replace("Ã¤", "ä", $titleString);
	$titleString = str_replace("Ã¶", "ö", $titleString);
	$link = explode("http://", $titleString);
	if($link[1]) {
		$linkSeparated = explode(" ", $link[1]);
		if($linkSeparated[0]) {
			$linkString[] = "http://" . $linkSeparated[0];
		} else {
			$linkString[] = "http://" . $link[1];
		}
	} else {
		$linkString[] = "";
	}
	
	if($link[0]) {
		$title[] = $link[0];
	} else {
		$title[] = $titleString;
	}

	$pic[] = $xml->entry[$i]->link[1]['href'];
}


// create a new XML document but only if we have some data
if($author[0]) {
	$doc = new DomDocument('1.0', 'iso-8859-1');

	// create root node
	$twitterXML = $doc->createElement('twitter');
	$twitterXML = $doc->appendChild($twitterXML);

	for($j=0; $j<3; $j++) {

		$itemXML = $doc->createElement('entry');
		$itemXML = $twitterXML->appendChild($itemXML);

		// add nodes for items	
		$childAuthor = $doc->createElement('author');
		$childAuthor = $itemXML->appendChild($childAuthor);
		
		$childAuthorName = $doc->createElement('authorName');
		$childAuthorName = $itemXML->appendChild($childAuthorName);
		
		$childTitle = $doc->createElement('title');
		$childTitle = $itemXML->appendChild($childTitle);
		
		$childPic = $doc->createElement('pic');
		$childPic = $itemXML->appendChild($childPic);
		
		$childLink = $doc->createElement('link');
		$childLink = $itemXML->appendChild($childLink);
		
		// add content
		$name = $author[$j];
		$value = $doc->createTextNode('' . $name . '');
		$value = $childAuthor->appendChild($value);
		
		$twitterUser = $twitterName[$j];
		$value = $doc->createTextNode('' . $twitterUser . '');
		$value = $childAuthorName->appendChild($value);
		
		$content = $title[$j];
		$value = $doc->createTextNode('' . $content . '');
		$value = $childTitle->appendChild($value);
		
		$picture = $pic[$j];
		$value = $doc->createTextNode('' . $picture . '');
		$value = $childPic->appendChild($value);
		
		$link = $linkString[$j];
		$value = $doc->createTextNode('' . $link . '');
		$value = $childLink->appendChild($value);

	}

	// get completed xml document
	$xml_string = $doc->saveXML();

	// save xml to file
	if (!$xmlfile = fopen('XML-Twitter-Veikkausliiga.xml', 'wb')) {
		print('XML-tiedoston avaus ei onnistunut.');
	}

	if (fwrite($xmlfile, $xml_string) === FALSE) {
		print('XML-tiedostoon ei voinut kirjoittaa.');
	}

	// remove the last xml line
	unset($child);
}

?> 