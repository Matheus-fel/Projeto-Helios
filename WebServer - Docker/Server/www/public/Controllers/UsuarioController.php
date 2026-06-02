<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class UsuarioController extends Controller {
    private UsuarioModel $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    /** GET /usuarios */
    public function index(): void {
        $usuarios = $this->model->all();
        // nunca expõe senha_hash na listagem
        foreach ($usuarios as &$u) unset($u['senha_hash']);
        $this->success($usuarios);
    }

    /** GET /usuarios/{id} */
    public function show(int $id): void {
        $usuario = $this->model->find($id);
        if (!$usuario) $this->error('Usuário não encontrado', 404);
        unset($usuario['senha_hash']);
        $this->success($usuario);
    }

    /** POST /usuarios */
    public function store(): void {
        $data = $this->body();
        foreach (['nome', 'email', 'senha', 'empresa_id'] as $f) {
            if (empty($data[$f])) $this->error("Campo obrigatório: $f");
        }
        if ($this->model->findByEmail($data['email'])) {
            $this->error('E-mail já cadastrado', 409);
        }
        $data['nivel_acesso'] = $data['nivel_acesso'] ?? 'operador';
        $id = $this->model->criar($data);
        $this->success(['id' => $id], 'Usuário criado');
    }

    /** PUT /usuarios/{id} */
    public function update(int $id): void {
        $data = $this->body();
        unset($data['id'], $data['senha_hash']);
        if (isset($data['senha'])) {
            $data['senha_hash'] = password_hash($data['senha'], PASSWORD_BCRYPT);
            unset($data['senha']);
        }
        $this->model->update($id, $data)
            ? $this->success(null, 'Usuário atualizado')
            : $this->error('Usuário não encontrado', 404);
    }

    /** DELETE /usuarios/{id} */
    public function destroy(int $id): void {
        $this->model->delete($id)
            ? $this->success(null, 'Usuário removido')
            : $this->error('Usuário não encontrado', 404);
    }

    /** POST /usuarios/login */
    public function login(): void {
        $data = $this->body();
        if (empty($data['email']) || empty($data['senha'])) {
            $this->error('E-mail e senha são obrigatórios');
        }
        $usuario = $this->model->autenticar($data['email'], $data['senha']);
        $usuario
            ? $this->success($usuario, 'Login realizado')
            : $this->error('Credenciais inválidas', 401);
    }

    /** GET /usuarios/{id}/historico */
    public function historico(int $id): void {
        $limit = (int) ($this->param('limit') ?? 20);
        $this->success($this->model->historico($id, $limit));
    }
}
