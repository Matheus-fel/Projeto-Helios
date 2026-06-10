<?php
 
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
 
class UsuarioController extends Controller {
    private UsuarioModel $model;
 
    public function __construct() {
        $this->model = new UsuarioModel();
    }
 
    public function index(): void {
        $usuarios = $this->model->all();
        foreach ($usuarios as &$u) unset($u['senha_hash']);
        $this->success($usuarios);
    }
 
    public function show(int $id): void {
        $usuario = $this->model->find($id);
        if (!$usuario) $this->error('Usuário não encontrado', 404);
        unset($usuario['senha_hash']);
        $this->success($usuario);
    }
 
    public function store(): void {
        $data = $this->body();
 
        foreach (['nome', 'email', 'senha'] as $f) {
            if (empty($data[$f])) $this->error("Campo obrigatório: $f", 422);
        }
 
        if ($this->model->findByEmail($data['email'])) {
            $this->error('E-mail já cadastrado', 409);
        }
 
        $data['nivel_acesso'] = $data['nivel_acesso'] ?? 'operador';
        $data['empresa_id']   = !empty($data['empresa_id']) ? (int) $data['empresa_id'] : null;
 
        $id = $this->model->criar($data);
        // retorna o usuário criado para o front poder fazer login automático
        $usuario = $this->model->find($id);
        unset($usuario['senha_hash']);
        $this->success($usuario, 'Usuário criado', 201);
    }
 
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
 
    public function destroy(int $id): void {
        $this->model->delete($id)
            ? $this->success(null, 'Usuário removido')
            : $this->error('Usuário não encontrado', 404);
    }
 
    public function login(): void {
        $data = $this->body();
        if (empty($data['email']) || empty($data['senha'])) {
            $this->error('E-mail e senha são obrigatórios', 422);
        }
        $usuario = $this->model->autenticar($data['email'], $data['senha']);
        $usuario
            ? $this->success($usuario, 'Login realizado')
            : $this->error('Credenciais inválidas', 401);
    }
 
    public function historico(int $id): void {
        $limit = (int) ($this->param('limit') ?? 20);
        $this->success($this->model->historico($id, $limit));
    }
}
 