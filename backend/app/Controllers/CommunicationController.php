<?php
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Services/CommunicationService.php';

class CommunicationController {
    private $service;

    public function __construct() {
        $this->service = new CommunicationService();
    }

    /* ===================== STORE NOTE ===================== */
    public function storeNote() {
        $data = $_REQUEST['decoded_input'] ?? $_POST;

        if (!isset($data['appointment_id']) || !isset($data['content'])) {
            Response::json(['error' => 'appointment_id and content are required'], 400);
            return;
        }

        try {
            $id = $this->service->addNote($data);
            Response::json(['message' => 'Note added successfully', 'id' => $id], 201);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /* ===================== GET NOTES BY APPOINTMENT ===================== */
    public function getNotesByAppointment($id) {
        try {
            $notes = $this->service->getNotesByAppointment($id);
            Response::json($notes);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /* ===================== GET MESSAGE HISTORY ===================== */
    public function getHistory() {
        try {
            $history = $this->service->getMessageHistory();
            Response::json($history);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
