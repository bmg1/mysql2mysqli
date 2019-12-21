<?php
/**
 * Created by PhpStorm.
 * Filename: fixMysql.php
 * InProject: DMC
 * Descr: homepage for php project for client X
 * User: BMG.lv (c) 2019
 * Date: 21.12.2019
 * Time: 01:51
 */


ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(0);

//header("Content-Type: text/html; charset=UTF-8");
header("Content-Type: text/plain; charset=UTF-8");

define('IS_DEBUG', true);

$dir = realpath(__DIR__.DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

$allFunctionForIgnore = array(
	'mysql_one(', 'mysql_or_addnew(','mysql_count(',
);

$allFilesAsReplace = array();
$allFilesList = array();
$allFunctionSearchCount = array();
$allFunctionSearch = array();
$allFunctionReplace = array(
	'mysql_insert_id(' => 'mysqli_insert_id($db_link',
	'mysql_connect(' => '$db_link = mysqli_connect(',
	'mysql_select_db(' => 'mysqli_select_db($db_link, ',
	'mysql_query(' => 'mysqli_query($db_link, ',
	'mysql_fetch_array(' => 'mysqli_fetch_array($db_link, ',
	'mysql_real_escape_string(' => 'mysqli_real_escape_string($db_link, ',
	'mysql_set_charset(' => 'mysqli_set_charset($db_link, ',
	'mysql_fetch_assoc(' => 'mysqli_fetch_assoc(',
	'mysql_num_rows(' => 'mysqli_num_rows(',
	'mysql_fetch_row(' => 'mysqli_fetch_row(',
	'mysql_free_result(' => 'mysqli_free_result(',
	'mysql_escape_string(' => 'mysqli_real_escape_string($db_link, ',

	'mysql_error(' => 'mysqli_error($db_link, ',
	'mysql_fetch_object(' => 'mysqli_fetch_object(',
	'mysql_errno(' => 'mysqli_errno($db_link, ',
	'mysql_num_fields(' => 'mysqli_num_fields(',
	'mysql_affected_rows(' => 'mysqli_affected_rows($db_link, ',
	'mysql_fetch_field(' => 'mysqli_fetch_field(',
	'mysql_ping(' => 'mysqli_ping($db_link, ',
	'mysql_close(' => 'mysqli_close($db_link, ',

	/**
	 * todo@ find with query string and parametrs
	 */
	'mysql_unbuffered_query(' => 'mysqli_query($db_link, ', // as variants. Ideal is: mysqli_query($db_link, "SELECT Name FROM City", MYSQLI_USE_RESULT);
	'mysql_pconnect(' => '$db_link = mysqli_connect(\'p:\'.',
	'mysql_data_seek(' => 'mysqli_data_seek(',

);

echo $dir;

searchFilesMysql($dir);


if(IS_DEBUG) {
	echo 'DEBUG!!!';
	print_r($allFilesList);
	print_r($allFunctionSearch);
}else {
	echo 'WORK!!!';
	print_r($allFunctionSearchCount);
	echo '$allFilesAsReplace: ' . print_r($allFilesAsReplace, 1) . "\r\n\r\n";
}
echo 'END!';

function searchFilesMysql($startDir){
	global $allFilesList;
	$fileList = scandir($startDir);
	if(!empty($fileList)){
		foreach($fileList as $f){
			if($f == '.' || $f == '..' || $f == basename(__FILE__)) continue;

			$thisPath = $startDir . $f;
			if(is_dir($thisPath)){
				searchFilesMysql($thisPath. DIRECTORY_SEPARATOR);
			}
			if(is_file($thisPath) && strpos($f, '.php') !== false){
				$result = fixMysqlFunc($thisPath);
				if($result){ // is find!

				}
			}


		}
	}
	return array();
}


function fixMysqlFunc($file){

	if(!file_exists($file)) exit('ERROR! File not found: '.$file);

	$fileContent = file_get_contents($file);



	preg_match_all('/mysql_([a-z_]+)\(/', $fileContent, $matches);
//	preg_match_all('/mysql_([a-z_]+)\((?:[^\)]+)/', $fileContent, $matches);

	if(!empty($matches[0])){

//		echo $file;		echo "\r\n";		print_r($matches);

		addToArrayMysql($matches[0], $file);
		if(!IS_DEBUG) fixMysql2Mysqli($matches[0], $file);

//		echo "\r\n\r\n";

		return true;
	}
	return false;


}

function addToArrayMysql($array, $file){
	global $allFunctionSearch, $allFunctionReplace, $allFunctionForIgnore, $allFilesList, $allFunctionSearchCount;

	foreach ($array as $v){
		if(in_array($v, $allFunctionForIgnore)) continue;

		if(!isset($allFunctionSearchCount[$v])) $allFunctionSearchCount[$v] = 0;
		$allFunctionSearchCount[$v]++;

		if(IS_DEBUG){
			if(!in_array($v, array_keys($allFunctionSearch))){
				$allFunctionSearch[$v] = $v;
				$allFilesList[$file] = $file;
			}
		}else {
			if (!in_array($v, array_keys($allFunctionReplace))) {
				$allFunctionSearch[$v] = $v;
				$allFilesList[$file] = $file;
			}
		}
	}

}


function fixMysql2Mysqli($array, $file){
	global $allFunctionReplace, $allFilesAsReplace, $allFunctionForIgnore;

	if(!file_exists($file)) exit('ERROR! File not Exist: '.$file);

	copy($file, $file.'.BAK');
	$allFilesAsReplace[$file] = $file;

	$content = file_get_contents($file);
	foreach ($array as $v){
		if(in_array($v, array_keys($allFunctionReplace)) && !in_array($v, $allFunctionForIgnore)){
			$search = $v;
			$search = escapePregMatch($search);
			$replace = $allFunctionReplace[$v];
			$content = preg_replace('/'.$search.'/', $replace, $content);
		}
	}
//	echo $content;

		file_put_contents($file, $content);

}


function escapePregMatch($str){

	$search2replace = array(
		'('=>'\('
	);
	return str_replace(array_keys($search2replace), $search2replace, $str);

}
