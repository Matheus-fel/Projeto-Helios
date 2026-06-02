<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/HistoricoChatModel.php';

class HistoricoChatController extends Controller {
    private HistoricoChatModel $model;

    public function __construct() {
        $this->model = new HistoricoChatModel();
    }

    /** GET /historico */
    public function index(): void {
        $limit = (int) ($this->param('limit') ?? 10);
        $this->success($this->model->recentes($limit));
    }

    /** GET /historico/{id} */
    public function show(int $id): void {
        $item = $this->model->find($id);
        $item ? $this->success($item) : $this->error('Registro não encontrado', 404);
    }

    /** POST /historico */
    public function store(): void {
        $data = $this->body();
        foreach (['usuario_id', 'pergunta_tecnica', 'resposta_ia'] as $f) {
            if (empty($data[$f])) $this->error("Campo obrigatório: $f");
        }
        $id = $this->model->registrar(
            (int) $data['usuario_id'],
            $data['pergunta_tecnica'],
            $data['resposta_ia'],
            $data['normas_relacionadas'] ?? ''
        );
        $this->success(['id' => $id], 'Interação registrada');
    }

    /** DELETE /historico/{id} */
    public function destroy(int $id): void {
        $this->model->delete($id)
            ? $this->success(null, 'Registro removido')
            : $this->error('Registro não encontrado', 404);
    }
}
