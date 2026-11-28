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
 
    /**
     * Cadastra um novo usuário no sistema.
     * Suporta dois fluxos:
     * 1. Auto-registro público (sem token JWT, exige codigo_acesso da empresa).
     * 2. Onboarding direto pelo Gerente (com token JWT, vincula obrigatoriamente ao empresa_id do gerente).
     */
    public function store(): void {
$data = $this->body();
        if (empty($data)) {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?? [];
        }

        // Se codigo_acesso foi informado, trata como auto-registro público;
        // caso contrário, verifica o token JWT para o fluxo de onboarding direto pelo Gerente.
        $hasCodigoAcesso = !empty(trim((string) ($data['codigo_acesso'] ?? '')));
        $payload = $hasCodigoAcesso ? null : $this->getAuthPayload(false);

        if ($payload !== null) {
            // Apenas gerentes podem cadastrar colaboradores diretamente pela aba de Equipe
            if (($payload->nivel_acesso ?? '') !== 'gerente') {
                $this->error('Apenas gerentes vinculados a uma empresa podem cadastrar colaboradores diretamente.', 403);
            }

            // Busca o gerente no banco para obter o empresa_id real e atualizado
            $gerente = $this->model->find((int) $payload->sub);
            if (!$gerente || empty($gerente['empresa_id'])) {
                $this->error('Seu perfil de gerente não está vinculado a nenhuma empresa.', 403);
            }

            // Validação dos campos obrigatórios do onboarding direto
            foreach (['nome', 'email', 'senha'] as $f) {
                if (empty(trim((string) ($data[$f] ?? '')))) {
                    $this->error("Campo obrigatório: $f", 422);
                }
            }

            $data['nome']  = trim((string) $data['nome']);
            $data['email'] = trim((string) $data['email']);

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->error('E-mail inválido.', 422);
            }

            if (strlen((string) $data['senha']) < 6) {
                $this->error('A senha inicial deve ter no mínimo 6 caracteres.', 422);
            }

            if ($this->model->findByEmail($data['email'])) {
                $this->error('E-mail já cadastrado', 409);
            }

            // Higieniza e valida o telefone (opcional; se informado, exige 10 ou 11 dígitos)
            if (array_key_exists('telefone', $data)) {
                $data['telefone'] = $this->normalizarTelefone($data['telefone']);
            }

            // Higieniza o cargo/função inicial (opcional)
            if (array_key_exists('cargo_funcao', $data)) {
                $cargo = trim((string) $data['cargo_funcao']);
                if (mb_strlen($cargo) > 500) {
                    $this->error('O campo Cargo / Função deve ter no máximo 500 caracteres.', 422);
                }
                $data['cargo_funcao'] = $cargo !== '' ? $cargo : null;
            }

            // Força o nível como operador e vincula obrigatoriamente à empresa do gerente logado
            $data['nivel_acesso'] = 'operador';
            $data['empresa_id']   = (int) $gerente['empresa_id'];
            unset($data['codigo_acesso']);
        } else {
            // ── Fluxo público de auto-registro via código de convite ──
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
        }

        // Criação do usuário
        $id = $this->model->criar($data);

        // No auto-registro público ($payload === null), retorna dados completos + token JWT para login automático
        if ($payload === null) {
            $usuarioCriado = $this->model->find($id);
            if ($usuarioCriado) {
                unset($usuarioCriado['senha_hash']);

                require_once __DIR__ . '/../JwtHandler.php';
                $usuarioCriado['token'] = JwtHandler::gerarToken([
                    'id'           => $usuarioCriado['id'],
                    'email'        => $usuarioCriado['email'],
                    'nivel_acesso' => $usuarioCriado['nivel_acesso']
                ]);

                $this->success($usuarioCriado, 'Usuário criado com sucesso', 201);
                return;
            }
        }
        
        $resposta = [
            'id'           => $id,
            'nome'         => $data['nome'],
            'email'        => $data['email'],
            'cargo_funcao' => $data['cargo_funcao'] ?? null,
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

        // Busca os dados atuais do usuário alvo antes de aplicar regras de governança
        $usuarioAtual = $this->model->find($userId);
        if (!$usuarioAtual) {
            $this->error('Usuario nao encontrado', 404);
        }

        // Valida regras de governança e hierarquia conforme o perfil autenticado no JWT
        $payload = $this->getAuthPayload(false);
        if ($payload !== null) {
            $requesterRole = $payload->nivel_acesso ?? '';
            $requesterId   = (int) $payload->sub;

            // Operadores só podem editar o próprio perfil e não podem alterar Cargo / Função
            if ($requesterRole === 'operador') {
                if ($userId !== $requesterId) {
                    $this->error('Operadores só têm permissão para editar o próprio perfil.', 403);
                }

                if (array_key_exists('cargo_funcao', $data)) {
                    $novoCargo  = trim((string) ($data['cargo_funcao'] ?? ''));
                    $cargoAtual = trim((string) ($usuarioAtual['cargo_funcao'] ?? ''));

                    // Bloqueia tentativa de alteração do cargo oficial; se veio inalterado, apenas ignora o campo
                    if ($novoCargo !== $cargoAtual) {
                        $this->error('Operadores não têm permissão para alterar o próprio Cargo / Função.', 403);
                    }
                    unset($data['cargo_funcao']);
                }

                if (isset($data['nivel_acesso']) && $data['nivel_acesso'] !== $usuarioAtual['nivel_acesso']) {
                    $this->error('Operadores não têm permissão para alterar o nível de acesso.', 403);
                }

                unset($data['nivel_acesso'], $data['empresa_id']);
            }

            // Valida se um gerente está tentando editar outro usuário fora da sua empresa
            if ($requesterRole === 'gerente' && $userId !== $requesterId) {
                $gerente = $this->model->find($requesterId);

                if (!$gerente || (int) $usuarioAtual['empresa_id'] !== (int) $gerente['empresa_id'] || $usuarioAtual['nivel_acesso'] === 'admin') {
                    $this->error('Você só tem permissão para editar colaboradores da sua própria empresa.', 403);
                }
                if (isset($data['nivel_acesso']) && $data['nivel_acesso'] === 'admin') {
                    $this->error('Gerentes não podem atribuir nível de acesso admin.', 403);
                }
            }
        }

        // Remove IDs, hashes e colunas virtuais do JOIN que não pertencem diretamente à tabela usuarios
        unset(
            $data['id'], 
            $data['usuario_id'], 
            $data['senha_hash'],
            $data['empresa_nome'],
            $data['empresa_cnpj'],
            $data['empresa_codigo_acesso']
        );

        // Tratamento e higienização dos novos campos de dados pessoais
        if (array_key_exists('telefone', $data)) {
            $data['telefone'] = $this->normalizarTelefone($data['telefone']);
        }

        if (array_key_exists('cargo_funcao', $data)) {
            $cargo = trim((string)($data['cargo_funcao'] ?? ''));
            if (mb_strlen($cargo) > 500) {
                $this->error('O campo Cargo / Função deve ter no máximo 500 caracteres.', 422);
            }
            $data['cargo_funcao'] = $cargo !== '' ? $cargo : null;
        }
        
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

    /**
     * Lê e valida o token JWT do cabeçalho Authorization, caso enviado.
     * Se um token for enviado mas estiver inválido/expirado, encerra com HTTP 401.
     */
    private function getAuthPayload(bool $obrigatorio = false): ?object {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            if ($obrigatorio) {
                $this->error('Token de autenticação não fornecido.', 401);
            }
            return null;
        }

        if (!preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $this->error('Formato de token inválido.', 401);
        }

        require_once __DIR__ . '/../JwtHandler.php';
        $payload = JwtHandler::validarToken($matches[1]);

        if (!$payload) {
            $this->error('Token inválido ou expirado. Faça login novamente.', 401);
        }

        return $payload;
    }

    /**
     * Valida e formata telefone brasileiro (10 ou 11 dígitos com DDD) quando informado.
     */
    private function normalizarTelefone(mixed $valor): ?string {
        $tel = trim((string) ($valor ?? ''));
        if ($tel === '') {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $tel);
        $len = strlen($digitos);

        if ($len !== 10 && $len !== 11) {
            $this->error('Telefone inválido. Informe DDD + número com 10 ou 11 dígitos.', 422);
        }

        if ($len === 11) {
            return sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 5), substr($digitos, 7, 4));
        }

        return sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 4), substr($digitos, 6, 4));
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

        if ($usuario) {
            // Garante que o JwtHandler seja carregado
            require_once __DIR__ . '/../JwtHandler.php';

            // Gera o token JWT assinado
            $token = JwtHandler::gerarToken([
                'id'           => $usuario['id'],
                'email'        => $usuario['email'],
                'nivel_acesso' => $usuario['nivel_acesso']
            ]);

            // Anexa o token ao retorno do usuário
            $usuario['token'] = $token;

            $this->success($usuario, 'Login realizado');
        } else {
            $this->error('Credenciais invalidas', 401);
        }
    }
 
    public function historico(int $id): void {
        $limit = (int) ($this->param('limit') ?? 20);
        $this->success($this->model->historico($id, $limit));
    }

    public function alterarSenha(int $usuarioIdLogado): void {
        $data = $this->body();
        if (empty($data)) {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?? [];
        }

        $senhaAtual      = $data['senha_atual'] ?? '';
        $novaSenha      = $data['nova_senha'] ?? '';
        $confirmarSenha = $data['confirmar_senha'] ?? '';

        // 1. Validação de campos obrigatórios
        if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
            $this->error('Preencha todos os campos de senha.', 422);
        }

        // 2. Confirmação de igualdade
        if ($novaSenha !== $confirmarSenha) {
            $this->error('A nova senha e a confirmação não coincidem.', 422);
        }

        // 3. Tamanho mínimo da senha
        if (strlen($novaSenha) < 6) {
            $this->error('A nova senha deve ter no mínimo 6 caracteres.', 422);
        }

        // 4. Busca o usuário no banco para verificar a senha atual
        $usuario = $this->model->find($usuarioIdLogado);
        if (!$usuario) {
            $this->error('Usuário não encontrado.', 404);
        }

        if (!password_verify($senhaAtual, $usuario['senha_hash'])) {
            $this->error('A senha atual está incorreta.', 400);
        }

        // 5. Gera a nova hash e salva no banco
        $novaSenhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);

        if ($this->model->update($usuarioIdLogado, ['senha_hash' => $novaSenhaHash])) {
            $this->success(null, 'Senha alterada com sucesso!');
        } else {
            $this->error('Não foi possível atualizar a senha.', 500);
        }
    }
}