<?php
// classes/Cliente.php
require_once __DIR__ . '/../config/database.php';

class Cliente {
    private $conn;
    private $table = 'clientes';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Listar clientes da empresa
    public function listarClientes($empresa_id, $ativo = null, $busca = null) {
        try {
            $where_conditions = ["empresa_id = :empresa_id"];
            $params = [':empresa_id' => $empresa_id];

            if ($ativo !== null) {
                $where_conditions[] = "ativo = :ativo";
                $params[':ativo'] = $ativo;
            }

            if (!empty($busca)) {
                $where_conditions[] = "(razao_social LIKE :busca OR nome_fantasia LIKE :busca OR responsavel_nome LIKE :busca OR cnpj LIKE :busca)";
                $params[':busca'] = "%$busca%";
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query = "SELECT * FROM " . $this->table . " 
            WHERE $where_clause 
            ORDER BY razao_social ASC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    // Adicionar cliente
    public function adicionarCliente($dados, $empresa_id) {
        try {
            // Verificar se CNPJ já existe na empresa
            $query = "SELECT id FROM " . $this->table . " WHERE cnpj = :cnpj AND empresa_id = :empresa_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cnpj', $dados['cnpj']);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Este CNPJ já está cadastrado para sua empresa.'];
            }

            // Validações
            if (!$this->validarCNPJ($dados['cnpj'])) {
                return ['success' => false, 'message' => 'CNPJ inválido.'];
            }

            if (!filter_var($dados['email_responsavel'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email do responsável inválido.'];
            }

            if (!empty($dados['email_empresa']) && !filter_var($dados['email_empresa'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email da empresa inválido.'];
            }

            $query = "INSERT INTO " . $this->table . " 
            (empresa_id, razao_social, nome_fantasia, cnpj, responsavel_nome, responsavel_cargo,
              telefone_empresa, telefone_responsavel, email_empresa, email_responsavel, 
              endereco, cidade, estado, cep, observacoes, ativo) 
            VALUES (:empresa_id, :razao_social, :nome_fantasia, :cnpj, :responsavel_nome, :responsavel_cargo,
             :telefone_empresa, :telefone_responsavel, :email_empresa, :email_responsavel,
             :endereco, :cidade, :estado, :cep, :observacoes, :ativo)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':razao_social', $dados['razao_social']);
            $stmt->bindParam(':nome_fantasia', $dados['nome_fantasia']);
            $stmt->bindParam(':cnpj', $dados['cnpj']);
            $stmt->bindParam(':responsavel_nome', $dados['responsavel_nome']);
            $stmt->bindParam(':responsavel_cargo', $dados['responsavel_cargo']);
            $stmt->bindParam(':telefone_empresa', $dados['telefone_empresa']);
            $stmt->bindParam(':telefone_responsavel', $dados['telefone_responsavel']);
            $stmt->bindParam(':email_empresa', $dados['email_empresa']);
            $stmt->bindParam(':email_responsavel', $dados['email_responsavel']);
            $stmt->bindParam(':endereco', $dados['endereco']);
            $stmt->bindParam(':cidade', $dados['cidade']);
            $stmt->bindParam(':estado', $dados['estado']);
            $stmt->bindParam(':cep', $dados['cep']);
            $stmt->bindParam(':observacoes', $dados['observacoes']);
            $stmt->bindParam(':ativo', $dados['ativo']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $id = $this->conn->lastInsertId();
                $this->logAction($_SESSION['user_id'], 'adicionar_cliente', $this->table, $id, null, $dados);
                return ['success' => true, 'message' => 'Cliente cadastrado com sucesso!'];
            } else {
                return ['success' => false, 'message' => 'Falha ao cadastrar cliente.'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao adicionar cliente: ' . $e->getMessage()];
        }
    }

    // Buscar cliente
    public function buscarCliente($id, $empresa_id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND empresa_id = :empresa_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'cliente' => $stmt->fetch()];
            } else {
                return ['success' => false, 'message' => 'Cliente não encontrado.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar cliente: ' . $e->getMessage()];
        }
    }

    // Alterar status
    public function alterarStatusCliente($id, $empresa_id, $ativo) {
        try {
            $result = $this->buscarCliente($id, $empresa_id);
            if (!$result['success']) return $result;
            $dados_antigos = $result['cliente'];

            $query = "UPDATE " . $this->table . " SET ativo = :ativo WHERE id = :id AND empresa_id = :empresa_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ativo', $ativo);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $acao = $ativo ? 'ativar_cliente' : 'desativar_cliente';
                $this->logAction($_SESSION['user_id'], $acao, $this->table, $id, $dados_antigos, ['ativo' => $ativo]);
                return ['success' => true, 'message' => "Cliente " . ($ativo ? 'ativado' : 'desativado') . " com sucesso!"];
            }

            return ['success' => false, 'message' => 'Nenhuma alteração foi feita.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao alterar status: ' . $e->getMessage()];
        }
    }

    // Excluir cliente
    public function excluirCliente($id, $empresa_id) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE cliente_id = :cliente_id");
            $stmt->bindParam(':cliente_id', $id);
            $stmt->execute();
            if ($stmt->fetch()['total'] > 0) {
                return ['success' => false, 'message' => 'Não é possível excluir este cliente pois ele possui orçamentos cadastrados.'];
            }

            $result = $this->buscarCliente($id, $empresa_id);
            if (!$result['success']) return $result;
            $dados_antigos = $result['cliente'];

            $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id AND empresa_id = :empresa_id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->logAction($_SESSION['user_id'], 'excluir_cliente', $this->table, $id, $dados_antigos, null);
                return ['success' => true, 'message' => 'Cliente excluído com sucesso!'];
            }

            return ['success' => false, 'message' => 'Cliente não encontrado.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir cliente: ' . $e->getMessage()];
        }
    }

    // Dropdown de clientes ativos
    public function listarClientesAtivos($empresa_id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, razao_social, nome_fantasia FROM " . $this->table . " WHERE empresa_id = :empresa_id AND ativo = 1 ORDER BY razao_social ASC");
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    // Estatísticas
    public function estatisticasClientes($empresa_id) {
        try {
            $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
            SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) as inativos,
            COUNT(CASE WHEN DATE(data_criacao) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 END) as ultimos_30_dias
            FROM " . $this->table . " 
            WHERE empresa_id = :empresa_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            return ['total' => 0, 'ativos' => 0, 'inativos' => 0, 'ultimos_30_dias' => 0];
        }
    }

    // Validação de CNPJ
    private function validarCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14) return false;

