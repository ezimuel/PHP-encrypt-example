<?php
/**
 * Encrypt/decrypt file using Zend\Filter and Zend\Crypt\BlockCipher
 *
 * @author Enrico Zimuel (enrico@zimuel.it)
 * @copyright GNU General Public License 
 */
require 'vendor/autoload.php';

use Zend\Filter\Encrypt;
use Zend\Filter\Decrypt;

if ($argc != 4) {
    die("Usage: " . basename(__FILE__) . " [e|d] <file_to_encrypt/decrypt> <encryption_key>\n");
}

$action = strtolower($argv[1]);
$fileIn = $argv[2];
$key    = $argv[3];

if (!in_array($action, array('e', 'd'))) {
    die("The first parameter must be 'e' to encrypt or 'd' to decrypt.\n");
}

if (!file_exists($fileIn)) {
    die("The file $fileIn specified doesn't exist\n");
}
   
$filterConfig = array( 'adapter' => 'BlockCipher' );
if ($action === 'e') {
    $fileOut = $fileIn . '.enc';
    $filter  = new Encrypt($filterConfig);
} else {
    $fileOut = strstr($fileIn, '.enc', true);
    $filter  = new Decrypt($filterConfig);
}

$bufferSize = 1048576; // 1 MB
$filter->setKey($key);
     
$read  = @fopen($fileIn, "r");
$write = @fopen($fileOut, "w");

$delimiter = '$';
$buffer    = '';      
while (!feof($read)) {
    $data = fread($read, $bufferSize);
    if ($action === 'd') {
        $pos = strpos($data, $delimiter);
        if (false === $pos) {
            $buffer .= $data;
        } else {
            $buffer .= substr($data, 0, $pos);
            $result  = $filter->filter($buffer);
            $buffer  = substr($data, $pos + 1);  
            fwrite($write, $result);
        }
    } else {
        fwrite($write, $filter->filter($data) . $delimiter);
    }
}
if (!empty($buffer)) {
    fwrite($write, $filter->filter($buffer));
}
       
fclose($write);
fclose($read);
