<?php

defined('BASEPATH') or exit('No direct script access allowed');

class SecurityAttendance extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Security_attendance_model', 'AttendanceModel');
    }

    private function current_guard(): ?array
    {
        if (empty($this->auth_user['id'])) {
            return null;
        }
        $guard = $this->db->get_where('security_guards', ['user_id' => $this->auth_user['id']])->row_array();
        return $guard ?: null;
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];
        $date = trim((string)($this->input->get('date') ?? ''));
        $status = trim((string)($this->input->get('status') ?? ''));

        $guard_id = null;
        if (!$this->has_permission('app.services.security.attendance.manage')) {
            $guard = $this->current_guard();
            $guard_id = $guard ? (int)$guard['id'] : -1;
        }

        $res = $this->AttendanceModel->get_list($page, $per, $date, $status, $guard_id);

        api_ok(['items' => $res['items']], [
            'page' => $page,
            'per_page' => $per,
            'total' => $res['total'],
        ]);
    }

    public function check_in(): void
    {
        $in = $this->json_input();
        $can_manage = $this->has_permission('app.services.security.attendance.manage');

        $security_guard_id = $can_manage ? (int)($in['security_guard_id'] ?? 0) : 0;
        if ($security_guard_id <= 0) {
            $guard = $this->current_guard();
            if ($guard) {
                $security_guard_id = (int)$guard['id'];
            }
        }

        $shift_id = isset($in['shift_id']) ? (int)$in['shift_id'] : null;
        $date = $in['date'] ?? date('Y-m-d');

        $err = [];
        if ($security_guard_id <= 0) {
            $err['security_guard_id'] = 'Security guard wajib dipilih atau akun Anda belum terhubung ke data anggota keamanan.';
        }

        if ($err) {
            api_validation_error($err);
            return;
        }

        $existing = $this->AttendanceModel->find_by_guard_and_date($security_guard_id, $date);
        if ($existing) {
            api_validation_error(['date' => 'Sudah melakukan absensi pada tanggal ini']);
            return;
        }

        $id = $this->AttendanceModel->create([
            'security_guard_id' => $security_guard_id,
            'shift_id' => $shift_id,
            'date' => $date,
            'check_in_time' => date('Y-m-d H:i:s'),
            'status' => 'present',
            'latitude' => $in['latitude'] ?? null,
            'longitude' => $in['longitude'] ?? null,
            'notes' => $in['notes'] ?? null,
            'created_by' => $this->auth_user['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $attendance = $this->AttendanceModel->find_by_id($id);
        api_ok($attendance, null, 201);
    }

    public function check_out(): void
    {
        $in = $this->json_input();
        $id = isset($in['id']) ? (int)$in['id'] : 0;
        $can_manage = $this->has_permission('app.services.security.attendance.manage');
        $guard = $can_manage ? null : $this->current_guard();
        $guard_id = $guard ? (int)$guard['id'] : 0;

        if (!$can_manage) {
            if ($guard_id <= 0) {
                api_validation_error(['security_guard_id' => 'Akun Anda belum terhubung ke data anggota keamanan.']);
                return;
            }

            if ($id <= 0) {
                $att = $this->db->get_where('security_attendances', [
                    'security_guard_id' => $guard_id,
                    'date' => date('Y-m-d')
                ])->row_array();
                if ($att) {
                    $id = (int)$att['id'];
                }
            }
        }

        if ($id <= 0) {
            api_validation_error(['id' => 'Gagal mendeteksi sesi absensi. Pastikan Anda sudah Check-in pada hari ini.']);
            return;
        }

        $attendance = $this->AttendanceModel->find_by_id($id);
        if (!$attendance) {
            api_not_found('Absensi tidak ditemukan');
            return;
        }

        if (!$can_manage && (int)($attendance['security_guard_id'] ?? 0) !== $guard_id) {
            api_error('FORBIDDEN', 'Anda hanya bisa check-out untuk sesi absensi milik akun Anda sendiri.', 403);
            return;
        }

        if ($attendance['check_out_time']) {
            api_validation_error(['id' => 'Sudah melakukan check-out']);
            return;
        }

        $upd = [
            'check_out_time' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($in['notes'])) {
            $upd['notes'] = $attendance['notes']
                ? $attendance['notes'] . "
Check-out notes: " . $in['notes']
                : $in['notes'];
        }

        $this->AttendanceModel->update($id, $upd);

        $fresh = $this->AttendanceModel->find_by_id($id);
        api_ok($fresh);
    }

    public function manual_log(): void
    {
        $this->require_permission('app.services.security.attendance.manage');

        $in = $this->json_input();

        $security_guard_id = isset($in['security_guard_id']) ? (int)$in['security_guard_id'] : 0;
        $shift_id = isset($in['shift_id']) ? (int)$in['shift_id'] : null;
        $date = trim((string)($in['date'] ?? ''));
        $status = in_array($in['status'] ?? '', ['present', 'excused', 'absent', 'sick']) ? $in['status'] : 'absent';

        $err = [];
        if ($security_guard_id <= 0) {
            $err['security_guard_id'] = 'Security guard wajib dipilih';
        }
        if ($date === '') {
            $err['date'] = 'Tanggal wajib diisi';
        }

        if ($err) {
            api_validation_error($err);
            return;
        }

        $existing = $this->AttendanceModel->find_by_guard_and_date($security_guard_id, $date);
        if ($existing) {
            $upd = [
                'status' => $status,
                'shift_id' => $shift_id,
                'notes' => $in['notes'] ?? $existing['notes'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (isset($in['check_in_time'])) {
                $upd['check_in_time'] = $in['check_in_time'];
            }
            if (isset($in['check_out_time'])) {
                $upd['check_out_time'] = $in['check_out_time'];
            }

            $this->AttendanceModel->update($existing['id'], $upd);
            $fresh = $this->AttendanceModel->find_by_id($existing['id']);
            api_ok($fresh);
            return;
        }

        $id = $this->AttendanceModel->create([
            'security_guard_id' => $security_guard_id,
            'shift_id' => $shift_id,
            'date' => $date,
            'check_in_time' => $in['check_in_time'] ?? null,
            'check_out_time' => $in['check_out_time'] ?? null,
            'status' => $status,
            'notes' => $in['notes'] ?? null,
            'created_by' => $this->auth_user['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $attendance = $this->AttendanceModel->find_by_id($id);
        api_ok($attendance, null, 201);
    }

    public function destroy(int $id = 0): void
    {
        $this->require_permission('app.services.security.attendance.manage');

        if ($id <= 0) {
            api_not_found();
            return;
        }

        $attendance = $this->AttendanceModel->find_by_id($id);
        if (!$attendance) {
            api_not_found();
            return;
        }

        $this->AttendanceModel->delete($id);
        api_ok(['deleted' => true]);
    }

    public function summary(): void
    {
        $year = (int)($this->input->get('year') ?? date('Y'));
        $month = (int)($this->input->get('month') ?? date('m'));

        $guard_id = null;
        if (!$this->has_permission('app.services.security.attendance.manage')) {
            $guard = $this->current_guard();
            $guard_id = $guard ? (int)$guard['id'] : -1;
        }

        $items = $this->AttendanceModel->get_monthly_summary($year, $month, $guard_id);

        api_ok(['items' => $items, 'year' => $year, 'month' => $month]);
    }

    public function calendar(): void
    {
        $guard_id = (int)($this->input->get('security_guard_id') ?? 0);
        $year = (int)($this->input->get('year') ?? date('Y'));
        $month = (int)($this->input->get('month') ?? date('m'));

        if (!$this->has_permission('app.services.security.attendance.manage')) {
            $guard = $this->current_guard();
            $guard_id = $guard ? (int)$guard['id'] : -1;
        }

        $items = $this->AttendanceModel->get_calendar($year, $month, $guard_id);
        api_ok(['items' => $items, 'year' => $year, 'month' => $month]);
    }
}
