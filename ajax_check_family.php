<?php
// ajax_check_family.php
require_once 'config.php';
use classes\Security\Security;
U::log("\$_GET: " . json_encode($_GET));

$family_name = $_GET['family_name'] ?? '';
$family_id = $db_c->SELECT('SELECT family_id FROM families WHERE family_name = :family_name', ['family_name' => $family_name]);
if ($family_id) {
  echo json_encode(['exists' => true]);
} else {
  echo json_encode(['exists' => false]);
}
exit;
?>