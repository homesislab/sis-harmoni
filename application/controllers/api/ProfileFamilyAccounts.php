<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profile: Family Accounts
 * - List household members with account status
 * - Create account for a household member (default role: resident)
 * - Update household member account (password)
 */
class ProfileFamilyAccounts extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('User_model', 'UserModel');
        $this->load->model('Household_model', 'HouseholdModel');
        $this->load->model('Person_model', 'PersonModel');
    }

    public function index(): void
    {
        if (empty($this->auth_user['person_id'])) {
            api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
            return;
        }

        $person_id = (int)$this->auth_user['person_id'];
        $household_id = $this->UserModel->resolve_household_id_by_person($person_id);
        if (!$household_id) {
            api_ok(['household' => null, 'members' => []]);
            return;
        }

        $detail = $this->HouseholdModel->find_detail((int)$household_id);
        if (!$detail) {
            api_ok(['household' => null, 'members' => []]);
            return;
        }

        $members = $detail['members'] ?? [];
        $personIds = [];
        foreach ((array)$members as $m) {
            $pid = (int)($m['id'] ?? 0);
            if ($pid > 0) $personIds[] = $pid;
        }
        $personIds = array_values(array_unique($personIds));

        $accountsByPerson = [];
        if ($personIds) {
            $rows = $this->db
                ->select('id, person_id, username, status, created_at, updated_at')
                ->from('users')
                ->where_in('person_id', $personIds)
                ->get()->result_array();
            foreach ($rows as $u) {
                $accountsByPerson[(int)$u['person_id']] = $u;
            }
        }

        $outMembers = [];
        foreach ((array)$members as $m) {
            $pid = (int)($m['id'] ?? 0);
            $out = $m;
            $out['account'] = $pid > 0 && isset($accountsByPerson[$pid]) ? $accountsByPerson[$pid] : null;
            $outMembers[] = $out;
        }

        api_ok([
            'household' => $detail['household'] ?? null,
            'head' => $detail['head'] ?? null,
            'members' => $outMembers,
        ]);
    }

    public function store(): void
    {
        if (empty($this->auth_user['person_id'])) {
            api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
            return;
        }

        $in = $this->json_input();
        $target_person_id = (int)($in['person_id'] ?? 0);
        $username = trim((string)($in['username'] ?? ''));
        $password = (string)($in['password'] ?? '');

        $err = [];
        if ($target_person_id <= 0) $err['person_id'] = 'Wajib diisi';
        if ($username === '') $err['username'] = 'Wajib diisi';
        if (strlen($password) < 6) $err['password'] = 'Minimal 6 karakter';
        if ($err) { api_validation_error($err); return; }

        $me_person_id = (int)$this->auth_user['person_id'];
        $household_id = $this->UserModel->resolve_household_id_by_person($me_person_id);
        if (!$household_id) {
            api_error('FORBIDDEN', 'KK tidak ditemukan', 403);
            return;
        }
        $allowed = $this->HouseholdModel->person_is_member($target_person_id, (int)$household_id)
            || ((int)($this->db->select('head_person_id')->from('households')->where('id', (int)$household_id)->get()->row_array()['head_person_id'] ?? 0) === $target_person_id);
        if (!$allowed) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        if (!$this->PersonModel->find_by_id($target_person_id)) {
            api_validation_error(['person_id' => 'Warga tidak ditemukan']);
            return;
        }

        if ($this->UserModel->find_by_username($username)) {
            api_conflict('Username sudah digunakan');
            return;
        }

        $existing = $this->db->get_where('users', ['person_id' => $target_person_id])->row_array();
        if ($existing) {
            api_conflict('Akun untuk warga ini sudah ada');
            return;
        }

        $id = $this->UserModel->create([
            'person_id' => $target_person_id,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'status' => 'active',
        ]);

        $this->UserModel->assign_role_code($id, 'resident');

        $user = $this->UserModel->find_by_id($id);
        unset($user['password_hash']);
        api_ok($user, null, 201);
    }

    public function update(int $user_id = 0): void
    {
        if ($user_id <= 0) { api_not_found(); return; }
        if (empty($this->auth_user['person_id'])) {
            api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
            return;
        }

        $u = $this->db->get_where('users', ['id' => $user_id])->row_array();
        if (!$u) { api_not_found(); return; }
        $target_person_id = (int)($u['person_id'] ?? 0);
        if ($target_person_id <= 0) { api_error('FORBIDDEN', 'Akun belum terhubung ke data warga', 403); return; }

        $me_person_id = (int)$this->auth_user['person_id'];
        $household_id = $this->UserModel->resolve_household_id_by_person($me_person_id);
        if (!$household_id) { api_error('FORBIDDEN', 'KK tidak ditemukan', 403); return; }

        $allowed = $this->HouseholdModel->person_is_member($target_person_id, (int)$household_id)
            || ((int)($this->db->select('head_person_id')->from('households')->where('id', (int)$household_id)->get()->row_array()['head_person_id'] ?? 0) === $target_person_id);
        if (!$allowed) { api_error('FORBIDDEN', 'Akses ditolak', 403); return; }

        $in = $this->json_input();
        $upd = [];

        if (isset($in['password'])) {
            $p = trim((string)$in['password']);
            if ($p !== '') {
                if (strlen($p) < 6) { api_validation_error(['password' => 'Minimal 6 karakter']); return; }
                $upd['password_hash'] = password_hash($p, PASSWORD_BCRYPT);
            }
        }

        if ($upd) {
            $this->db->where('id', $user_id)->update('users', $upd);
        }

        $fresh = $this->UserModel->find_by_id($user_id);
        unset($fresh['password_hash']);
        api_ok($fresh);
    }
}
