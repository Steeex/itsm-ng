<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_DEPRECATED ^ E_WARNING);

session_start();
define('GLPI_ROOT', realpath('..'));


include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_ROOT . "/inc/db.function.php");

$GLPI = new GLPI();
$GLPI->initLogger();
$GLPI->initErrorHandler();

Config::detectRootDoc();
require_once '../vendor/autoload.php';

use Glpi\System\RequirementsManager;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;
$loader = new FilesystemLoader('../templates/install');
$twig = new Environment($loader, [
    /*     'cache' => './cache', */
    'cache' => false
]);
$TRANSLATE;

$filter = new \Twig\TwigFilter('trans', '__');
$twig->addFilter($filter);


function check_post($var_name){
    global $twig;
    global $header_data;
    if (!isset($_POST[$var_name])){
        if (!isset($_SESSION[$var_name])){
            echo $twig->render('index.html.twig', ['header_data'=> $header_data,'step' => 'error']);
            die();
        } else {
            return false;
        }
    }
    return true;
}


header("Cache-Control: private, max-age=10800, pre-check=10800");
header("Pragma: private");
header("Expires: " . date(DATE_RFC822,strtotime("+2 day")));


$header_data = [  
    "js_variables"  =>   Html::getCoreVariablesForJavascript(),
    "js_scripts"    =>  [
                        Html::script("public/lib/base.js"),
                        Html::script("public/lib/fuzzy.js"),
                        Html::script("js/common.js"),
                        Html::script("js/bootstrap.bundle.min.js"),
                        Html::script("js/bootstrap-select.min.js"),
                        Html::script("js/tableExport.min.js"),
                        Html::script("js/bootstrap-table.min.js"),
                        Html::script("js/bootstrap-table-export.min.js"),

                        ],
    "css_files"     =>  [
                        Html::css("css/bootstrap.min.css"),
                        Html::css('css/bootstrap-select.min.css'),
                        Html::css('css/bootstrap-table.min.css'),
                        Html::css("css/fresh-bootstrap-table.css"),
                        Html::css('public/lib/base.css'),
                        Html::css("css/style_install.css"),
                        Html::css("css/font-awesome.min.css"),
                         ]
                    ];

