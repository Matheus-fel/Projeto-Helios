<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/EmpresaModel.php';

class EmpresaController extends Controller {
    private EmpresaModel $model;

    private const UFS = [
        'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA',
        'PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO',
    ];

    public function __construct() {
        $this->model = new EmpresaModel();
    }

    /** GET /empresas (retorna o cadastro corporativo completo) */
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
        $data = $this->normalizar($this->body());

        foreach (['nome_comercial', 'cnpj'] as $field) {
            if (empty($data[$field])) {
                $this->error("Campo obrigatório: $field");
                return;
            }
        }

        $this->validar($data);

        if ($this->model->findByCnpj($data['cnpj'])) {
            $this->error('Já existe uma empresa cadastrada com este CNPJ', 409);
            return;
        }

        if (empty($data['codigo_acesso'])) {
            // Campo em branco: gera automaticamente
            $data['codigo_acesso'] = $this->gerarCodigoAcesso($data['nome_comercial']);
        } elseif ($this->model->findByCodigo($data['codigo_acesso'])) {
            $this->error('Este código de acesso já está em uso. Escolha outro ou deixe em branco para gerar automaticamente', 409);
            return;
        }

        $data['data_adesao'] = $data['data_adesao'] ?? date('Y-m-d H:i:s');

        try {
            $id = $this->model->create($data);
        } catch (PDOException $e) {
            // Proteção contra corrida: o índice UNIQUE do banco é a palavra final
            if (($e->errorInfo[1] ?? null) === 1062) {
                $this->error('CNPJ ou código de acesso já cadastrado', 409);
                return;
            }
            throw $e;
        }

        $this->success($this->model->find($id), 'Empresa criada');
    }

    /** PUT /empresas/{id} */
    public function update(int $id): void {
        $body = $this->body();
        unset($body['id']);

        // Só normaliza/valida os campos que vieram na requisição
        $data = $this->normalizar($body);
        $data = array_intersect_key($data, $body);
        $this->validar($data, true);

        if (isset($data['cnpj'])) {
            $outra = $this->model->findByCnpj($data['cnpj']);
            if ($outra && (int) $outra['id'] !== $id) {
                $this->error('Já existe uma empresa cadastrada com este CNPJ', 409);
                return;
            }
        }
        if (!empty($data['codigo_acesso'])) {
            $outra = $this->model->findByCodigo($data['codigo_acesso']);
            if ($outra && (int) $outra['id'] !== $id) {
                $this->error('Este código de acesso já está em uso', 409);
                return;
            }
        }

        try {
            $ok = $this->model->update($id, $data);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                $this->error('CNPJ ou código de acesso já cadastrado', 409);
                return;
            }
            throw $e;
        }

        $ok ? $this->success(null, 'Empresa atualizada')
            : $this->error('Empresa não encontrada', 404);
    }

    /** DELETE /empresas/{id} */
    public function destroy(int $id): void {
        $this->model->delete($id)
            ? $this->success(null, 'Empresa removida')
            : $this->error('Empresa não encontrada', 404);
    }

    /** GET /empresas/{id}/usuarios */
    public function usuarios(int $id): void {
        $this->success($this->model->usuarios($id));
    }

    /** Limpa e padroniza os campos recebidos */
    private function normalizar(array $d): array {
        foreach (['nome_comercial', 'razao_social', 'cnpj', 'telefone',
                  'email_contato', 'cidade', 'uf', 'codigo_acesso'] as $campo) {
            if (array_key_exists($campo, $d)) {
                $d[$campo] = trim((string) $d[$campo]);
                if ($d[$campo] === '' && !in_array($campo, ['nome_comercial', 'cnpj', 'codigo_acesso'], true)) {
                    $d[$campo] = null; // opcionais vazios viram NULL
                }
            }
        }
        if (!empty($d['uf'])) {
            $d['uf'] = strtoupper($d['uf']);
        }
        if (!empty($d['codigo_acesso'])) {
            $d['codigo_acesso'] = strtoupper($d['codigo_acesso']);
        }
        if (!empty($d['cnpj'])) {
            $dig = preg_replace('/\D+/', '', $d['cnpj']);
            if (strlen($dig) === 14) { // padroniza como 00.000.000/0000-00
                $d['cnpj'] = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $dig);
            }
        }
        return $d;
    }

    /** Valida formato dos campos presentes. $parcial = true no PUT. */
    private function validar(array $d, bool $parcial = false): void {
        if (!empty($d['cnpj']) && strlen(preg_replace('/\D+/', '', $d['cnpj'])) !== 14) {
            $this->error('CNPJ inválido: deve conter 14 dígitos');
        }
        if (!empty($d['email_contato']) && !filter_var($d['email_contato'], FILTER_VALIDATE_EMAIL)) {
            $this->error('E-mail de contato inválido');
        }
        if (!empty($d['telefone'])) {
            $n = strlen(preg_replace('/\D+/', '', $d['telefone']));
            if ($n < 10 || $n > 13) {
                $this->error('Telefone inválido: informe DDD + número');
            }
        }
        if (!empty($d['uf']) && !in_array($d['uf'], self::UFS, true)) {
            $this->error('UF inválida. Use a sigla com 2 letras (ex: GO)');
        }
        if (!empty($d['codigo_acesso']) && !preg_match('/^[A-Z0-9-]{4,50}$/', $d['codigo_acesso'])) {
            $this->error('Código de acesso inválido: use de 4 a 50 caracteres (letras, números e hífen)');
        }
        if (isset($d['nome_comercial']) && $d['nome_comercial'] === '' && $parcial) {
            $this->error('Nome comercial não pode ficar vazio');
        }
    }

    /** Gera um código único no formato NOME-2026-A1B2 */
    private function gerarCodigoAcesso(string $nome): string {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome);
        $base  = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $ascii !== false ? $ascii : $nome));
        $base  = trim(substr($base, 0, 20), '-');
        if ($base === '') {
            $base = 'EMP';
        }
        do {
            $codigo = $base . '-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while ($this->model->findByCodigo($codigo));
        return $codigo;
    }
}