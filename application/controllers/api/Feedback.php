<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Feedback extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Feedback_category_model', 'CategoryModel');
        $this->load->model('Feedback_model', 'FeedbackModel');
        $this->load->model('Feedback_response_model', 'ResponseModel');
        $this->load->library('whatsapp');
    }

    public function index(): void
    {
        $this->require_any_permission(['app.services.info.feedback.view']);
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];
        $filters = [
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'q' => $this->input->get('q') ? (string)$this->input->get('q') : null,
        ];
        $res = $this->FeedbackModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_any_permission(['app.services.resident.feedback.create']);
        $in = $this->json_input();
        $err = [];
        if (empty($in['title'])) {
            $err['title'] = 'Wajib diisi';
        }
        if (empty($in['message'])) {
            $err['message'] = 'Wajib diisi';
        }
        if ($err) {
            api_validation_error($err);
            return;
        }

        $payload = $in;
        $payload['created_by'] = (int)$this->auth_user['id'];
        $payload['person_id'] = (int)($this->auth_user['person_id'] ?? 0) ?: null;
        if (empty($payload['house_id']) && !empty($this->auth_house_id)) {
            $payload['house_id'] = (int)$this->auth_house_id;
        }

        $id = $this->FeedbackModel->create($payload);

        $row = $this->FeedbackModel->find_by_id($id);
        $row['responses'] = [];

        // Send WA Notification
        $admin_wa = $this->whatsapp->get_group_pengurus();
        if ($admin_wa) {
            $person_id = $payload['person_id'] ?? 0;
            $nama = 'Warga';
            if ($person_id) {
                $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
                if ($person) {
                    $nama = $person['full_name'];
                }
            }
            $cat = $row['category_name'] ?? 'Laporan';
            $title = $row['title'] ?? '';
            $wa_msg = "*[Info SIS]*\n\nAssalamu'alaikum, Admin.\n\n📢 *Laporan Warga Baru!*\n\nAda pesan baru dari *{$nama}* mengenai *{$cat}*:\n_{$title}_\n\nMohon dicek di aplikasi atau dashboard admin untuk tindak lanjut ya.";
            $this->whatsapp->send_message($admin_wa, $wa_msg);
        }

        api_ok($row, null, 201);
    }

    public function show(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.feedback.view']);
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $fb = $this->FeedbackModel->find_by_id($id);
        if (!$fb) {
            api_not_found();
            return;
        }

        $fb['responses'] = $this->ResponseModel->list_for_feedback($id, true);
        api_ok(['feedback' => $fb]);
    }

    public function respond(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.feedback.respond']);
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $fb = $this->FeedbackModel->find_by_id($id);
        if (!$fb) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        if (empty($in['message'])) {
            api_validation_error(['message' => 'Wajib diisi']);
            return;
        }

        $this->ResponseModel->create([
            'feedback_id' => $id,
            'responder_id' => (int)$this->auth_user['id'],
            'message' => (string)$in['message'],
            'is_public' => isset($in['is_public']) ? (int)$in['is_public'] : 1,
        ]);

        $this->FeedbackModel->update($id, ['status' => 'responded']);

        $fb = $this->FeedbackModel->find_by_id($id);
        $fb['responses'] = $this->ResponseModel->list_for_feedback($id, true);

        // Send WA Notification
        $person_id = (int)($fb['person_id'] ?? 0);
        if ($person_id) {
            $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
            if ($person && !empty($person['phone'])) {
                $nama = $person['full_name'] ?? 'Warga';
                $cat = $fb['category_name'] ?? 'Laporan';
                $msg_text = $in['message'] ?? '';
                $wa_msg = "*[Info SIS]*\n\nAssalamu'alaikum, *{$nama}*,\n\n💬 Ada tanggapan dari pengurus terkait laporan Anda tentang *{$cat}*:\n_{$msg_text}_\n\nSilakan cek aplikasi untuk detailnya ya.";
                $this->whatsapp->send_message($person['phone'], $wa_msg);
            }
        }

        api_ok($fb);
    }

    public function close(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.feedback.respond']);
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $fb = $this->FeedbackModel->find_by_id($id);
        if (!$fb) {
            api_not_found();
            return;
        }

        $this->FeedbackModel->update($id, [
            'status' => 'closed',
            'closed_by' => (int)$this->auth_user['id'],
            'closed_at' => date('Y-m-d H:i:s'),
        ]);

        $fb = $this->FeedbackModel->find_by_id($id);
        $fb['responses'] = $this->ResponseModel->list_for_feedback($id, true);

        // Send WA Notification
        $person_id = (int)($fb['person_id'] ?? 0);
        if ($person_id) {
            $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
            if ($person && !empty($person['phone'])) {
                $nama = $person['full_name'] ?? 'Warga';
                $cat = $fb['category_name'] ?? 'Laporan';
                $wa_msg = "*[Info SIS]*\n\nAssalamu'alaikum, *{$nama}*,\n\n✅ Laporan Anda mengenai *{$cat}* sudah *DISELESAIKAN/DITUTUP* oleh pengurus.\nTerima kasih banyak atas laporannya.";
                $this->whatsapp->send_message($person['phone'], $wa_msg);
            }
        }

        api_ok($fb);
    }
}
