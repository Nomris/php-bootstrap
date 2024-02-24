<?php
// ##############################################################
// #####                        CODE                        #####
// ##### -------------------------------------------------- #####
// ##### Don't touch this if you don't know waht your doing #####
// ##############################################################
require_once (__DIR__ . '/config.php');

if (file_exists(get_include_path() . '/' . BOOTSTRAP_WEBDATA_INCLUDE)) // Check that web_data.php exists
    require_once(BOOTSTRAP_WEBDATA_INCLUDE);
else // If it dosen't try downloading it
{
    file_put_contents('/tmp/.web_data.download.cache.php', file_get_contents('https://raw.githubusercontent.com/Nomris/php-misc-libs/main/web_data.php'));
    require_once('/tmp/.web_data.download.cache.php');
}

$eTag = md5_file(__FILE__);
header("ETag: $eTag");

$req = new RequestData();
$TYPE = join(array: array_slice($req->Path, BOOTSTRAP_PRE_BOOTSTRAP_PATH_SEGMENTS), separator: '/');
if (count($req->Path) <= BOOTSTRAP_PRE_BOOTSTRAP_PATH_SEGMENTS)
{
    echo 'No Resource-Type provided';
    http_response_code(401);
    exit();
}

if (str_contains($TYPE, '..') || $req->Path[BOOTSTRAP_PRE_BOOTSTRAP_PATH_SEGMENTS] == '.')
{
    echo 'Attempted locale file inclusion';
    http_response_code(401);
    exit();
}


$FLAGS = $req->getQuery('flags');
if ($FLAGS)
{
    $FLAGS = explode(',', $FLAGS);
    $i = count($FLAGS);
    while (--$i >= 0)
    {
        $FLAGS[strtolower($FLAGS[$i])] = true;
        unset($FLAGS[$i]);
    }
}
else $FLAGS = null;

$TYPE = str_replace('..', '', $TYPE);

if (strtolower($TYPE) == '.git')
{
    echo 'Unknown Resource-Type provided';
    http_response_code(404);
    exit();
}

$dir_path = BOOTSTRAP_DATA_DIR;

if (!is_dir($dir_path))
{
    echo 'Unknown Resource-Type provided';
    http_response_code(404);
    exit();
}

if (file_exists($dir_path . '_metadata.ini'))
{
    $ini = parse_ini_file($dir_path . '_metadata.ini', false, INI_SCANNER_TYPED);
    if (isset($ini['mimetype'])) header("Content-Type: {$ini['mimetype']}");
    $COMMENT_PREFIX = $ini['comment_prefix'];
    $COMMENT_SUFFIX = $ini['comment_suffix'];

    $EXTENSION_FILTER = $ini['extension_filter'];
    if (!$EXTENSION_FILTER) $EXTENSION_FILTER = '';

    $COMMENTS_ENABLED = $ini['support_comment'];
    if (!isset($ini['support_comment'])) $COMMENTS_ENABLED = true;

    if (!isset($FLAGS['rname']))
        $COMMENTS_ENABLED = false;
    else
    {
        $COMMENTS_ENABLED = $COMMENTS_ENABLED && $FLAGS['rname'] && isset($ini['comment_prefix']) && isset($ini['comment_suffix']);
        if (isset($GLOBALS['BOOTSTRAP_DISABLE_RELATIVE_NAME'])) $COMMENTS_ENABLED = $COMMENTS_ENABLED && $GLOBALS['BOOTSTRAP_DISABLE_RELATIVE_NAME'];
    }
}

$LATEST_MODIFIED = 0;

$outBuffer = travel_directory($dir_path);


$LATEST_MODIFIED = gmdate('D, d M Y H:i:s', $LATEST_MODIFIED) . ' GMT';

header("Last-Modified: $LATEST_MODIFIED");

header('Cache-Control: must-revalidate');
if ($LATEST_MODIFIED == $req->getHeader('if-modified-since') && $eTAg == $req->getHeader('etag'))
{
    http_response_code(304);
    exit();
}

header('Content-Length: ' . strlen($outBuffer));

echo $outBuffer;

function travel_directory(string $dir)
{
    global $FLAGS, $COMMENT_PREFIX, $COMMENT_SUFFIX, $EXTENSION_FILTER, $COMMENTS_ENABLED, $LATEST_MODIFIED;

    $outputBuffer = '';

    foreach (scandir($dir) as $fsItem)
    {
        $modified = filemtime($fsItem);
        if ($modified > $LATEST_MODIFIED) $LATEST_MODIFIED = $modified;

        if ($fsItem[0] == '.') continue;
        if ($fsItem[0] == '_') continue;
        
        if (is_dir("$dir/$fsItem")) 
        {
            $outputBuffer .= travel_directory("$dir/$fsItem");
            continue;
        }


        if (!str_ends_with($fsItem, $EXTENSION_FILTER)) continue;


        if ($COMMENTS_ENABLED) $outputBuffer .= $COMMENT_PREFIX . '+>> ' . substr($dir . $fsItem, strlen(__DIR__) + 1) . ' <<+' . $COMMENT_SUFFIX . "\n";
        $outputBuffer .= file_get_contents($dir . '/' . $fsItem) . "\n";
        if ($COMMENTS_ENABLED) $outputBuffer .= $COMMENT_PREFIX . '->> ' .substr($dir . $fsItem, strlen(__DIR__) + 1) . ' <<-' . $COMMENT_SUFFIX . "\n";
    }

    return $outputBuffer;

}