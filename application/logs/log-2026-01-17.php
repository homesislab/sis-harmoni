<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-01-17 04:26:37 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:26:37 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:27:40 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:27:40 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:34:27 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:34:27 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:34:34 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:34:34 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:35:15 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:35:15 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:35:17 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:35:17 --> 404 Page Not Found: api/V1/products
ERROR - 2026-01-17 04:38:17 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:17 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:24 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:24 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:32 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:33 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:40 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:40 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:43 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:43 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:48 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:38:48 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:39:06 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 04:39:06 --> 404 Page Not Found: api/V1/businesses
ERROR - 2026-01-17 06:33:21 --> Severity: error --> Exception: Call to undefined method Local_product_model::paginate() /Users/gunalirezqi/Herd/sis-harmoni/application/controllers/api/Products.php 26
ERROR - 2026-01-17 06:33:21 --> Severity: error --> Exception: Call to undefined method Local_product_model::paginate() /Users/gunalirezqi/Herd/sis-harmoni/application/controllers/api/Products.php 26
ERROR - 2026-01-17 06:33:39 --> Severity: error --> Exception: Call to undefined method Local_product_model::paginate() /Users/gunalirezqi/Herd/sis-harmoni/application/controllers/api/Products.php 26
ERROR - 2026-01-17 06:33:39 --> Severity: error --> Exception: Call to undefined method Local_product_model::paginate() /Users/gunalirezqi/Herd/sis-harmoni/application/controllers/api/Products.php 26
ERROR - 2026-01-17 06:37:26 --> Severity: error --> Exception: Call to undefined method Local_product_model::paginate() /Users/gunalirezqi/Herd/sis-harmoni/application/controllers/api/Products.php 26
ERROR - 2026-01-17 06:37:27 --> Severity: error --> Exception: Call to undefined method Local_product_model::paginate() /Users/gunalirezqi/Herd/sis-harmoni/application/controllers/api/Products.php 26
ERROR - 2026-01-17 08:49:04 --> Severity: Warning --> unlink(/var/tmp/ci_session6a86f3c591b77c77ac8555c106e05be5e4fc0b22): No such file or directory /Users/gunalirezqi/Herd/sis-harmoni/system/libraries/Session/drivers/Session_files_driver.php 393
ERROR - 2026-01-17 09:34:10 --> 404 Page Not Found: api/V1/houses
ERROR - 2026-01-17 09:34:10 --> 404 Page Not Found: api/V1/houses
ERROR - 2026-01-17 12:54:05 --> Query error: Table 'sis_harmoni.occupancies' doesn't exist - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `payments` `p`
LEFT JOIN `households` `hh` ON `hh`.`id`=`p`.`payer_household_id`
LEFT JOIN `occupancies` `oc` ON `oc`.`household_id`=`hh`.`id` AND `oc`.`status`=`\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id`=`oc`.`house_id`
LEFT JOIN `household_members` `hm` ON `hm`.`household_id`=`hh`.`id` AND `hm`.`relationship`=`\"head\"`
LEFT JOIN `persons` `hp` ON `hp`.`id`=`hm`.`person_id`
WHERE `p`.`status` = 'pending'
ERROR - 2026-01-17 12:54:05 --> Query error: Table 'sis_harmoni.occupancies' doesn't exist - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `payments` `p`
LEFT JOIN `households` `hh` ON `hh`.`id`=`p`.`payer_household_id`
LEFT JOIN `occupancies` `oc` ON `oc`.`household_id`=`hh`.`id` AND `oc`.`status`=`\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id`=`oc`.`house_id`
LEFT JOIN `household_members` `hm` ON `hm`.`household_id`=`hh`.`id` AND `hm`.`relationship`=`\"head\"`
LEFT JOIN `persons` `hp` ON `hp`.`id`=`hm`.`person_id`
WHERE `p`.`status` = 'pending'
ERROR - 2026-01-17 12:54:59 --> Query error: Unknown column '\"head\"' in 'on clause' - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `payments` `p`
LEFT JOIN `households` `hh` ON `hh`.`id`=`p`.`payer_household_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id`=`hh`.`id` AND `ho`.`status`=`\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id`=`ho`.`house_id`
LEFT JOIN `household_members` `hm` ON `hm`.`household_id`=`hh`.`id` AND `hm`.`relationship`=`\"head\"`
LEFT JOIN `persons` `hp` ON `hp`.`id`=`hm`.`person_id`
WHERE `p`.`status` = 'pending'
ERROR - 2026-01-17 12:54:59 --> Query error: Unknown column '\"head\"' in 'on clause' - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `payments` `p`
LEFT JOIN `households` `hh` ON `hh`.`id`=`p`.`payer_household_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id`=`hh`.`id` AND `ho`.`status`=`\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id`=`ho`.`house_id`
LEFT JOIN `household_members` `hm` ON `hm`.`household_id`=`hh`.`id` AND `hm`.`relationship`=`\"head\"`
LEFT JOIN `persons` `hp` ON `hp`.`id`=`hm`.`person_id`
WHERE `p`.`status` = 'pending'
ERROR - 2026-01-17 13:03:44 --> Query error: Not unique table/alias: 'p' - Invalid query: SELECT p.*, hh.id as household_id, h.id as house_id, h.block as house_block, h.number as house_number, hp.full_name as head_name, hp.phone as head_phone, (
              SELECT COUNT(*) FROM payment_invoice_intents pii
              WHERE pii.payment_id = p.id
            ) as intents_count
