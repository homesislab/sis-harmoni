<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Security_attendance_model extends MY_Model
{
    private $table = 'security_attendances';

    public function get_list(int $page = 1, int $per_page = 20, ?string $date = null, ?string $status = null, ?int $guard_id = null): array
    {
        $offset = ($page - 1) * $per_page;

        $qb = $this->db->from("{$this->table} sa")
            ->join('security_guards sg', 'sg.id = sa.security_guard_id', 'left')
            ->join('security_shifts ss', 'ss.id = sa.shift_id', 'left');

        if ($date !== null && $date !== '') {
            $qb->where('sa.date', $date);
        }
        if ($status !== null && $status !== '') {
            $qb->where('sa.status', $status);
        }
        if ($guard_id !== null) {
            $qb->where('sa.security_guard_id', $guard_id);
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('sa.*, sg.employee_id, sg.full_name as guard_name, ss.name as shift_name, ss.start_time, ss.end_time')
            ->order_by('sa.date', 'DESC')
            ->order_by('sa.id', 'DESC')
            ->limit($per_page, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->select('sa.*, sg.employee_id, sg.full_name as guard_name, ss.name as shift_name')
            ->from("{$this->table} sa")
            ->join('security_guards sg', 'sg.id = sa.security_guard_id', 'left')
            ->join('security_shifts ss', 'ss.id = sa.shift_id', 'left')
            ->where('sa.id', $id)
            ->get()->row_array();

        return $row ?: null;
    }

    public function find_by_guard_and_date(int $guard_id, string $date): ?array
    {
        $row = $this->db->get_where($this->table, [
            'security_guard_id' => $guard_id,
            'date' => $date
        ])->row_array();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete(int $id): bool
    {
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function get_monthly_summary(int $year, int $month, ?int $guard_id = null): array
    {
        $this->db->select('
            sg.id as guard_id,
            sg.full_name as guard_name,
            sg.employee_id,
            COUNT(sa.id) as total_days,
            SUM(CASE WHEN sa.status = "present" THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN sa.status = "absent" THEN 1 ELSE 0 END) as total_absent,
            SUM(CASE WHEN sa.status = "sick" THEN 1 ELSE 0 END) as total_sick,
            SUM(CASE WHEN sa.status = "excused" THEN 1 ELSE 0 END) as total_excused
        ');
        $this->db->from('security_guards sg');
        $this->db->join("{$this->table} sa", "sa.security_guard_id = sg.id AND YEAR(sa.date) = {$year} AND MONTH(sa.date) = {$month}", 'left');
        
        if ($guard_id !== null) {
            $this->db->where('sg.id', $guard_id);
        }

        $this->db->group_by('sg.id');
        $this->db->order_by('sg.full_name', 'ASC');

        return $this->db->get()->result_array();
    }

    public function get_calendar_events(string $start_date, string $end_date, ?int $guard_id = null): array
    {
        $this->db->select('sa.*, sg.full_name as guard_name, ss.name as shift_name, ss.start_time, ss.end_time');
        $this->db->from("{$this->table} sa");
        $this->db->join('security_guards sg', 'sg.id = sa.security_guard_id', 'left');
        $this->db->join('security_shifts ss', 'ss.id = sa.shift_id', 'left');
        
        $this->db->where('sa.date >=', $start_date);
        $this->db->where('sa.date <=', $end_date);

        if ($guard_id !== null) {
            $this->db->where('sa.security_guard_id', $guard_id);
        }

        $this->db->order_by('sa.date', 'ASC');
        $this->db->order_by('sa.check_in_time', 'ASC');

        return $this->db->get()->result_array();
    }
}
