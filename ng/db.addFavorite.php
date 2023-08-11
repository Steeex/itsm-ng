<?php
if (isset($_POST['menu_name']))
file_put_contents('./test.txt', $_POST);
$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$DB->queryOrDie(
    'ALTER TABLE glpi_users ADD COLUMN IF NOT EXISTS menu_favorite longtext'
);
$favorites = $DB->request(
    [
        'SELECT' => 'menu_favorite',
        'FROM'   => 'glpi_users',
        'WHERE'  => ['id' => $_SESSION["glpiID"]]
    ]
);
if (!isset($_POST['menu_name'])) {
    echo "printing query result :<br>";
    // $favorites = json_decode($favorites->next()['menu_favorite'], MYSQLI_ASSOC);
    print_r($favorites->next());
    echo "<br>";
    echo "user_id : " . $_SESSION["glpiID"];
    // $DB->updateOrInsert('glpi_users', ['menu_favorite' => 'aa'], ['id' => '2']);
    die();
}
$favorites = json_decode($favorites->next()['menu_favorite'], true);
if (is_null($favorites)){
    $favorites = [];
}
// $favorites = [['menu' => 'test', 'submenu' => 'test2']];
if (filter_var($_POST['remove'], FILTER_VALIDATE_BOOLEAN)) {
    $key = array_search($_POST['submenu_name'], $favorites[$_POST['menu_name']]);
    unset($favorites[$_POST['menu_name']][$key]);
    if(empty($favorites[$_POST['menu_name']])){
        unset($favorites[$_POST['menu_name']]);
    } else {
        $favorites[$_POST['menu_name']] = $favorites[$_POST['menu_name']];
    }
} else {
    $favorites[$_POST['menu_name']][] = $_POST['submenu_name'];
}
file_put_contents('./test.txt', $_POST['remove']);
$favorites = json_encode($favorites);

$DB->updateOrInsert('glpi_users', ['menu_favorite' => $favorites], ['id' => $_SESSION['glpiID']]);
echo "aa";
// <?php
// $a = [["menu" => "helpdesk", "submenu" => "ticket0"],["menu" => "helpdesk", "submenu" => "ticket1"],["menu" => "helpdesk", "submenu" => "ticket2"]];
// $b = ["menu" => "helpdesk", "submenu" => "ticket2"];
// echo array_search($b, $a);
// echo "<br>";
// unset($a[array_search($b, $a)]);
// $a = json_encode(array_values($a));
// print_r($a);
// echo "<br>";

// $a = json_decode($a, true);
// print_r($a);

// echo "<br>";
// $a = json_encode(array_values($a));
// print_r($a);
