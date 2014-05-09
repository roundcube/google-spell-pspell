<?php

/**
 * Entry point for the Pspell-based Google Spell Check XML API service
 *
 * Based on the wrapper library from https://github.com/AlphawolfWMP/google-spell-pspell
 * and powered by the free Aspell and Pspell modules.
 *
 * Copyright (C) 2014, The Roundcube Dev Team
 *
 * @license GNU General Public License (GPL) 3.0 <http://www.gnu.org/copyleft/gpl.html>
 * @author: Thomas Bruederli <thomas@roundcube.net>
 */

require_once('spell-check-library.php');

// catch pspell errors (e.g. language not supported)
set_error_handler(function($errno, $errstr){
    if (strpos($errstr, 'pspell_') !== false) {
        spellerror(500, $errstr);
        exit;
    }
    return false;
}, (E_ERROR | E_WARNING));

/*
$postdata = <<<EOF
<?xml version="1.0" encoding="utf-8" ?>
<spellrequest textalreadyclipped="0" ignoredups="0" ignoredigits="1" ignoreallcaps="1">
<text>Ths is a tst</text>
</spellrequest>
EOF;
*/

$postdata = file_get_contents('php://input');

try {
    $request = new SimpleXMLElement($postdata);
}
catch (Exception $e) {
    spellerror(400, "Invalid request data");
    exit;
}

$lang = $_GET['lang'] ?: 'en';

$options = array(
    'lang'              => $lang,
    'maxSuggestions'    => 10,
    'customDict'        => 0,
    'charset'           => 'utf-8',
);
$text = strval($request->text);
if (!strlen($text)) $text = '*';  // avoid empty strings
$factory = new SpellChecker($options);
$spell = $factory->create($text);

if ($factory->errorLog()) {
    spellerror(500);
}
else {
    header('Content-Type: text/xml; charset=UTF-8');
    echo $spell->toXML();
}


function spellerror($code, $message)
{
    $http_status = $code == 400 ? 'Bad Request' : 'Internal Server Error';
    header("HTTP/1.0 $code $http_status");
    header('Content-Type: text/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
    echo '<spellresult error="'.$code.'"><errortext>' . htmlentities($message) . '</errortext></spellresult>';
}
