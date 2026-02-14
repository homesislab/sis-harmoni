<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_model extends CI_Model
{
    private string $table = 'invoices';

    public function find_by_household_charge_period(int $household_id, int $charge_type_id, string $period): ?array
    {
        $row = $this->db->get_where('invoices', [
            'household_id' => $household_id,
            'charge_type_id' => $charge_type_id,
            'period' => $period,
        ])->row_array();
        return $row ?: null;
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->select("
            i.*,
            ct.name as charge_name,
            ct.category as charge_category,
            p.full_name as head_name,
            CONCAT(h.block, '-', h.number) as house_code
        ", false)
        ->from('invoices i')
        ->join('charge_types ct','ct.id=i.charge_type_id','left')
        ->join('households hh','hh.id=i.household_id','left')
        ->join('persons p','p.id=hh.head_person_id','left')
        ->join('house_occupancies ho','ho.household_id = i.household_id AND ho.status = "active"','left')
        ->join('houses h','h.id = ho.house_id','left')
        ->where('i.id', $id)
        ->get()->row_array();

        return $row ?: null;
    }

    public function find_by_household_and_id(int $household_id, int $invoice_id): ?array
    {
        $row = $this->db->select("
            i.*,
            ct.name as charge_name,
            ct.category as charge_category,
            p.full_name as head_name,
            CONCAT(h.block, '-', h.number) as house_code
        ", false)
        ->from('invoices i')
        ->join('charge_types ct','ct.id=i.charge_type_id','left')
        ->join('households hh','hh.id=i.household_id','left')
        ->join('persons p','p.id=hh.head_person_id','left')
        ->join('house_occupancies ho','ho.household_id = i.household_id AND ho.status = "active"','left')
        ->join('houses h','h.id = ho.house_id','left')
        ->where('i.id', $invoice_id)
        ->where('i.household_id', $household_id)
        ->get()->row_array();

        return $row ?: null;
    }

    public function list_by_household_and_ids(int $household_id, array $ids): array
    {
        $ids = array_values(array_unique(array_map(function($x){ return (int)$x; }, $ids)));
        $ids = array_values(array_filter($ids, fn($v)=>$v>0));
        if (empty($ids)) return [];

        return $this->db->select('i.*, ct.name as charge_name, ct.category as charge_category')
            ->from('invoices i')
            ->join('charge_types ct','ct.id=i.charge_type_id','left')
            ->where('i.household_id', $household_id)
            ->where_in('i.id', $ids)
            ->get()->result_array();
    }

    public function paginate_for_household(int $household_id, int $page, int $per, array $filters = []): array
    {
        $offset = ($page - 1) * $per;

        $q = $this->db->select("
                i.*,
                ct.name as charge_name,
                ct.category as charge_category,
                MAX(CASE WHEN p.status='pending' THEN p.id ELSE NULL END) as pending_payment_id,
                SUM(CASE WHEN p.status='pending' THEN 1 ELSE 0 END) as pending_payment_count,
                SUBSTRING_INDEX(GROUP_CONCAT(p.status ORDER BY p.id DESC), ',', 1) as last_payment_status,
                SUBSTRING_INDEX(GROUP_CONCAT(p.note ORDER BY p.id DESC SEPARATOR '||'), '||', 1) as last_payment_note
            ", false)
            ->from('invoices i')
            ->join('charge_types ct','ct.id=i.charge_type_id','left')
            ->join('payment_invoice_intents pii','pii.invoice_id=i.id','left')
            ->join('payments p','p.id=pii.payment_id','left')
            ->where('i.household_id', $household_id)
            ->group_by('i.id');

        if (!empty($filters['status'])) {
            $st = (string)$filters['status'];
            if ($st === 'paid') {
                $q->where_in('i.status', ['paid','void']);
            } elseif ($st === 'unpaid') {
                $q->where_not_in('i.status', ['paid','void']);
            } else {
                $q->where('i.status', $st);
            }
        }

        if (!empty($filters['period'])) {
            $q->where('i.period', (string)$filters['period']);
        }

        if (!empty($filters['period_like'])) {
            $q->like('i.period', (string)$filters['period_like'], 'after'); // "2026-"
        }

        if (!empty($filters['charge_type_id'])) {
            $q->where('i.charge_type_id', (int)$filters['charge_type_id']);
        }

        if (!empty($filters['q'])) {
            $s = trim((string)$filters['q']);
            if ($s !== '') {
                $q->group_start();
                $q->like('ct.name', $s);
                $q->or_like('i.period', $s);
                $q->or_like('i.id', $s);
                $q->group_end();
            }
        }

        $totalQ = clone $q;
        $total = (int)$totalQ->count_all_results('', false);

        $items = $q->order_by('i.period','DESC')->order_by('i.id','DESC')
            ->limit($per, $offset)
            ->get()->result_array();

        return ['items'=>$items,'meta'=>api_pagination_meta($page,$per,$total)];
    }

    public function paginate(int $page, int $per, array $filters = []): array
    {
        $offset = ($page - 1) * $per;

        $q = $this->db->select("
            i.*,
            ct.name as charge_name,
            ct.category as charge_category,
            p.full_name as head_name,
            CONCAT(h.block, '-', h.number) as house_code
        ", false)
        ->from('invoices i')
        ->join('charge_types ct','ct.id=i.charge_type_id','left')
        ->join('households hh','hh.id=i.household_id','left')
        ->join('persons p','p.id=hh.head_person_id','left')
        ->join('house_occupancies ho','ho.household_id = i.household_id AND ho.status = "active"','left')
        ->join('houses h','h.id = ho.house_id','left');

        if (!empty($filters['household_id'])) $q->where('i.household_id', (int)$filters['household_id']);
        if (!empty($filters['status'])) $q->where('i.status', $filters['status']);
        if (!empty($filters['period'])) $q->where('i.period', $filters['period']);
        if (!empty($filters['category'])) $q->where('ct.category', $filters['category']);
        if (!empty($filters['charge_type_id'])) $q->where('i.charge_type_id', (int)$filters['charge_type_id']);

        $totalQ = clone $q;
        $total = (int)$totalQ->count_all_results('', false);

        $items = $q->order_by('i.period','DESC')->order_by('i.id','DESC')
            ->limit($per,$offset)->get()->result_array();

        return ['items'=>$items,'meta'=>api_pagination_meta($page,$per,$total)];
    }

    public function create(array $data): int
    {
        $this->db->insert('invoices', [
            'household_id' => (int)$data['household_id'],
            'charge_type_id' => (int)$data['charge_type_id'],
            'period' => (string)$data['period'],
            'total_amount' => (float)$data['total_amount'],
            'status' => $data['status'] ?? 'unpaid',
            'note' => $data['note'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_status(int $id, string $status): void
    {
        $allowed = ['unpaid','partial','paid','void'];
        if (!in_array($status, $allowed, true)) return;
        $this->db->where('id',$id)->update('invoices', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['household_id','charge_type_id','period','total_amount','status','note'];
        $upd = [];
        foreach ($allowed as $k) if (array_key_exists($k,$data)) $upd[$k] = $data[$k];
        if (!$upd) return;
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id',$id)->update('invoices',$upd);
    }

    public function delete(int $id): void
    {
        $this->db->where('id',$id)->delete('invoices');
    }

    public function list_lines(int $invoice_id): array
    {
        return $this->db->get_where('invoice_lines',['invoice_id'=>$invoice_id])->result_array();
    }

    public function add_line(int $invoice_id, array $line): int
    {
        $this->db->insert('invoice_lines', [
            'invoice_id' => $invoice_id,
            'house_id' => $line['house_id'] ?? null,
            'line_type' => $line['line_type'] ?? 'other',
            'description' => (string)($line['description'] ?? ''),
            'qty' => (float)($line['qty'] ?? 1),
            'unit_price' => (float)($line['unit_price'] ?? 0),
            'amount' => (float)($line['amount'] ?? 0),
            'sort_order' => (int)($line['sort_order'] ?? 1),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    public function validate_payload(array $in, bool $is_create): array
    {
        $err = [];
        $req = ['household_id','charge_type_id','period','total_amount'];
        if ($is_create) {
            foreach ($req as $k) if (!isset($in[$k]) || $in[$k]==='') $err[$k]='wajib';
        }
        if (isset($in['household_id']) && (int)$in['household_id']<=0) $err['household_id']='harus > 0';
        if (isset($in['charge_type_id']) && (int)$in['charge_type_id']<=0) $err['charge_type_id']='harus > 0';
        if (isset($in['period']) && !preg_match('/^\d{4}-\d{2}$/',(string)$in['period'])) $err['period']='format YYYY-MM';
        if (isset($in['total_amount']) && (float)$in['total_amount']<0) $err['total_amount']='>= 0';
        return $err;
    }
}
