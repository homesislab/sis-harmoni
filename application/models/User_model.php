<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    public function find_by_id(int $id): ?array
    {
        $row = $this->db
            ->select('users.id, users.person_id, persons.full_name,
                users.username, users.email, users.status, users.created_at, users.updated_at')
            ->from('users')
            ->join('persons', 'persons.id = users.person_id', 'left')
            ->where('users.id', $id)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    public function find_by_username(string $username): ?array
    {
        $row = $this->db->get_where('users', ['username' => $username])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $insert = [
            'person_id'      => $data['person_id'] ?? null,
            'username'       => $data['username'],
            'password_hash'  => $data['password_hash'],
            'email'          => $data['email'] ?? null,
            'status'         => $data['status'] ?? 'active',
        ];

        $this->db->insert('users', $insert);
        return (int)$this->db->insert_id();
    }

    public function is_email_taken(string $email, ?int $exclude_user_id = null): bool
    {
        $this->db->from('users')->where('email', $email);
        if ($exclude_user_id) $this->db->where('id !=', $exclude_user_id);
        return (int)$this->db->count_all_results() > 0;
    }

    public function update_self(int $user_id, array $in): void
    {
        $upd = [];
        if (array_key_exists('email', $in)) {
            $upd['email'] = $in['email'] !== null ? trim((string)$in['email']) : null;
        }
        if (array_key_exists('password_hash', $in)) {
            $upd['password_hash'] = (string)$in['password_hash'];
        }
        if (!$upd) return;
        $this->db->where('id', $user_id)->update('users', $upd);
    }
    public function get_me_payload(int $user_id, ?array $rbac = null): ?array
    {
        $user = $this->find_by_id($user_id);
        if (!$user) return null;

        unset($user['password_hash']);

        return [
            'user' => $user,
            'roles' => $rbac['roles'] ?? [],
            'permissions' => $rbac['permissions'] ?? [],
        ];
    }

    public function resolve_household_id_by_person(int $person_id): ?int
    {
        $row = $this->db->select('id')
            ->from('households')
            ->where('head_person_id', $person_id)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row_array();
        if ($row) return (int)$row['id'];

        $row2 = $this->db->select('household_id')
            ->from('household_members')
            ->where('person_id', $person_id)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row_array();
        return $row2 ? (int)$row2['household_id'] : null;
    }

    public function resolve_house_id_by_household(int $household_id): ?int
    {
        $row = $this->db->select('house_id')
            ->from('house_occupancies')
            ->where('household_id', $household_id)
            ->where('status', 'active')
            ->order_by("FIELD(occupancy_type,'tenant','owner_live','family','caretaker','owner_not_live')", '', false)
            ->order_by('start_date', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row_array();
        return $row ? (int)$row['house_id'] : null;
    }

public function assign_role_code(int $user_id, string $role_code): void
    {
        $role = $this->db->get_where('roles', ['code' => $role_code])->row_array();
        if (!$role) return;

        $exists = $this->db->get_where('user_roles', [
            'user_id' => $user_id,
            'role_id' => (int)$role['id'],
        ])->row_array();

        if ($exists) return;

        $this->db->insert('user_roles', [
            'user_id' => $user_id,
            'role_id' => (int)$role['id'],
        ]);
    }
}
