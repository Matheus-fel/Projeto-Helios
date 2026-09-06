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
        if (!$usuario) $this->error('Usuario nao encontrado', 404);
        unset($usuario['senha_hash']);
        $this->success($usuario);
    }
 
    public function store(): void {
        $data = $this->body();

        // Validação dos campos obrigatórios
        foreach (['nome', 'email', 'senha', 'codigo_acesso'] as $f) {
            if (empty($data[$f])) {
                $this->error("Campo obrigatório: $f", 422);
            }
        }

        if ($this->model->findByEmail($data['email'])) {
            $this->error('E-mail já cadastrado', 409);
        }

        // Valida o código da empresa
        require_once __DIR__ . '/../models/EmpresaModel.php';
        $empresaModel = new EmpresaModel();
        $empresa = $empresaModel->findByCodigo(trim($data['codigo_acesso']));

        if (!$empresa) {
            $this->error('Código de acesso inválido. Empresa não encontrada.', 404);
        }

        // Define o nível de acesso fixo como operador e associa a empresa
        $data['nivel_acesso'] = 'operador';
        $data['empresa_id']   = (int) $empresa['id'];

        // Remove campo auxiliar que não pertence à tabela do banco
        unset($data['codigo_acesso']);

        // Criação do usuário
        $id = $this->model->criar($data);
        
        $resposta = [
            'id'           => $id,
            'nome'         => $data['nome'],
            'email'        => $data['email'],
            'nivel_acesso' => $data['nivel_acesso'],
            'empresa_id'   => $data['empresa_id']
        ];
        
        $this->success($resposta, 'Usuário criado com sucesso', 201);
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
        
        // Validação do Nível de Acesso
        if (isset($data['nivel_acesso'])) {
            $niveisValidos = ['operador', 'gerente', 'admin'];
            if (!in_array($data['nivel_acesso'], $niveisValidos, true)) {
                $this->error('Nível de acesso inválido. Escolha entre: operador, gerente ou admin.', 422);
            }

            // Se for promovido a admin, ajusta empresa_id se necessário
            if ($data['nivel_acesso'] === 'admin' && array_key_exists('empresa_id', $data) && empty($data['empresa_id'])) {
                $data['empresa_id'] = null;
            }
        }

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