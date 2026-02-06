<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-01-29 03:59:02 --> Query error: Table 'sis_harmoni.ownerships' doesn't exist - Invalid query: 
            SELECT DISTINCT p.id, p.full_name
            FROM persons p
            JOIN ownerships o ON o.person_id = p.id
            WHERE o.house_id = 163 AND (o.end_date IS NULL OR o.end_date = '')
            UNION
            SELECT DISTINCT p2.id, p2.full_name
            FROM persons p2
            JOIN households hh ON hh.id = (
                SELECT ho.household_id
                FROM house_occupancies ho
                WHERE ho.house_id = 163 AND ho.status = 'active'
                ORDER BY ho.id DESC
                LIMIT 1
            )
            JOIN household_members hm ON hm.household_id = hh.id
            WHERE hm.person_id = p2.id
            ORDER BY full_name ASC
        
ERROR - 2026-01-29 03:59:13 --> Query error: Table 'sis_harmoni.ownerships' doesn't exist - Invalid query: 
            SELECT DISTINCT p.id, p.full_name
            FROM persons p
            JOIN ownerships o ON o.person_id = p.id
            WHERE o.house_id = 182 AND (o.end_date IS NULL OR o.end_date = '')
            UNION
            SELECT DISTINCT p2.id, p2.full_name
            FROM persons p2
            JOIN households hh ON hh.id = (
                SELECT ho.household_id
                FROM house_occupancies ho
                WHERE ho.house_id = 182 AND ho.status = 'active'
                ORDER BY ho.id DESC
                LIMIT 1
            )
            JOIN household_members hm ON hm.household_id = hh.id
            WHERE hm.person_id = p2.id
            ORDER BY full_name ASC
        
ERROR - 2026-01-29 03:59:45 --> Query error: Table 'sis_harmoni.ownerships' doesn't exist - Invalid query: 
            SELECT DISTINCT p.id, p.full_name
            FROM persons p
            JOIN ownerships o ON o.person_id = p.id
            WHERE o.house_id = 182 AND (o.end_date IS NULL OR o.end_date = '')
            UNION
            SELECT DISTINCT p2.id, p2.full_name
            FROM persons p2
            JOIN households hh ON hh.id = (
                SELECT ho.household_id
                FROM house_occupancies ho
                WHERE ho.house_id = 182 AND ho.status = 'active'
                ORDER BY ho.id DESC
                LIMIT 1
            )
            JOIN household_members hm ON hm.household_id = hh.id
            WHERE hm.person_id = p2.id
            ORDER BY full_name ASC
        
ERROR - 2026-01-29 13:11:36 --> Query error: Unknown column 'created_at' in 'field list' - Invalid query: SELECT `id`, `code`, `name`, `description`, `created_at`, `updated_at`
FROM `roles`
ORDER BY `code` ASC
ERROR - 2026-01-29 13:11:36 --> Query error: Unknown column 'created_at' in 'field list' - Invalid query: SELECT `id`, `code`, `name`, `description`, `created_at`, `updated_at`
FROM `roles`
ORDER BY `code` ASC
ERROR - 2026-01-29 13:11:50 --> Query error: Unknown column 'created_at' in 'field list' - Invalid query: SELECT `id`, `code`, `name`, `description`, `created_at`, `updated_at`
FROM `roles`
ORDER BY `code` ASC
ERROR - 2026-01-29 13:11:50 --> Query error: Unknown column 'created_at' in 'field list' - Invalid query: SELECT `id`, `code`, `name`, `description`, `created_at`, `updated_at`
FROM `roles`
ORDER BY `code` ASC
