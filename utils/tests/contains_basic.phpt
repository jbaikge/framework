--TEST--
Ensure String::contains() works.
--FILE--
<?php
require_once(dirname(__DIR__) . '/String.php');
use Core::Util::String;

$haystack = "Lorem ipsum dolor sit amet";
$good_needle = "Lorem";
$upper_needle = strtoupper($good_needle);
$bad_needle = "amot";

// Case-Sensitive Tests
$sensitive = array(
	'Haystack contains needle' => array($haystack, $good_needle),
	'Haystack does not contain uppercase needle' => array($haystack, strtoupper($good_needle)),
	'Haystack does not contain bad needle' => array($haystack, $bad_needle),
	'Blank haystack does not contain needle' => array('', $good_needle),
	'Haystack contains blank needle' => array($haystack, ''),
	'Blank haystack contains blank needle' => array('', ''),
	'Haystack equals needle' => array($haystack, $haystack),
	'Needle does not contain haystack' => array($good_needle, $haystack)
);

// Case-Insensitive Tests
$insensitive = array(
	'Haystack contains needle' => array($haystack, $good_needle),
	'Haystack contains uppercase needle' => array($haystack, $upper_needle),
	'Haystack does not contain bad needle' => array($haystack, $bad_needle),
	'Needle contains uppercase needle' => array($good_needle, $upper_needle)
);

echo "Case-Sensitive Tests\n";
foreach ($sensitive as $desc => $args) {
	echo "    $desc: ";
	var_dump(String::contains($args[0], $args[1]));
}
echo "Case-Insensitive Tests\n";
foreach ($insensitive as $desc => $args) {
	echo "    $desc: ";
	var_dump(String::contains($args[0], $args[1], true));
}
?>
--EXPECT--
Case-Sensitive Tests
    Haystack contains needle: bool(true)
    Haystack does not contain uppercase needle: bool(false)
    Haystack does not contain bad needle: bool(false)
    Blank haystack does not contain needle: bool(false)
    Haystack contains blank needle: bool(true)
    Blank haystack contains blank needle: bool(true)
    Haystack equals needle: bool(true)
    Needle does not contain haystack: bool(false)
Case-Insensitive Tests
    Haystack contains needle: bool(true)
    Haystack contains uppercase needle: bool(true)
    Haystack does not contain bad needle: bool(false)
    Needle contains uppercase needle: bool(true)
