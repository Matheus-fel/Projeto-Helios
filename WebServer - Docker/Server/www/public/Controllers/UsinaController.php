<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/UsinaModel.php';

class UsinaController extends Controller {
    private UsinaModel $model;

    public function __construct() {
        $this->model = new UsinaModel();
    }

    /** GET /usinas */
    public function index(): void {
        $this->success($this->model->all());
    }

    /** GET /usinas/{id} */
    public function show(int $id): void {
        $usina = $this->model->find($id);
        $usina ? $this->success($usina) : $this->error('Usina não encontrada', 404);
    }

    /** POST /usinas */
    public function store(): void {
        $data = $this->body();
        foreach (['nome_usina', 'empresa_id', 'tipo_geracao'] as $f) {
            if (empty($data[$f])) $this->error("Campo obrigatório: $f");
        }
        $id = $this->model->create($data);
        $this->success(['id' => $id], 'Usina criada');
    }

    /** PUT /usinas/{id} */
    public function update(int $id): void {
        $data = $this->body();
        unset($data['id']);
        $this->model->update($id, $data)
            ? $this->success(null, 'Usina atualizada')
            : $this->error('Usina não encontrada', 404);
    }

    /** DELETE /usinas/{id} */
    public function destroy(int $id): void {
        $this->model->delete($id)
            ? $this->success(null, 'Usina removida')
            : $this->error('Usina não encontrada', 404);
    }

    /** GET /usinas/{id}/telemetria */
    public function telemetria(int $id): void {
        $limit = (int) ($this->param('limit') ?? 50);
        $this->success($this->model->telemetria($id, $limit));
    }

    /** GET /usinas/{id}/telemetria/filtro?parametro=X&de=Y&ate=Z */
    public function telemetriaFiltrada(int $id): void {
        $parametro = $this->param('parametro');
        $de        = $this->param('de');
        $ate       = $this->param('ate');
        if (!$parametro || !$de || !$ate) {
            $this->error('Informe: parametro, de, ate');
        }
        $this->success($this->model->telemetriaFiltrada($id, $parametro, $de, $ate));
    }

    /** GET /usinas/{id}/telemetria/media?parametro=X */
    public function mediaHoraria(int $id): void {
        $parametro = $this->param('parametro');
        if (!$parametro) $this->error('Informe o parâmetro');
        $this->success($this->model->mediaHoraria($id, $parametro));
    }
}
