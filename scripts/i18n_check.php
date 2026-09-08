<?php
/** Report translation keys used in code but missing from app/lang/fr.php. */
$dict = require __DIR__ . '/../app/lang/fr.php';
$keys = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app'));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') {
        continue;
    }
    if (strpos($f->getPathname(), 'lang') !== false) {
        continue;
    }
    $src = file_get_contents($f->getPathname());
    preg_match_all('/__\(\'((?:[^\'\\\\]|\\\\.)*)\'\s*[,)]/', $src, $m);
    foreach ($m[1] as $k) {
        $keys[stripslashes($k)] = true;
    }
}
$missing = array_keys(array_diff_key($keys, $dict));
sort($missing);
echo count($keys) . " distinct keys, " . count($missing) . " missing from fr.php\n";
foreach ($missing as $k) {
    echo '  [' . $k . "]\n";
}
