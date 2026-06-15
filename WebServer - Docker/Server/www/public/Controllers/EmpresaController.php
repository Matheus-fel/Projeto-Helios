<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/EmpresaModel.php';

class EmpresaController extends Controller {
    private EmpresaModel $model;

    public function __construct() {
        $this->model = new EmpresaModel();
    }

    /** GET /empresas */
    public function index(): void {
        $this->success($this->model->all());
    }

    /** GET /empresas/{id} */
    public function show(int $id): void {
        $empresa = $this->model->find($id);
        $empresa ? $this->success($empresa) : $this->error('Empresa não encontrada', 404);
    }

    /** POST /empresas */
    public function store(): void {
        $data = $this->body();
        $required = ['nome_comercial', 'cnpj'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Campo obrigatório: $field");
            }
        }
        $data['data_adesao'] = $data['data_adesao'] ?? date('Y-m-d H:i:s');

        if (empty($data['codigo_acesso'])) {
            $data['codigo_acesso'] = $this->gerarCodigoAcesso();
        }

        $id = $this->model->create($data);
        $empresa = $this->model->find($id);
        $this->success($empresa, 'Empresa criada');
    }

    /** PUT /empresas/{id} */
    public function update(int $id): void {
        $data = $this->body();
        unset($data['id']);
        $this->model->update($id, $data)
            ? $this->success(null, 'Empresa atualizada')
            : $this->error('Empresa não encontrada', 404);
    }

    /** DELETE /empresas/{id} */
    public function destroy(int $id): void {
        $this->model->delete($id)
            ? $this->success(null, 'Empresa removida')
            : $this->error('Empresa não encontrada', 404);
    }

    /** GET /empresas/{id}/usinas */
    public function usinas(int $id): void {
        $this->success($this->model->usinas($id));
    }

    /** GET /empresas/{id}/usuarios */
    public function usuarios(int $id): void {
        $this->success($this->model->usuarios($id));
    }

    /** Gera um código de acesso único (6 caracteres alfanuméricos) */
    private function gerarCodigoAcesso(): string {
        do {
            $codigo = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->model->findByCodigo($codigo));
        return $codigo;
    }
}