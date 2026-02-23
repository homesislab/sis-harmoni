<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Payment_model extends MY_Model
{
    protected string $table_name = 'payments';

    public function sum_allocated_for_invoice(int $invoice_id): float
    {
        $row = $this->db->select('COALESCE(SUM(allocated_amount),0) AS s', false)
            ->from('payment_invoice_allocations')
            ->where('invoice_id', $invoice_id)
            ->get()->row_array();
        return (float)($row['s'] ?? 0);
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where('payments', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->insert('payments', [
            'payer_household_id' => $data['payer_household_id'] ?? null,
            'ledger_entry_id'    => $data['ledger_entry_id'] ?? null,
            'amount'             => (float)($data['amount'] ?? 0),
            'paid_at'            => $data['paid_at'] ?? date('Y-m-d H:i:s'),
            'proof_file_url'     => $data['proof_file_url'] ?? null,
            'status'             => $data['status'] ?? 'pending',
            'note'               => $data['note'] ?? null,
            'verified_by'        => $data['verified_by'] ?? null,
            'verified_at'        => $data['verified_at'] ?? null,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    public function insert_intents(int $payment_id, array $invoice_ids): void
    {
        foreach ($invoice_ids as $invId) {
            $invId = (int)$invId;
            if ($invId <= 0) {
                continue;
            }

            $exists = $this->db->get_where('payment_invoice_intents', [
                'payment_id' => $payment_id,
                'invoice_id' => $invId,
            ])->row_array();

            if ($exists) {
                continue;
            }

            $this->db->insert('payment_invoice_intents', [
                'payment_id' => $payment_id,
                'invoice_id' => $invId,
                'intended_amount' => 0.00,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function list_intents(int $payment_id): array
    {
        return $this->db->from('payment_invoice_intents')
            ->where('payment_id', $payment_id)
            ->order_by('id', 'ASC')
            ->get()->result_array();
    }

    public function list_intent_invoices(int $payment_id): array
    {
        return $this->db->select('pii.invoice_id, pii.intended_amount, i.period, i.total_amount, i.status as invoice_status, ct.name as charge_name, ct.category as charge_category')
            ->from('payment_invoice_intents pii')
            ->join('invoices i', 'i.id=pii.invoice_id', 'left')
            ->join('charge_types ct', 'ct.id=i.charge_type_id', 'left')
            ->where('pii.payment_id', $payment_id)
            ->order_by('i.period', 'DESC')
            ->order_by('pii.id', 'ASC')
            ->get()->result_array();
    }

    public function approve(int $payment_id, int $verified_by, array $allocInvoices, array $allocComponents): void
    {
        $this->db->where('id', $payment_id)->update('payments', [
            'status' => 'approved',
            'verified_by' => $verified_by,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->where('payment_id', $payment_id)->delete('payment_invoice_allocations');
        foreach ($allocInvoices as $row) {
            $this->db->insert('payment_invoice_allocations', [
                'payment_id' => $payment_id,
                'invoice_id' => (int)($row['invoice_id'] ?? 0),
                'allocated_amount' => (float)($row['allocated_amount'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->where('payment_id', $payment_id)->delete('payment_component_allocations');
        foreach ($allocComponents as $row) {
            $this->db->insert('payment_component_allocations', [
                'payment_id' => $payment_id,
                'charge_component_id' => (int)($row['charge_component_id'] ?? 0),
                'allocated_amount' => (float)($row['allocated_amount'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function reject(int $id, int $verified_by, ?string $note = null): void
    {
        $this->db->where('id', $id)->update('payments', [
            'status' => 'rejected',
            'verified_by' => $verified_by,
            'verified_at' => date('Y-m-d H:i:s'),
            'note' => $note,
        ]);
    }

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;

        $countQ = $this->db
            ->from('payments p')
            ->join('households hh', 'hh.id = p.payer_household_id', 'left')
            ->join('persons hp', 'hp.id = hh.head_person_id', 'left')
            ->join("house_occupancies ho", "ho.household_id = hh.id AND ho.status = 'active'", 'left')
            ->join('houses h', 'h.id = ho.house_id', 'left');

        if (!empty($filters['status'])) {
            $countQ->where('p.status', (string)$filters['status']);
        }
        if (!empty($filters['payer_household_id'])) {
            $countQ->where('p.payer_household_id', (int)$filters['payer_household_id']);
        }

        $total = (int)$countQ->count_all_results();

        $itemsQ = $this->db->select("
            p.*,
            hh.id as household_id,
            h.id as house_id,
            h.block as house_block,
            h.number as house_number,
            hp.full_name as head_name,
            hp.phone as head_phone,
            (
            SELECT COUNT(*) FROM payment_invoice_intents pii
            WHERE pii.payment_id = p.id
            ) as intents_count
        ", false)
        ->from('payments p')
        ->join('households hh', 'hh.id = p.payer_household_id', 'left')
        ->join('persons hp', 'hp.id = hh.head_person_id', 'left')
        ->join("house_occupancies ho", "ho.household_id = hh.id AND ho.status = 'active'", 'left')
        ->join('houses h', 'h.id = ho.house_id', 'left');

        if (!empty($filters['status'])) {
            $itemsQ->where('p.status', (string)$filters['status']);
        }
        if (!empty($filters['payer_household_id'])) {
            $itemsQ->where('p.payer_household_id', (int)$filters['payer_household_id']);
        }

        $items = $itemsQ
            ->order_by('p.id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->result_array();

        return [
            'items' => $items,
            'meta'  => api_pagination_meta($page, $perPage, $total),
        ];
    }

    public function list_invoice_allocations(int $payment_id): array
    {
        return $this->db->get_where('payment_invoice_allocations', ['payment_id' => $payment_id])->result_array();
    }

    public function list_component_allocations(int $payment_id): array
    {
        return $this->db->get_where('payment_component_allocations', ['payment_id' => $payment_id])->result_array();
    }

    public function list_invoice_allocations_for_invoice(int $invoice_id): array
    {
        return $this->db->select('pia.*, p.status as payment_status, p.paid_at, p.amount as payment_amount')
            ->from('payment_invoice_allocations pia')
            ->join('payments p', 'p.id=pia.payment_id', 'left')
            ->where('pia.invoice_id', $invoice_id)
            ->order_by('p.id', 'DESC')
            ->get()->result_array();
    }

    public function list_component_allocations_for_invoice(int $invoice_id): array
    {
        return $this->db->select("
            pca.payment_id,
            pca.charge_component_id,
            cc.name as component_name,
            pca.allocated_amount,
            p.status as payment_status,
            p.paid_at
        ", false)
        ->from('payment_invoice_allocations pia')
        ->join('payments p', 'p.id=pia.payment_id', 'inner')
        ->join('payment_component_allocations pca', 'pca.payment_id=p.id', 'inner')
        ->join('charge_components cc', 'cc.id=pca.charge_component_id', 'left')
        ->where('pia.invoice_id', $invoice_id)
        ->order_by('p.id', 'DESC')
        ->order_by('pca.charge_component_id','ASC')
        ->get()->result_array();
    }
}