FROM (`payments` `p`, `payments` `p`)
LEFT JOIN `households` `hh` ON `hh`.`id` = `p`.`payer_household_id`
LEFT JOIN `persons` `hp` ON `hp`.`id` = `hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `hh`.`id` AND `ho`.`status` = 'active'
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
LEFT JOIN `households` `hh` ON `hh`.`id` = `p`.`payer_household_id`
LEFT JOIN `persons` `hp` ON `hp`.`id` = `hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `hh`.`id` AND `ho`.`status` = 'active'
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
WHERE `p`.`status` = 'pending'
AND `p`.`status` = 'pending'
ORDER BY `p`.`id` DESC
 LIMIT 30
ERROR - 2026-01-17 13:03:44 --> Query error: Not unique table/alias: 'p' - Invalid query: SELECT p.*, hh.id as household_id, h.id as house_id, h.block as house_block, h.number as house_number, hp.full_name as head_name, hp.phone as head_phone, (
              SELECT COUNT(*) FROM payment_invoice_intents pii
              WHERE pii.payment_id = p.id
            ) as intents_count
FROM (`payments` `p`, `payments` `p`)
LEFT JOIN `households` `hh` ON `hh`.`id` = `p`.`payer_household_id`
LEFT JOIN `persons` `hp` ON `hp`.`id` = `hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `hh`.`id` AND `ho`.`status` = 'active'
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
LEFT JOIN `households` `hh` ON `hh`.`id` = `p`.`payer_household_id`
LEFT JOIN `persons` `hp` ON `hp`.`id` = `hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `hh`.`id` AND `ho`.`status` = 'active'
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
WHERE `p`.`status` = 'pending'
AND `p`.`status` = 'pending'
ORDER BY `p`.`id` DESC
 LIMIT 30
ERROR - 2026-01-17 17:35:49 --> Query error: Table 'sis_harmoni.occupancies' doesn't exist - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `occupancies` `oc` ON `oc`.`household_id` = `i`.`household_id` AND `oc`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `oc`.`house_id`
ERROR - 2026-01-17 17:35:54 --> Query error: Table 'sis_harmoni.occupancies' doesn't exist - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `occupancies` `oc` ON `oc`.`household_id` = `i`.`household_id` AND `oc`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `oc`.`house_id`
ERROR - 2026-01-17 17:36:06 --> Query error: Table 'sis_harmoni.occupancies' doesn't exist - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `occupancies` `oc` ON `oc`.`household_id` = `i`.`household_id` AND `oc`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `oc`.`house_id`
ERROR - 2026-01-17 17:36:06 --> Query error: Table 'sis_harmoni.occupancies' doesn't exist - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `occupancies` `oc` ON `oc`.`household_id` = `i`.`household_id` AND `oc`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `oc`.`house_id`
ERROR - 2026-01-17 17:36:35 --> Query error: Unknown column '\"active\"' in 'on clause' - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `i`.`household_id` AND `ho`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
ERROR - 2026-01-17 17:36:35 --> Query error: Unknown column '\"active\"' in 'on clause' - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `i`.`household_id` AND `ho`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
ERROR - 2026-01-17 17:36:43 --> Query error: Unknown column '\"active\"' in 'on clause' - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `i`.`household_id` AND `ho`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
ERROR - 2026-01-17 17:36:43 --> Query error: Unknown column '\"active\"' in 'on clause' - Invalid query: SELECT COUNT(*) AS `numrows`
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `i`.`household_id` AND `ho`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
ERROR - 2026-01-17 17:52:11 --> Query error: Unknown column '\"active\"' in 'on clause' - Invalid query: SELECT i.*, ct.name as charge_name, ct.category as charge_category, p.full_name as head_name, CONCAT(h.block, '-', h.number) as house_code
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `i`.`household_id` AND `ho`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
WHERE `i`.`id` = 86
ERROR - 2026-01-17 17:52:11 --> Query error: Unknown column '\"active\"' in 'on clause' - Invalid query: SELECT i.*, ct.name as charge_name, ct.category as charge_category, p.full_name as head_name, CONCAT(h.block, '-', h.number) as house_code
FROM `invoices` `i`
LEFT JOIN `charge_types` `ct` ON `ct`.`id`=`i`.`charge_type_id`
LEFT JOIN `households` `hh` ON `hh`.`id`=`i`.`household_id`
LEFT JOIN `persons` `p` ON `p`.`id`=`hh`.`head_person_id`
LEFT JOIN `house_occupancies` `ho` ON `ho`.`household_id` = `i`.`household_id` AND `ho`.`status` = `\"active\"`
LEFT JOIN `houses` `h` ON `h`.`id` = `ho`.`house_id`
WHERE `i`.`id` = 86
