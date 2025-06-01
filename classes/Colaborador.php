<?php
// classes/Colaborador.php
require_once __DIR__ . '/../config/database.php';

class Colaborador {
    private $conn;
    private $table_usuarios = 'usuarios';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Listar colaboradores da empresa
    public function listarColaboradores($empresa_id, $ativo = null) {
        try {
            $where_ativo = '';
            if ($ativo !== null) {
                $where_ativo = " AND ativo = :ativo";
            }

            $query = "SELECT id, nome, email, tipo, ativo, data_criacao, data_atualizacao 
                     FROM " . $this->table_usuarios . " 
                     WHERE empresa_id = :empresa_id AND tipo IN ('admin_empresa', 'colaborador') " . $where_ativo . "
                     ORDER BY tipo ASC, nome ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            
            if ($ativo !== null) {
                $stmt->bindParam(':ativo', $ativo);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    // Adicionar colaborador
    public function adicionarColaborador($dados, $empresa_id) {
        try {
            // Verificar se email já existe
            $query = "SELECT id FROM " . $this->table_usuarios . " WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Este email já está cadastrado no sistema.'];
            }

            // Validações
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email inválido.'];
            }

            if (strlen($dados['senha']) < 6) {
                return ['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'];
            }

            if (!in_array($dados['tipo'], ['admin_empresa', 'colaborador'])) {
                return ['success' => false, 'message' => 'Tipo de usuário inválido.'];
            }

            $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $query = "INSERT INTO " . $this->table_usuarios . " 
                     (nome, email, senha, tipo, empresa_id, ativo) 
                     VALUES (:nome, :email, :senha, :tipo, :empresa_id, 1)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':tipo', $dados['tipo']);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            $colaborador_id = $this->conn->lastInsertId();
            
            $this->logAction($_SESSION['user_id'], 'adicionar_colaborador', 'usuarios', $colaborador_id, null, $dados);
            
            return ['success' => true, 'message' => 'Colaborador adicionado com sucesso!', 'id' => $colaborador_id];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao adicionar colaborador: ' . $e->getMessage()];
        }
    }

