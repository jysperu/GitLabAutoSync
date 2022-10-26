<?php

//== bye buffer
while(ob_get_level())
	ob_end_clean();


//=== VARIABLES

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

defined('PHAR_SOURCE') or define('PHAR_SOURCE', __DIR__ . '/src');
defined('PHAR_OUTPUT') or define('PHAR_OUTPUT', __DIR__ . '/dist/GitLabAutoSync.phar');

defined('PHAR_OUTPUT_gz')  or define('PHAR_OUTPUT_gz',  PHAR_OUTPUT . '.gz');
defined('PHAR_OUTPUT_dn')  or define('PHAR_OUTPUT_dn',  dirname(PHAR_OUTPUT));
defined('PHAR_OUTPUT_php') or define('PHAR_OUTPUT_php', PHAR_OUTPUT_dn . DS . basename(PHAR_OUTPUT, '.phar') . '.php');
defined('PHAR_OUTPUT_zip') or define('PHAR_OUTPUT_zip', PHAR_OUTPUT_dn . DS . 'glas.v' . date('YmdHis', filemtime(__DIR__)) . '.zip');


//=== CLEANING

if (file_exists(PHAR_OUTPUT))
	unlink(PHAR_OUTPUT);

if (file_exists(PHAR_OUTPUT_gz))
	unlink(PHAR_OUTPUT_gz);


//=== CREATING PHAR

$phar = new Phar(PHAR_OUTPUT);
$phar->buildFromDirectory(PHAR_SOURCE);

$phargz = $phar->compress(Phar::GZ);
$phargz->setStub('<?php return include (\'phar://\' . __FILE__ . \'/bootstrap.php\'); __HALT_COMPILER();');

if (file_exists(PHAR_OUTPUT))
	unlink(PHAR_OUTPUT);


//=== CRATING INDEX.PHP
$index_content = '<?php' . PHP_EOL;
$index_content.= 'chdir(__DIR__);' . PHP_EOL;
$index_content.= 'defined(\'GitLabAutoSync_CONFIGFILE\') or define(\'GitLabAutoSync_CONFIGFILE\', __DIR__ . \'/GitLabAutoSync.config.php\');' . PHP_EOL;
$index_content.= 'return require_once \'phar://./' . basename(PHAR_OUTPUT_gz) . '\';' . PHP_EOL;

file_put_contents(PHAR_OUTPUT_php, $index_content);

$zip = new ZipArchive;
$zip->open(PHAR_OUTPUT_zip, ZipArchive::CREATE);

$zip->addFile(PHAR_OUTPUT_gz,  basename(PHAR_OUTPUT_gz));
$zip->addFile(PHAR_OUTPUT_php, basename(PHAR_OUTPUT_php));

$zip->close();

if (file_exists(__DIR__ . DS . 'test'))
{
	copy(PHAR_OUTPUT_gz,  __DIR__ . DS . 'test' . DS . 'GitLabAutoSync.phar.gz');
	copy(PHAR_OUTPUT_php, __DIR__ . DS . 'test' . DS . 'GitLabAutoSync.php');
}