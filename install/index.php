<?php
define('ITSM_ROOT', realpath('..'));
$l = json_decode(file_get_contents("/var/www/html/itsm-ng/ng/languages/languages.json", true), true);
print_r($l);
echo "ui";