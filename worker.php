<?php
// ##############################################################
// #####                        CODE                        #####
// ##### -------------------------------------------------- #####
// ##### Don't touch this if you don't know waht your doing #####
// ##############################################################
require_once ('./config.php');

if (file_exists(get_include_path() . '/' . BOOTSTRAP_WEBDATA_INCLUDE)) // Check that web_data.php exists
    require_once(BOOTSTRAP_WEBDATA_INCLUDE);
else // If it dosen't try downloading it
{
    file_put_contents(__DIR__ . '.web_data.download.cache.php', file_get_contents('https://raw.githubusercontent.com/Nomris/php-misc-libs/main/web_data.php'));
    require_once(__DIR__ . '.web_data.download.cache.php');
}

$req = new RequestData();
$TYPE = join(array: array_slice($req->Path, PRE_BOOTSTRAP_PATH_SEGMENTS), separator: '/');
if (count($req->Path) <= PRE_BOOTSTRAP_PATH_SEGMENTS)
{
    echo 'No Resource-Type provided';
    http_response_code(401);
    exit(401);
}

if (str_contains($TYPE, '..') || $req->Path[PRE_BOOTSTRAP_PATH_SEGMENTS] == '.')
{
    echo 'Attempted locale file inclusion';
    http_response_code(401);
    exit(401);
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
    exit(404);
}

$dir_path = __DIR__ . '/' . $TYPE . '/';

if (!is_dir($dir_path))
{
    echo 'Unknown Resource-Type provided';
    http_response_code(404);
    exit(404);
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

    $COMMENTS_ENABLED = $FLAGS['rname'] && !$GLOBALS['BOOTSTRAP_DISABLE_RELATIVE_NAME'] && $COMMENTS_ENABLED && isset($ini['comment_prefix']) && isset($ini['comment_suffix']);
}

$outBuffer = travel_directory($dir_path);

header('Content-Length: ' . strlen($outBuffer));

echo $outBuffer;

function travel_directory(string $dir,)
{
    global $FLAGS, $COMMENT_PREFIX, $COMMENT_SUFFIX, $EXTENSION_FILTER, $COMMENTS_ENABLED;

    $outputBuffer = '';

    foreach (scandir($dir) as $fsItem)
    {
        if ($fsItem[0] == '.') continue;
        if ($fsItem[0] == '_') continue;
        if (!str_ends_with($fsItem, $EXTENSION_FILTER)) continue;

        if (is_dir($fsItem)) $outputBuffer .= travel_directory($dir . '/' . $fsItem);

        if ($COMMENTS_ENABLED) $outputBuffer .= $COMMENT_PREFIX . '+>> ' . substr($dir . $fsItem, strlen(__DIR__) + 1) . ' <<+' . $COMMENT_SUFFIX . "\n";
        $outputBuffer .= file_get_contents($dir . '/' . $fsItem) . "\n";
        if ($COMMENTS_ENABLED) $outputBuffer .= $COMMENT_PREFIX . '->> ' .substr($dir . $fsItem, strlen(__DIR__) + 1) . ' <<-' . $COMMENT_SUFFIX . "\n";
    }

    return $outputBuffer;

}