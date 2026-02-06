<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-01-24 04:56:47 --> Query error: Unknown column 'created_at' in 'field list' - Invalid query: SELECT `id`, `code`, `name`, `description`, `created_at`, `updated_at`
FROM `roles`
ORDER BY `code` ASC
ERROR - 2026-01-24 04:56:47 --> Query error: Unknown column 'created_at' in 'field list' - Invalid query: SELECT `id`, `code`, `name`, `description`, `created_at`, `updated_at`
FROM `roles`
ORDER BY `code` ASC
ERROR - 2026-01-24 05:18:12 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 05:18:12 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 05:18:13 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 05:18:27 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 05:18:28 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 05:18:28 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 05:18:35 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 05:18:36 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 05:18:36 --> Query error: Unknown column 'oc.is_primary' in 'order clause' - Invalid query: SELECT p.*, (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id, (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number, (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship, (
                    SELECT ho2.code
                    FROM house_occupancies oc
                    JOIN houses ho2 ON ho2.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.is_primary DESC, oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
FROM `persons` `p`
ORDER BY `p`.`id` DESC
 LIMIT 20
ERROR - 2026-01-24 06:13:05 --> Query error: Table 'sis_harmoni.occupancies' doesn't exist - Invalid query: SELECT `v`.*, `h`.`id` AS `house_id`, `h`.`code` AS `house_code`
FROM `vehicles` `v`
LEFT JOIN (SELECT person_id, MAX(id) AS occ_id
FROM `occupancies`
GROUP BY `person_id`) occx ON `occx`.`person_id` = `v`.`person_id`
LEFT JOIN `occupancies` `o` ON `o`.`id` = `occx`.`occ_id`
LEFT JOIN `houses` `h` ON `h`.`id` = `o`.`house_id`
ORDER BY `v`.`id` DESC
 LIMIT 100
ERROR - 2026-01-24 06:13:05 --> Query error: Table 'sis_harmoni.occupancies' doesn't exist - Invalid query: SELECT `v`.*, `h`.`id` AS `house_id`, `h`.`code` AS `house_code`
FROM `vehicles` `v`
LEFT JOIN (SELECT person_id, MAX(id) AS occ_id
FROM `occupancies`
GROUP BY `person_id`) occx ON `occx`.`person_id` = `v`.`person_id`
LEFT JOIN `occupancies` `o` ON `o`.`id` = `occx`.`occ_id`
LEFT JOIN `houses` `h` ON `h`.`id` = `o`.`house_id`
ORDER BY `v`.`id` DESC
 LIMIT 100