$steps =   ['0', '1', '2', '3', '4', '5', '6','7','8', 'error'];
global $CFG_GLPI;
if (isset($_GET['step']) and in_array($_GET['step'], $steps)) {
    $step = $_GET['step'];
} else {
    $step = '0';
}
$twig_vars = [];
Session::loadLanguage('', false);
switch ($step) {
    case "0":
        $languages_raw = $CFG_GLPI["languages"];

        $european_languages_code = [                                                    //a implementer directement dans $CFG_GLPI["languages"]
            'bd_BG','ca_ES','cz_CZ','de_DE','da_DK','et_EE','en_GB','eu_ES','fr_FR','fr_BE','es_ES',
            'gl_ES','el_GR','hr_HR','hu_HU','it_IT','lv_LV','lt_LT','nl_NL','nl_BE',
            'nb_NO','nn_NO','pt_PT','ro_RO','ru_RU','sk_SK','sl_SI','sr_RS','fi_FI',
            'sv_SE','tr_TR','uk_UA','be_BY','is_IS'];

        $languages = [
            'europe' => [],
            'others' => [] 
        ];

        foreach($languages_raw as $code => $l) {
            if (in_array($code, $european_languages_code)){
                $languages['europe'][] = [$code => $l];
            } else {
                $languages['others'][] = [$code => $l];
            }
        }
        if (isset($_SESSION['language'])){
            $language = $_SESSION['language'];
        } else {
            $language = Session::getPreferredLanguage();
        }
        $twig_vars = ['languages' => $languages, 'preferred_language' =>  $language];
        break;

    case "1":
        if (check_post('language')){$_SESSION['language'] = $_POST['language'];}
        Session::loadLanguage($_SESSION['language'], false);
        $TRANSLATE->setLocale($_SESSION['language']);
        $license = file_get_contents("../COPYING.txt");
        $twig_vars = ['license' => $license];
        break;

    case "3":
        if (isset($_POST['install'])){
            $_SESSION['action'] = 'install';
        } else if (isset($_POST['update'])){
            $_SESSION['action'] = 'update';
        }
        $raw_requirements = (new RequirementsManager())->getCoreRequirementList(null);
        $requirements = [];
        foreach ($raw_requirements as $raw_requirement) {
            if (!$raw_requirement->isOutOfContext()) { // skip raw_requirement if not relevant
                $title = $raw_requirement->getTitle();
                $required = $raw_requirement->isMissing() && !$raw_requirement->isOptional();
                $optional = $raw_requirement->isMissing() && $raw_requirement->isOptional();
                $validated = $raw_requirement->isValidated();
                $message = implode('. ', Html::entities_deep($raw_requirement->getValidationMessages()));
                $requirement = ['title' => $title, 'required' => $required, 'validated' => $validated, 'optional' => $optional, 'message' => $message];
                $requirements[] = $requirement;
            }
        }
        if ($raw_requirements->hasMissingMandatoryRequirements()) {
            $passed = 2;
        } else if ($raw_requirements->hasMissingOptionalRequirements()) {
            $passed = 1;
        } else {
            $passed = 0;
        }

        $twig_vars = ['requirements' => $requirements, 'passed' => $passed];
        break;

    case "4":
        $host = isset($_SESSION['db_host']) ? $_SESSION['db_host'] : "";
        $user = isset($_SESSION['db_user']) ? $_SESSION['db_user'] : "";

        $twig_vars = ['host' => $host, 'user' => $user];
        break;

    case "5":
        if (check_post('db_host')){
            $_SESSION['db_host'] = $_POST['db_host'];
        }
        if (check_post('db_user')){
            $_SESSION['db_user'] = $_POST['db_user'];
        }
        if (check_post('db_pass')){
            $_SESSION['db_pass'] = $_POST['db_pass'];
        }
        error_reporting(16);
        mysqli_report(MYSQLI_REPORT_OFF);
        $hostport = explode(":", $_SESSION['db_host']);
        if (count($hostport) < 2) {
            $link = new mysqli($hostport[0], $_SESSION['db_user'], $_SESSION['db_pass']);
        } else {
            $link = new mysqli($hostport[0], $_SESSION['db_user'], $_SESSION['db_pass'], '', $hostport[1]);
        } 
        $connect_error = $link->connect_error;
        if (!$connect_error){
            $DB_ver = $link->query("SELECT version()");
            $row = $DB_ver->fetch_array();
            $version = $row[0];
            $result = Config::checkDbEngine($version);
            $version = key($result);
            $db_ver = $result[$version];
            if (!$db_ver) {
                $ver_too_old = true;
            } else {
                $ver_too_old = false;
                $databases_info = [];
                $db_info = [];
                if ($DB_list = $link->query(   
                   "SELECT S.schema_name AS 'name', COUNT(T.table_name) AS 'table_count', DATE(MIN(T.create_time)) AS 'table_create', DATE(MAX(T.update_time)) AS 'table_update'
                    FROM information_schema.tables AS T 
                    RIGHT JOIN information_schema.schemata AS S 
                    ON S.schema_name = T.table_schema
                    GROUP BY S.schema_name;")){
                    while ($row = $DB_list->fetch_array(MYSQLI_ASSOC)) {
                        if (!in_array($row['name'],["information_schema","mysql","performance_schema","sys"])){
                            $databases_info[] = $row;
                        }
                    }
                }
            }
            $link->close();
        }
        $twig_vars = [  'host' =>           $_SESSION['db_host'],   'user' =>       $_SESSION['db_user'], 
                        'connect_error' =>  $connect_error,         'version' =>    $version, 
                        'ver_too_old' =>    $ver_too_old,           'action' =>     $_SESSION['action'],
                        'databases' =>      $databases_info];
        break;

    case "6":
        global $done;
        if (isset($_SESSION['initialized']) and $_SESSION['initialized']){
            Toolbox::createSchema($_SESSION['language']);
            $done = true;
        } else {
            print_r($_POST);
            $done = false;
            if (check_post('databasename')){
                if (empty($_POST['databasename'])){
                    $_SESSION['databasename'] = $_POST['newdatabasename'];
                    $_SESSION['new'] = true;
                } else {
                    $_SESSION['databasename'] = $_POST['databasename'];
                    $_SESSION['new'] = false;
                }
            }
            $created = false;
            $_SESSION['initialized'] = false;
            $sql_error = "";
            $error = "";
            $db_state = "";
            $secured = null;
            if ($_SESSION['action'] == 'install'){
                $glpikey = new GLPIKey();
                $secured = $glpikey->keyExists();
                if (!$secured) {
                    $secured = $glpikey->generate();
                }
                if ($secured){
                    mysqli_report(MYSQLI_REPORT_OFF);
                    $hostport = explode(":", $_SESSION['db_host']);
                    if (count($hostport) < 2) {
                        $link = new mysqli($hostport[0], $_SESSION['db_user'], $_SESSION['db_pass']);
                    } else {
                        $link = new mysqli($hostport[0], $_SESSION['db_user'], $_SESSION['db_pass'], '', $hostport[1]);
                    } 
                    $databasename = $link->real_escape_string($_SESSION['databasename']);// use db already created
                    $DB_selected = $link->select_db($databasename);
                    $db_state = "";
                    if ($_SESSION['new'] && !$DB_selected) {
                        if ($link->query("CREATE DATABASE IF NOT EXISTS `".$databasename."`")) {
                            $DB_selected = $link->select_db($databasename);
                            $db_state = "created";
                            $created = true;
                        } else {
                            $error = "create_db";
                        }
                    }
                    if (!$DB_selected){
                        $sql_error = $link->error;
                        $error = "use";
                    } else {
                        $_SESSION['language'] = Session::getPreferredLanguage();
                        if (DBConnection::createMainConfig($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['databasename'])){
                            $db_state = "initialized";
                            $_SESSION['initialized'] = true;
                            $error = "none";
                        } else { 
                            $error = "setup";
                        }
                    } 
                } else {
                    $error = "select";
                    }
                } else if ($_SESSION['action'] == 'update') {
                    if (DBConnection::createMainConfig($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['databasename'])){
    /*                     include_once(GLPI_ROOT ."/install/update.php"); */
                    } else { // can't create config_db file
                        $error = "create_config";
                    }
                }
            }
                $twig_vars = [  'db_state'      => $db_state,       'action'        => $_SESSION['action'],
                                'created' => $created,              'initialized'   => $_SESSION['initialized'],
                                'error'         => $error,          'done'          => $done,
                                'secured'   => $secured,            'sql_error'     => $sql_error];
        break;

    case "7":
        include_once(GLPI_ROOT . "/inc/dbmysql.class.php");
        include_once(GLPI_CONFIG_DIR . "/config_db.php");
        $DB = new DB();

        $url_base = str_replace("/install/install.php", "", $_SERVER['HTTP_REFERER']);
        $DB->update(
            'glpi_configs',
            ['value' => $DB->escape($url_base)], [
                'context'   => 'core',
                'name'      => 'url_base'
            ]
        );

        $url_base_api = "$url_base/apirest.php/";
        $DB->update(
            'glpi_configs',
            ['value' => $DB->escape($url_base_api)], [
                'context'   => 'core',
                'name'      => 'url_base_api'
            ]
        );

}
try {
    echo $twig->render('index.html.twig',  ['step' => $step,'header_data' => $header_data] + $twig_vars);
    if($step == "5" and !$done){
        header("Refresh:0");
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}
