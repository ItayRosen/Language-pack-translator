<?php
set_time_limit(0);
$key = ''; //google translate api key
$source = 'en'; //source language code
$target = 'de'; //target language code
$input = 'en-gb'; //the folder we wish to translate
$output = 'de-ch'; //the folder we wish to create for translated files

function getDirContents($dir)
{
    $handle = opendir($dir);
    if (!$handle)
        return array();
    $contents = array();
    while ($entry = readdir($handle)) {
        if ($entry == '.' || $entry == '..')
            continue;
        
        $entry = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_file($entry)) {
            $contents[] = $entry;
        } else if (is_dir($entry)) {
            $contents = array_merge($contents, getDirContents($entry));
        }
    }
    closedir($handle);
    return $contents;
}
function translate($string)
{
	global $key, $source, $target;
		$url = 'https://www.googleapis.com/language/translate/v2?key='.$key.'&source='.$source.'&target='.$target.'&q=' . str_replace(" ","+",$string);
	    $ch         = curl_init();
        $curlConfig = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_FOLLOWLOCATION => 1
        );
        curl_setopt_array($ch, $curlConfig);
        $json = curl_exec($ch);
        curl_close($ch);
    $decoded = json_decode($json, true);
    return $decoded['data']['translations'][0]['translatedText'];
}

$files = getDirContents($input);
foreach ($files as $file) {
    $array = explode(".", $file);
    if (end($array) == 'php') {
        $rows = file($file);
        foreach ($rows as $row) {
            if (strpos($row, ' = ') !== FALSE && strpos($row, '%') === FALSE && strpos($row, '/') === FALSE) {
                $array2                          = explode("= '", $row);
				$string                          = str_replace("';", "", $array2[1]);
                $translated                      = translate($string);
                $newRow                          = str_replace($string, $translated, $row);
                $rows[array_search($row, $rows)] = $newRow;
            }
			else
			{
				echo 'Please manually edit: '.$file.'<br>';
			}
        }
        file_put_contents(str_replace($input, $output, $file), implode($rows));
    } else {
        copy($file, str_replace($input, $output, $file));
    }
}
