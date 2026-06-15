<?php
 
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
 
class UsuarioController extends Controller {
    private UsuarioModel $model;

    /** Chave master fixa exigida para cadastro de administradores. Troque por uma chave forte/secreta. */
    private const ADMIN_MASTER_KEY = 'h7lio_adm';
 
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
        if (!$usuario) $this->error('Usuario nao encontrado', 404);
        unset($usuario['senha_hash']);
        $this->success($usuario);
    }
 
    public function store(): void {
        $data = $this->body();
 
        // Validação dos campos obrigatórios globais
        foreach (['nome', 'email', 'senha', 'nivel_acesso'] as $f) {
            if (empty($data[$f])) $this->error("Campo obrigatorio: $f", 422);
        }
 
        if ($this->model->findByEmail($data['email'])) {
            $this->error('E-mail ja cadastrado', 409);
        }
 
        // Validação do código de vinculação da empresa
        if ($data['nivel_acesso'] === 'admin') {
            // Cadastro de admin exige chave master fixa (definida no topo desta classe)
            if (empty($data['chave_admin']) || !hash_equals(self::ADMIN_MASTER_KEY, (string) $data['chave_admin'])) {
                $this->error('Chave master invalida para cadastro de administrador.', 403);
            }

            $data['empresa_id'] = null;
        } else {
            // Gestores e Operadores PRECISAM fornecer o código de uma empresa ativa
            if (empty($data['codigo_acesso'])) {
                $this->error('Codigo de acesso da empresa e obrigatorio para este nivel.', 422);
            }

            // Instancia o EmpresaModel pra validar o código de convite
            require_once __DIR__ . '/../models/EmpresaModel.php';
            $empresaModel = new EmpresaModel();
            $empresa = $empresaModel->findByCodigo(trim($data['codigo_acesso']));

            if (!$empresa) {
                $this->error('Codigo de acesso invalido. Empresa nao encontrada.', 404);
            }

            // Amarra o ID da empresa encontrada no payload do novo usuário
            $data['empresa_id'] = (int) $empresa['id'];
        }
 
        // Remove campos auxiliares pra que não tentem ser inseridos na tabela usuarios
        unset($data['codigo_acesso'], $data['chave_admin']);
 
        // 3. Criação do usuário repassando o array com o empresa_id correto
        $id = $this->model->criar($data);
        $usuario = $this->model->find($id);
        unset($usuario['senha_hash']);
        $this->success($usuario, 'Usuario criado', 201);
    }
 
    public function update(int $id = 0): void {
        $data = $this->body();
        
        // Se a classe base nao conseguiu mapear o JSON, lê diretamente do input bruto
        if (empty($data)) {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?? [];
        }
        
        $userId = 0;
        if ($id > 0) {
            $userId = $id;
        } elseif (!empty($data['id'])) {
            $userId = (int)$data['id'];
        } elseif (!empty($data['usuario_id'])) {
            $userId = (int)$data['usuario_id'];
        }
        
        if ($userId === 0) {
            $this->error('ID do usuario nao fornecido', 422);
        }
 
        unset($data['id'], $data['usuario_id'], $data['senha_hash']);
        
        if (isset($data['senha'])) {
            $data['senha_hash'] = password_hash($data['senha'], PASSWORD_BCRYPT);
            unset($data['senha']);
        }
 
        if ($this->model->update($userId, $data)) {
            $usuarioAtualizado = $this->model->find($userId);
            if ($usuarioAtualizado) {
                unset($usuarioAtualizado['senha_hash']);
            }
            $this->success($usuarioAtualizado, 'Usuario atualizado');
        } else {
            $usuarioAtualizado = $this->model->find($userId);
            if ($usuarioAtualizado) {
                unset($usuarioAtualizado['senha_hash']);
                $this->success($usuarioAtualizado, 'Dados mantidos');
            } else {
                $this->error('Usuario nao encontrado', 404);
            }
        }
    }
 
    public function destroy(int $id = 0): void {
        $data = $this->body();
        
        if (empty($data)) {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?? [];
        }
        
        $userId = 0;
        if ($id > 0) {
            $userId = $id;
        } elseif (!empty($data['id'])) {
            $userId = (int)$data['id'];
        } elseif (!empty($data['usuario_id'])) {
            $userId = (int)$data['usuario_id'];
        }
 
        if ($userId === 0) {
            $this->error('ID do usuario nao fornecido', 422);
        }
 
        $this->model->delete($userId)
            ? $this->success(null, 'Usuario removido')
            : $this->error('Usuario nao encontrado', 404);
    }
 
    public function login(): void {
        $data = $this->body();
        if (empty($data['email']) || empty($data['senha'])) {
            $this->error('E-mail e senha sao obrigatorios', 422);
        }
        $usuario = $this->model->autenticar($data['email'], $data['senha']);
        $usuario
            ? $this->success($usuario, 'Login realizado')
            : $this->error('Credenciais invalidas', 401);
    }
 
    public function historico(int $id): void {
        $limit = (int) ($this->param('limit') ?? 20);
        $this->success($this->model->historico($id, $limit));
    }
}