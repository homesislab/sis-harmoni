<?php
// tools/gen_permissions_sql.php
// Usage: php tools/gen_permissions_sql.php > permissions_seed.sql
$path = __DIR__ . '/../permission-registry.json';
$raw = file_get_contents($path);
if (!$raw) { fwrite(STDERR, "Cannot read permission-registry.json\n"); exit(1); }
$data = json_decode($raw, true);
if (!$data) { fwrite(STDERR, "Invalid JSON\n"); exit(1); }

$perms = $data['permissions'] ?? [];
$defaults = $data['defaults'] ?? [];

echo "-- Generated from permission-registry.json\n";
echo "START TRANSACTION;\n\n";

foreach ($perms as $p) {
  $code = addslashes($p['code'] ?? '');
  $name = addslashes($p['name'] ?? $code);
  $desc = addslashes($p['description'] ?? '');
  if (!$code) continue;
  echo "INSERT INTO permissions (code,name,description,created_at,updated_at)\n";
  echo "VALUES ('$code','$name','$desc',NOW(),NOW())\n";
  echo "ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=NOW();\n\n";
}

// Role mapping: if '*' given, handled at runtime by admin bypass. We still map explicit ones.
foreach ($defaults as $roleCode => $codes) {
  if (!is_array($codes)) continue;
  $roleCodeEsc = addslashes($roleCode);
  echo "-- defaults for role: $roleCodeEsc\n";
  echo "INSERT INTO roles (code,name,created_at,updated_at)\n";
  echo "VALUES ('$roleCodeEsc', '$roleCodeEsc', NOW(), NOW())\n";
  echo "ON DUPLICATE KEY UPDATE updated_at=NOW();\n";
  echo "SET @rid := (SELECT id FROM roles WHERE code='$roleCodeEsc' LIMIT 1);\n";
  echo "DELETE FROM role_permissions WHERE role_id=@rid;\n";
  foreach ($codes as $c) {
    if ($c === '*') continue;
    $cEsc = addslashes($c);
    echo "INSERT INTO role_permissions (role_id, permission_id)\n";
    echo "SELECT @rid, p.id FROM permissions p WHERE p.code='$cEsc'\n";
    echo "ON DUPLICATE KEY UPDATE permission_id=permission_id;\n";
  }
  echo "\n";
}

echo "COMMIT;\n";