        for ($t = 12; $t < 14; $t++) {
            $d = 0;
            $c = 0;
            for ($m = 0; $m < $t; $m++) {
                $d += $cnpj[$m] * (($t + 1 - $m) % 8 + 2);
            }
            $d = 11 - ($d % 11);
            if ($cnpj[$t] != ($d > 9 ? 0 : $d)) return false;
        }

        return true;
    }

    // Substituir o método logAction existente na classe Cliente por este:

// Log de ações
    private function logAction($user_id, $acao, $tabela, $registro_id, $dados_antigos = null, $dados_novos = null) {
        try {
            $query = "INSERT INTO logs_sistema 
            (usuario_id, empresa_id, acao, tabela_afetada, registro_id, dados_antigos, dados_novos, ip_address, user_agent) 
            VALUES (:usuario_id, :empresa_id, :acao, :tabela, :registro_id, :dados_antigos, :dados_novos, :ip, :user_agent)";
            
            $stmt = $this->conn->prepare($query);
            
            $empresa_id = $_SESSION['empresa_id'] ?? null;
            $dados_antigos_json = $dados_antigos ? json_encode($dados_antigos) : null;
            $dados_novos_json = $dados_novos ? json_encode($dados_novos) : null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->bindParam(':usuario_id', $user_id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':acao', $acao);
            $stmt->bindParam(':tabela', $tabela);
            $stmt->bindParam(':registro_id', $registro_id);
            $stmt->bindParam(':dados_antigos', $dados_antigos_json);
            $stmt->bindParam(':dados_novos', $dados_novos_json);
            $stmt->bindParam(':ip', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao salvar log: " . $e->getMessage());
        }
    }


// Adicionar este método na classe Cliente (classes/Cliente.php)
// Inserir após o método adicionarCliente()

// Editar cliente
    public function editarCliente($id, $dados, $empresa_id) {
        try {
        // Buscar dados atuais para o log
            $result = $this->buscarCliente($id, $empresa_id);
            if (!$result['success']) {
                return $result;
            }
            $dados_antigos = $result['cliente'];
            
        // Verificar se CNPJ já existe para outra empresa (exceto o próprio cliente)
            $query = "SELECT id FROM " . $this->table . " 
            WHERE cnpj = :cnpj AND empresa_id = :empresa_id AND id != :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cnpj', $dados['cnpj']);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Este CNPJ já está cadastrado para outro cliente.'];
            }

        // Validações
            if (!$this->validarCNPJ($dados['cnpj'])) {
                return ['success' => false, 'message' => 'CNPJ inválido.'];
            }

            if (!filter_var($dados['email_responsavel'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email do responsável inválido.'];
            }

            if (!empty($dados['email_empresa']) && !filter_var($dados['email_empresa'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email da empresa inválido.'];
            }

            $query = "UPDATE " . $this->table . " SET 
            razao_social = :razao_social,
            nome_fantasia = :nome_fantasia,
            cnpj = :cnpj,
            responsavel_nome = :responsavel_nome,
            responsavel_cargo = :responsavel_cargo,
            telefone_empresa = :telefone_empresa,
            telefone_responsavel = :telefone_responsavel,
            email_empresa = :email_empresa,
            email_responsavel = :email_responsavel,
            endereco = :endereco,
            cidade = :cidade,
            estado = :estado,
            cep = :cep,
            observacoes = :observacoes,
            ativo = :ativo,
            data_atualizacao = NOW()
            WHERE id = :id AND empresa_id = :empresa_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':razao_social', $dados['razao_social']);
            $stmt->bindParam(':nome_fantasia', $dados['nome_fantasia']);
            $stmt->bindParam(':cnpj', $dados['cnpj']);
            $stmt->bindParam(':responsavel_nome', $dados['responsavel_nome']);
            $stmt->bindParam(':responsavel_cargo', $dados['responsavel_cargo']);
            $stmt->bindParam(':telefone_empresa', $dados['telefone_empresa']);
            $stmt->bindParam(':telefone_responsavel', $dados['telefone_responsavel']);
            $stmt->bindParam(':email_empresa', $dados['email_empresa']);
            $stmt->bindParam(':email_responsavel', $dados['email_responsavel']);
            $stmt->bindParam(':endereco', $dados['endereco']);
            $stmt->bindParam(':cidade', $dados['cidade']);
            $stmt->bindParam(':estado', $dados['estado']);
            $stmt->bindParam(':cep', $dados['cep']);
            $stmt->bindParam(':observacoes', $dados['observacoes']);
            $stmt->bindParam(':ativo', $dados['ativo']);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->logAction($_SESSION['user_id'], 'editar_cliente', $this->table, $id, $dados_antigos, $dados);
                return ['success' => true, 'message' => 'Cliente atualizado com sucesso!'];
            } else {
                return ['success' => false, 'message' => 'Nenhuma alteração foi feita.'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao editar cliente: ' . $e->getMessage()];
        }
    }


}
