--TEST--
Ensure String::startsWith() works
--FILE--
<?php
require_once(dirname(__DIR__) . '/String.php');
use Core::Util::String;

$str = 'Lorem ipsum';
$upper_str = strtoupper($str);
$good_letter = 'L';
$good_str = 'Lore';
$bad_letter = 'q';
$bad_str = 'ipsum';

$sensitive = array(
	"'$str' starts with '$good_letter'" => array($str, $good_letter),
	"'$str' starts with '$str'" => array($str, $good_str),
	"'$str' does not start with '$bad_letter'" => array($str, $bad_letter),
	"'$str' does not start with '$bad_str'" => array($str, $bad_str)
);
$insensitive = array(
	"'$str' starts with '$good_letter'" => array($str, $good_letter),
	"'$str' starts with '$str'" => array($str, $good_str),
	"'$str' does not start with '$bad_letter'" => array($str, $bad_letter),
	"'$str' does not start with '$bad_str'" => array($str, $bad_str),
	"'$upper_str' starts with '$good_letter'" => array($str, $good_letter),
	"'$upper_str' starts with '$str'" => array($str, $good_str),
	"'$upper_str' does not start with '$bad_letter'" => array($str, $bad_letter),
);

echo "Case-Sensitive Tests\n";
foreach ($sensitive as $desc => $args) {
	echo "    $desc: ";
	var_dump(String::startsWith($args[0], $args[1]));
}

echo "Case-Insensitive Tests\n";
foreach ($insensitive as $desc => $args) {
	echo "    $desc: ";
	var_dump(String::startsWith($args[0], $args[1], true));
}
?>
--EXPECT--
Case-Sensitive Tests
    'Lorem ipsum' starts with 'L': bool(true)
    'Lorem ipsum' starts with 'Lorem ipsum': bool(true)
    'Lorem ipsum' does not start with 'q': bool(false)
    'Lorem ipsum' does not start with 'ipsum': bool(false)
Case-Insensitive Tests
    'Lorem ipsum' starts with 'L': bool(true)
    'Lorem ipsum' starts with 'Lorem ipsum': bool(true)
    'Lorem ipsum' does not start with 'q': bool(false)
    'Lorem ipsum' does not start with 'ipsum': bool(false)
    'LOREM IPSUM' starts with 'L': bool(true)
    'LOREM IPSUM' starts with 'Lorem ipsum': bool(true)
    'LOREM IPSUM' does not start with 'q': bool(false)