    // Buscar colaborador por ID
    public function buscarColaborador($id, $empresa_id) {
        try {
            $query = "SELECT id, nome, email, tipo, ativo, data_criacao 
                     FROM " . $this->table_usuarios . " 
                     WHERE id = :id AND empresa_id = :empresa_id AND tipo IN ('admin_empresa', 'colaborador')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'colaborador' => $stmt->fetch()];
            } else {
                return ['success' => false, 'message' => 'Colaborador não encontrado.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar colaborador: ' . $e->getMessage()];
        }
    }

    // Editar colaborador
    public function editarColaborador($id, $dados, $empresa_id) {
        try {
            // Buscar dados atuais
            $result = $this->buscarColaborador($id, $empresa_id);
            if (!$result['success']) {
                return $result;
            }
            $dados_antigos = $result['colaborador'];

            // Verificar se email já existe (exceto o próprio)
            $query = "SELECT id FROM " . $this->table_usuarios . " WHERE email = :email AND id != :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Este email já está cadastrado para outro usuário.'];
            }

            // Validações
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email inválido.'];
            }

            if (!in_array($dados['tipo'], ['admin_empresa', 'colaborador'])) {
                return ['success' => false, 'message' => 'Tipo de usuário inválido.'];
            }

            // Montar query de update
            $set_senha = '';
            if (!empty($dados['senha'])) {
                if (strlen($dados['senha']) < 6) {
                    return ['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'];
                }
                $set_senha = ', senha = :senha';
            }

            $query = "UPDATE " . $this->table_usuarios . " 
                     SET nome = :nome, email = :email, tipo = :tipo, ativo = :ativo" . $set_senha . "
                     WHERE id = :id AND empresa_id = :empresa_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->bindParam(':tipo', $dados['tipo']);
            $stmt->bindParam(':ativo', $dados['ativo']);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);

            if (!empty($dados['senha'])) {
                $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
                $stmt->bindParam(':senha', $senha_hash);
            }

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->logAction($_SESSION['user_id'], 'editar_colaborador', 'usuarios', $id, $dados_antigos, $dados);
                return ['success' => true, 'message' => 'Colaborador atualizado com sucesso!'];
            } else {
                return ['success' => false, 'message' => 'Nenhuma alteração foi feita.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao editar colaborador: ' . $e->getMessage()];
        }
    }

    // Ativar/Desativar colaborador
    public function alterarStatusColaborador($id, $empresa_id, $ativo) {
        try {
            // Verificar se é o próprio usuário logado
            if ($id == $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'Você não pode desativar sua própria conta.'];
            }

            // Buscar dados atuais
            $result = $this->buscarColaborador($id, $empresa_id);
            if (!$result['success']) {
                return $result;
            }
            $dados_antigos = $result['colaborador'];

            $query = "UPDATE " . $this->table_usuarios . " 
                     SET ativo = :ativo 
                     WHERE id = :id AND empresa_id = :empresa_id AND tipo IN ('admin_empresa', 'colaborador')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ativo', $ativo);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $acao = $ativo ? 'ativar_colaborador' : 'desativar_colaborador';
                $this->logAction($_SESSION['user_id'], $acao, 'usuarios', $id, $dados_antigos, ['ativo' => $ativo]);
                
                $status_text = $ativo ? 'ativado' : 'desativado';
                return ['success' => true, 'message' => "Colaborador $status_text com sucesso!"];
            } else {
                return ['success' => false, 'message' => 'Colaborador não encontrado ou nenhuma alteração foi feita.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao alterar status: ' . $e->getMessage()];
        }
    }

    // Excluir colaborador (soft delete)
    public function excluirColaborador($id, $empresa_id) {
        try {
            // Verificar se é o próprio usuário logado
            if ($id == $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'Você não pode excluir sua própria conta.'];
            }

            // Verificar se o colaborador tem orçamentos
            $query = "SELECT COUNT(*) as total FROM orcamentos WHERE colaborador_id = :colaborador_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':colaborador_id', $id);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result['total'] > 0) {
                return ['success' => false, 'message' => 'Não é possível excluir este colaborador pois ele possui orçamentos cadastrados. Desative-o ao invés de excluir.'];
            }

            // Buscar dados atuais
            $result = $this->buscarColaborador($id, $empresa_id);
            if (!$result['success']) {
                return $result;
            }
            $dados_antigos = $result['colaborador'];

            $query = "DELETE FROM " . $this->table_usuarios . " 
                     WHERE id = :id AND empresa_id = :empresa_id AND tipo IN ('admin_empresa', 'colaborador')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->logAction($_SESSION['user_id'], 'excluir_colaborador', 'usuarios', $id, $dados_antigos, null);
                return ['success' => true, 'message' => 'Colaborador excluído com sucesso!'];
            } else {
                return ['success' => false, 'message' => 'Colaborador não encontrado.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir colaborador: ' . $e->getMessage()];
        }
    }

    // Buscar colaboradores para dropdown
    public function listarColaboradoresAtivos($empresa_id) {
        try {
            $query = "SELECT id, nome FROM " . $this->table_usuarios . " 
                     WHERE empresa_id = :empresa_id AND tipo IN ('admin_empresa', 'colaborador') AND ativo = 1
                     ORDER BY nome ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    // Estatísticas de colaboradores
    public function estatisticasColaboradores($empresa_id) {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
                        SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) as inativos,
                        SUM(CASE WHEN tipo = 'admin_empresa' THEN 1 ELSE 0 END) as admins,
                        SUM(CASE WHEN tipo = 'colaborador' THEN 1 ELSE 0 END) as colaboradores
                     FROM " . $this->table_usuarios . " 
                     WHERE empresa_id = :empresa_id AND tipo IN ('admin_empresa', 'colaborador')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return [
                'total' => 0,
                'ativos' => 0,
                'inativos' => 0,
                'admins' => 0,
                'colaboradores' => 0
            ];
        }
    }

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
}
?>