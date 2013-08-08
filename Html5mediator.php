<?php

if ( !defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['html5mediator'][] = array(
	'path' => __FILE__,
	'name' => 'Html5mediator',
	'description' => 'a simple hook for html5media',
	'author' => 'Seung Park'
 	);

/* Register the registration function */
$wgHooks['ParserFirstCallInit'][] = 'wfParserHook';

function wfParserHook( $parser )
{
	$parser->setHook( 'html5media' , 'wfParserHookParse' );
	return true;
}

function wfParserHookParse( $data, $params, $parser, $frame )
{
	global $wgContLang;

	// escape from XSS vulnerabilities
	$params = htmlspecialchars( print_r( $params, true ) );
	$data = htmlspecialchars( $data );

	/*
	 * This block of code is borrowed from the fabulous
	 * MediawikiPlayer by Swiftlytilting.  It converts
	 * MediaWiki "File:" tags into fully-valid URLs.
	 */

	// load international name of File namespace
	$namespaceNames = $wgContLang->getNamespaces();
	$fileNS = strtolower($namespaceNames[NS_FILE]);
	$ns = strtolower(substr($data, 0, 5));

	// check to see if a file specified
	if ($ns == 'file:' || $ns == ($fileNS . ':'))
	{
		$image = wfFindFile(substr($data, 5));
		if ($image)
		{
			$data = $image->getFullURL();
		}
		else
		{
			return 'Html5mediator: error loading file:' . Xml::encodeJsVar(substr($data, 5));
		}
	}

	/* End borrowed code */

	// Perform validation on the purported URL
	if (!filter_var($data, FILTER_VALIDATE_URL)) return 'Html5mediator: not a valid URL';

	// Get the file extension -- first check for a 3-char extension (mp3, mp4), then 4-char (webm)
	if (substr($data, -4, 1) == ".") $ext = substr($data, -3);
	else if (substr($data, -5, 1) == ".") $ext = substr($data, -4);

	// Write out the actual HTML
	$code = "<script src=\"http://api.html5media.info/1.1.5/html5media.min.js\"></script>";

	switch ($ext)
	{
		// video file extensions
		case "mp4":
		case "webm":
		case "mov":
		case "ogv":
			$code = $code . "<video src=\"" . $data . "\" controls preload></video>";
			break;

		// audio file extensions
		case "mp3":
		case "ogg":
			$code = $code . "<audio src=\"" . $data . "\" controls preload></audio>";
			break;

		// unrecognized file extensions
		default:
			return "Html5mediator: file extension not recognized";
	}

	return $code;
}