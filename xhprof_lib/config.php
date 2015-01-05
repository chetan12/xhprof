<?php
global $_xhprof;
$_xhprof = array();

//Create the constants below
$_xhprof['dbtype'] = DB_TYPE; // Only relevant for PDO
$_xhprof['dbhost'] = DB_HOST;
$_xhprof['dbuser'] = DB_USER;
$_xhprof['dbpass'] = DB_PASS;
$_xhprof['dbname'] = DB_NAME;
$_xhprof['dbadapter'] = DB_ADAPTER;
$_xhprof['servername'] = SERVER_NAME;
$_xhprof['namespace'] = NAME_SPACE;

$_xhprof['url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/xhprof';

/*
 * MySQL/MySQLi/PDO ONLY
 * Switch to JSON for better performance and support for larger profiler data sets.
 * WARNING: Will break with existing profile data, you will need to TRUNCATE the profile data table.
 */
$_xhprof['serializer'] = 'php'; 

$_xhprof['dot_binary']  = '/usr/bin/dot';
$_xhprof['dot_tempdir'] = '/tmp';
$_xhprof['dot_errfile'] = '/tmp/xh_dot.err';

$ignoreURLs = array();
$ignoreDomains = array();
$exceptionURLs = array();
$exceptionPostURLs = array();
$exceptionPostURLs[] = "login";


$_xhprof['display'] = false;
$_xhprof['doprofile'] = false;

if(!isset($GLOBALS['controlIPs']))
    $GLOBALS['controlIPs'] = false; //Disables access controlls completely.

//Default weight - can be overidden by an Apache environment variable 'xhprof_weight' for domain-specific values
$weight = 100;

if($domain_weight = getenv('xhprof_weight')) {
	$weight = $domain_weight;
}

unset($domain_weight);

  /**
  * The goal of this function is to accept the URL for a resource, and return a "simplified" version
  * thereof. Similar URLs should become identical. Consider:
  * http://example.org/stories.php?id=2323
  * http://example.org/stories.php?id=2324
  * Under most setups these two URLs, while unique, will have an identical execution path, thus it's
  * worthwhile to consider them as identical. The script will store both the original URL and the
  * Simplified URL for display and comparison purposes. A good simplified URL would be:
  * http://example.org/stories.php?id=
  * 
  * @param string $url The URL to be simplified
  * @return string The simplified URL 
  */
  function _urlSimilartor($url)
  {
      //This is an example 
      $url = preg_replace("!\d{4}!", "", $url);
      
      // For domain-specific configuration, you can use Apache setEnv xhprof_urlSimilartor_include [some_php_file]
      if($similartorinclude = getenv('xhprof_urlSimilartor_include')) {
      	require_once($similartorinclude);
      }
      
      $url = preg_replace("![?&]_profile=\d!", "", $url);
      return $url;
  }
  
  function _aggregateCalls($calls, $rules = null)
  {
    $rules = array(
        'Loading' => 'load::',
        'mysql' => 'mysql_'
        );

    // For domain-specific configuration, you can use Apache setEnv xhprof_aggregateCalls_include [some_php_file]
  	if(isset($run_details['aggregateCalls_include']) && strlen($run_details['aggregateCalls_include']) > 1)
		{
    	require_once($run_details['aggregateCalls_include']);
		}        
        
    $addIns = array();
    foreach($calls as $index => $call)
    {
        foreach($rules as $rule => $search)
        {
            if (strpos($call['fn'], $search) !== false)
            {
                if (isset($addIns[$search]))
                {
                    unset($call['fn']);
                    foreach($call as $k => $v)
                    {
                        $addIns[$search][$k] += $v;
                    }
                }else
                {
                    $call['fn'] = $rule;
                    $addIns[$search] = $call;
                }
                unset($calls[$index]);  //Remove it from the listing
                break;  //We don't need to run any more rules on this
            }else
            {
                //echo "nomatch for $search in {$call['fn']}<br />\n";
            }
        }
    }
    return array_merge($addIns, $calls);
  }
