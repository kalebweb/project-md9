<?php
// classes/Orcamento.php
require_once __DIR__ . '/../config/database.php';

class Orcamento {
    private $conn;
    private $table = 'orcamentos';
    private $table_itens = 'orcamento_itens';
    private $table_clientes = 'clientes';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Verificar se pode criar orçamento (limite do plano)
    public function podecriarOrcamento($empresa_id) {
        try {
            $query = "SELECT plano, limite_orcamentos, orcamentos_utilizados FROM empresas WHERE id = :empresa_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $empresa = $stmt->fetch();
                
                // Se é premium, pode criar ilimitado
                if ($empresa['plano'] == 'premium') {
                    return ['success' => true, 'pode_criar' => true];
                }
                
                // Se é gratuito, verificar limite
                if ($empresa['orcamentos_utilizados'] < $empresa['limite_orcamentos']) {
                    return ['success' => true, 'pode_criar' => true];
                } else {
                    return [
                        'success' => false, 
                        'pode_criar' => false,
                        'message' => 'Limite de orçamentos atingido. Faça upgrade para Premium.'
                    ];
                }
            }
            
            return ['success' => false, 'pode_criar' => false, 'message' => 'Empresa não encontrada.'];
        } catch (Exception $e) {
            return ['success' => false, 'pode_criar' => false, 'message' => 'Erro ao verificar limite: ' . $e->getMessage()];
        }
    }

    // Listar orçamentos
    public function listarOrcamentos($empresa_id, $colaborador_id = null, $status = null, $busca = null) {
        try {
            $where_conditions = ["o.empresa_id = :empresa_id"];
            $params = [':empresa_id' => $empresa_id];

            // Se não é admin, filtrar apenas orçamentos do colaborador
            if ($colaborador_id !== null && $_SESSION['user_type'] == 'colaborador') {
                $where_conditions[] = "o.colaborador_id = :colaborador_id";
                $params[':colaborador_id'] = $colaborador_id;
            }

            if ($status !== null) {
                $where_conditions[] = "o.status = :status";
                $params[':status'] = $status;
            }

            if (!empty($busca)) {
                $where_conditions[] = "(o.numero_orcamento LIKE :busca OR o.titulo LIKE :busca OR c.razao_social LIKE :busca)";
                $params[':busca'] = "%$busca%";
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query = "SELECT o.*, c.razao_social as cliente_nome, c.responsavel_nome, u.nome as colaborador_nome
                     FROM " . $this->table . " o 
                     JOIN clientes c ON o.cliente_id = c.id
                     JOIN usuarios u ON o.colaborador_id = u.id
                     WHERE $where_clause 
                     ORDER BY o.data_criacao DESC";
            
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

    // Criar orçamento
    public function criarOrcamento($dados, $empresa_id, $colaborador_id) {
        try {
            $this->conn->beginTransaction();

            // Verificar limite
            $limite_check = $this->podecriarOrcamento($empresa_id);
            if (!$limite_check['pode_criar']) {
                $this->conn->rollBack();
                return $limite_check;
            }

            // Gerar número único do orçamento
            $numero_orcamento = $this->gerarNumeroOrcamento($empresa_id);

            $query = "INSERT INTO " . $this->table . " 
                     (empresa_id, cliente_id, colaborador_id, numero_orcamento, titulo, descricao, 
                      validade, observacoes, condicoes_pagamento, prazo_entrega, status) 
                     VALUES (:empresa_id, :cliente_id, :colaborador_id, :numero_orcamento, :titulo, :descricao,
                             :validade, :observacoes, :condicoes_pagamento, :prazo_entrega, :status)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':cliente_id', $dados['cliente_id']);
            $stmt->bindParam(':colaborador_id', $colaborador_id);
            $stmt->bindParam(':numero_orcamento', $numero_orcamento);
            $stmt->bindParam(':titulo', $dados['titulo']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':validade', $dados['validade']);
            $stmt->bindParam(':observacoes', $dados['observacoes']);
            $stmt->bindParam(':condicoes_pagamento', $dados['condicoes_pagamento']);
            $stmt->bindParam(':prazo_entrega', $dados['prazo_entrega']);
            $stmt->bindParam(':status', $dados['status']);
            $stmt->execute();

            $orcamento_id = $this->conn->lastInsertId();

            // Adicionar itens se fornecidos
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $this->adicionarItem($orcamento_id, $item);
                }
            }

            $this->conn->commit();
            
            $this->logAction($colaborador_id, 'criar_orcamento', $this->table, $orcamento_id, null, $dados);
            
            return ['success' => true, 'message' => 'Orçamento criado com sucesso!', 'id' => $orcamento_id, 'numero' => $numero_orcamento];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro ao criar orçamento: ' . $e->getMessage()];
        }
    }

    // Buscar orçamento por ID
    public function buscarOrcamento($id, $empresa_id, $colaborador_id = null) {
        try {
            $where_conditions = ["o.id = :id", "o.empresa_id = :empresa_id"];
            $params = [':id' => $id, ':empresa_id' => $empresa_id];

            // Se não é admin, verificar se é do colaborador
            if ($colaborador_id !== null && $_SESSION['user_type'] == 'colaborador') {
                $where_conditions[] = "o.colaborador_id = :colaborador_id";
                $params[':colaborador_id'] = $colaborador_id;
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query = "SELECT o.*, c.razao_social, c.nome_fantasia, c.cnpj, c.responsavel_nome, c.responsavel_cargo,
                             c.telefone_empresa, c.telefone_responsavel, c.email_empresa, c.email_responsavel,
                             c.endereco, c.cidade, c.estado, c.cep, u.nome as colaborador_nome
                     FROM " . $this->table . " o 
                     JOIN clientes c ON o.cliente_id = c.id
                     JOIN usuarios u ON o.colaborador_id = u.id
                     WHERE $where_clause";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $orcamento = $stmt->fetch();
                
                // Buscar itens do orçamento
                $orcamento['itens'] = $this->buscarItensOrcamento($id);
                
                return ['success' => true, 'orcamento' => $orcamento];
            } else {
                return ['success' => false, 'message' => 'Orçamento não encontrado.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar orçamento: ' . $e->getMessage()];
        }
    }

    // Editar orçamento
    public function editarOrcamento($id, $dados, $empresa_id, $colaborador_id = null) {
        try {
            $this->conn->beginTransaction();

            // Buscar dados atuais
            $result = $this->buscarOrcamento($id, $empresa_id, $colaborador_id);
            if (!$result['success']) {
                $this->conn->rollBack();
                return $result;
            }
            $dados_antigos = $result['orcamento'];

            $query = "UPDATE " . $this->table . " 
                     SET cliente_id = :cliente_id, titulo = :titulo, descricao = :descricao,
                         validade = :validade, observacoes = :observacoes, condicoes_pagamento = :condicoes_pagamento,
                         prazo_entrega = :prazo_entrega, status = :status, valor_desconto = :valor_desconto
                     WHERE id = :id AND empresa_id = :empresa_id";

            // Se não é admin, verificar se é do colaborador
            if ($colaborador_id !== null && $_SESSION['user_type'] == 'colaborador') {
                $query .= " AND colaborador_id = :colaborador_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cliente_id', $dados['cliente_id']);
            $stmt->bindParam(':titulo', $dados['titulo']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':validade', $dados['validade']);
            $stmt->bindParam(':observacoes', $dados['observacoes']);
            $stmt->bindParam(':condicoes_pagamento', $dados['condicoes_pagamento']);
            $stmt->bindParam(':prazo_entrega', $dados['prazo_entrega']);
            $stmt->bindParam(':status', $dados['status']);
            $stmt->bindParam(':valor_desconto', $dados['valor_desconto']);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);

            if ($colaborador_id !== null && $_SESSION['user_type'] == 'colaborador') {
                $stmt->bindParam(':colaborador_id', $colaborador_id);
            }

            $stmt->execute();

            // Recalcular valor final
            $this->recalcularValores($id);

            $this->conn->commit();

            if ($stmt->rowCount() > 0) {
                $this->logAction($_SESSION['user_id'], 'editar_orcamento', $this->table, $id, $dados_antigos, $dados);
                return ['success' => true, 'message' => 'Orçamento atualizado com sucesso!'];
            } else {
                return ['success' => false, 'message' => 'Nenhuma alteração foi feita.'];
            }
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro ao editar orçamento: ' . $e->getMessage()];
        }
    }

   // Adicionar item ao orçamento
    public function adicionarItem($orcamento_id, $dados) {
        try {
            // Calcular valor total do item
            $valor_unitario = isset($dados['valor_unitario']) ? str_replace(',', '.', str_replace('.', '', $dados['valor_unitario'])) : 0;
            $valor_promocional = isset($dados['valor_promocional']) ? str_replace(',', '.', str_replace('.', '', $dados['valor_promocional'])) : null;
            $quantidade = isset($dados['quantidade']) ? $dados['quantidade'] : 1;
            
            // Usar valor promocional se existir, senão usar valor unitário
            $valor_usado = (!empty($valor_promocional) && $valor_promocional > 0) ? $valor_promocional : $valor_unitario;
            $valor_total = $valor_usado * $quantidade;
            $valor_final = $valor_total; // valor_final do item é igual ao valor_total (sem descontos no item)

            // Ordem do item
            $ordem = isset($dados['ordem']) ? $dados['ordem'] : 0;

            $query = "INSERT INTO " . $this->table_itens . " 
                     (orcamento_id, produto_id, descricao, quantidade, valor_unitario, valor_promocional, valor_total, valor_final, ordem) 
                     VALUES (:orcamento_id, :produto_id, :descricao, :quantidade, :valor_unitario, :valor_promocional, :valor_total, :valor_final, :ordem)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':orcamento_id', $orcamento_id);
            $stmt->bindParam(':produto_id', $dados['produto_id']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':valor_unitario', $valor_unitario);
            $stmt->bindParam(':valor_promocional', $valor_promocional);
            $stmt->bindParam(':valor_total', $valor_total);
            $stmt->bindParam(':valor_final', $valor_final);
            $stmt->bindParam(':ordem', $ordem);
            $stmt->execute();

            return $this->conn->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception('Erro ao adicionar item: ' . $e->getMessage());
        }
    }

    // Buscar itens do orçamento
    public function buscarItensOrcamento($orcamento_id) {
        try {
            $query = "SELECT oi.*, p.nome as produto_nome, p.foto as produto_foto
                     FROM " . $this->table_itens . " oi
                     LEFT JOIN produtos p ON oi.produto_id = p.id
                     WHERE oi.orcamento_id = :orcamento_id
                     ORDER BY oi.ordem ASC, oi.id ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':orcamento_id', $orcamento_id);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    // Remover item do orçamento
    public function removerItem($item_id, $orcamento_id) {
        try {
            $query = "DELETE FROM " . $this->table_itens . " 
                     WHERE id = :item_id AND orcamento_id = :orcamento_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':item_id', $item_id);
            $stmt->bindParam(':orcamento_id', $orcamento_id);
            $stmt->execute();

            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }

    // Alterar status do orçamento
    public function alterarStatus($id, $status, $empresa_id, $colaborador_id = null) {
        try {
            $result = $this->buscarOrcamento($id, $empresa_id, $colaborador_id);
            if (!$result['success']) {
                return $result;
            }
            $dados_antigos = $result['orcamento'];

            $query = "UPDATE " . $this->table . " 
                     SET status = :status 
                     WHERE id = :id AND empresa_id = :empresa_id";

            // Se não é admin, verificar se é do colaborador
            if ($colaborador_id !== null && $_SESSION['user_type'] == 'colaborador') {
                $query .= " AND colaborador_id = :colaborador_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);

            if ($colaborador_id !== null && $_SESSION['user_type'] == 'colaborador') {
                $stmt->bindParam(':colaborador_id', $colaborador_id);
            }

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->logAction($_SESSION['user_id'], 'alterar_status_orcamento', $this->table, $id, $dados_antigos, ['status' => $status]);
                return ['success' => true, 'message' => 'Status alterado com sucesso!'];
            } else {
                return ['success' => false, 'message' => 'Orçamento não encontrado ou nenhuma alteração foi feita.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao alterar status: ' . $e->getMessage()];
        }
    }

    // Duplicar orçamento
    public function duplicarOrcamento($id, $empresa_id, $colaborador_id) {
        try {
            $this->conn->beginTransaction();

            // Verificar limite
            $limite_check = $this->podecriarOrcamento($empresa_id);
            if (!$limite_check['pode_criar']) {
                $this->conn->rollBack();
                return $limite_check;
            }

            // Buscar orçamento original
            $result = $this->buscarOrcamento($id, $empresa_id);
            if (!$result['success']) {
                $this->conn->rollBack();
                return $result;
            }
            $orcamento_original = $result['orcamento'];

            // Gerar novo número
            $numero_orcamento = $this->gerarNumeroOrcamento($empresa_id);

            // Criar novo orçamento
            $query = "INSERT INTO " . $this->table . " 
                     (empresa_id, cliente_id, colaborador_id, numero_orcamento, titulo, descricao, 
                      validade, observacoes, condicoes_pagamento, prazo_entrega, status) 
                     VALUES (:empresa_id, :cliente_id, :colaborador_id, :numero_orcamento, :titulo, :descricao,
                             :validade, :observacoes, :condicoes_pagamento, :prazo_entrega, 'rascunho')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':cliente_id', $orcamento_original['cliente_id']);
            $stmt->bindParam(':colaborador_id', $colaborador_id);
            $stmt->bindParam(':numero_orcamento', $numero_orcamento);
            $stmt->bindParam(':titulo', $orcamento_original['titulo'] . ' (Cópia)');
            $stmt->bindParam(':descricao', $orcamento_original['descricao']);
            $stmt->bindParam(':validade', $orcamento_original['validade']);
            $stmt->bindParam(':observacoes', $orcamento_original['observacoes']);
            $stmt->bindParam(':condicoes_pagamento', $orcamento_original['condicoes_pagamento']);
            $stmt->bindParam(':prazo_entrega', $orcamento_original['prazo_entrega']);
            $stmt->execute();

            $novo_orcamento_id = $this->conn->lastInsertId();

            // Copiar itens
            foreach ($orcamento_original['itens'] as $item) {
                $this->adicionarItem($novo_orcamento_id, [
                    'produto_id' => $item['produto_id'],
                    'descricao' => $item['descricao'],
                    'quantidade' => $item['quantidade'],
                    'valor_unitario' => $item['valor_unitario'],
                    'valor_promocional' => $item['valor_promocional'],
                    'ordem' => $item['ordem']
                ]);
            }

            $this->conn->commit();
            
            $this->logAction($colaborador_id, 'duplicar_orcamento', $this->table, $novo_orcamento_id, null, ['original_id' => $id]);
            
            return ['success' => true, 'message' => 'Orçamento duplicado com sucesso!', 'id' => $novo_orcamento_id, 'numero' => $numero_orcamento];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro ao duplicar orçamento: ' . $e->getMessage()];
        }
    }

    // Gerar número único do orçamento
    private function gerarNumeroOrcamento($empresa_id) {
        $ano = date('Y');
        $mes = date('m');
        
        // Buscar último número do mês
        $query = "SELECT numero_orcamento FROM " . $this->table . " 
                 WHERE empresa_id = :empresa_id AND numero_orcamento LIKE :pattern 
                 ORDER BY numero_orcamento DESC LIMIT 1";
        
        $pattern = $empresa_id . $ano . $mes . '%';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':empresa_id', $empresa_id);
        $stmt->bindParam(':pattern', $pattern);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $ultimo = $stmt->fetch()['numero_orcamento'];
            $sequencial = (int)substr($ultimo, -4) + 1;
        } else {
            $sequencial = 1;
        }
        
        return $empresa_id . $ano . $mes . str_pad($sequencial, 4, '0', STR_PAD_LEFT);
    }

    // Recalcular valores do orçamento
    private function recalcularValores($orcamento_id) {
        try {
            $query = "UPDATE " . $this->table . " o
                     SET valor_total = (
                         SELECT COALESCE(SUM(valor_total), 0) 
                         FROM " . $this->table_itens . " 
                         WHERE orcamento_id = o.id
                     ),
                     valor_final = (
                         SELECT COALESCE(SUM(valor_total), 0) 
                         FROM " . $this->table_itens . " 
                         WHERE orcamento_id = o.id
                     ) - COALESCE(o.valor_desconto, 0)
                     WHERE o.id = :orcamento_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':orcamento_id', $orcamento_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao recalcular valores: " . $e->getMessage());
        }
    }

    // Estatísticas de orçamentos
    public function estatisticasOrcamentos($empresa_id, $colaborador_id = null) {
        try {
            $where_conditions = ["empresa_id = :empresa_id"];
            $params = [':empresa_id' => $empresa_id];

            if ($colaborador_id !== null && $_SESSION['user_type'] == 'colaborador') {
                $where_conditions[] = "colaborador_id = :colaborador_id";
                $params[':colaborador_id'] = $colaborador_id;
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'rascunho' THEN 1 ELSE 0 END) as rascunhos,
                        SUM(CASE WHEN status = 'enviado' THEN 1 ELSE 0 END) as enviados,
                        SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as aprovados,
                        SUM(CASE WHEN status = 'rejeitado' THEN 1 ELSE 0 END) as rejeitados,
                        SUM(CASE WHEN status = 'aprovado' THEN valor_final ELSE 0 END) as valor_aprovado,
                        AVG(CASE WHEN status = 'aprovado' THEN valor_final END) as ticket_medio
                     FROM " . $this->table . " 
                     WHERE $where_clause";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return [
                'total' => 0,
                'rascunhos' => 0,
                'enviados' => 0,
                'aprovados' => 0,
                'rejeitados' => 0,
                'valor_aprovado' => 0,
                'ticket_medio' => 0
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

    public function listarClientes($empresa_id) {
        $stmt = $this->conn->prepare("SELECT id, razao_social FROM clientes WHERE empresa_id = :empresa_id ORDER BY razao_social ASC");
        $stmt->bindParam(':empresa_id', $empresa_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function adicionarOrcamento($dados, $empresa_id, $colaborador_id) {
        try {
            $this->conn->beginTransaction();
            // Gerar número único do orçamento
            $numero_orcamento = $this->gerarNumeroOrcamento($empresa_id);
            $query = "INSERT INTO orcamentos (empresa_id, cliente_id, colaborador_id, numero_orcamento, titulo, descricao, usuario_id, data_criacao, status) VALUES (:empresa_id, :cliente_id, :colaborador_id, :numero_orcamento, :titulo, :descricao, :usuario_id, NOW(), 'rascunho')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':cliente_id', $dados['cliente_id']);
            $stmt->bindParam(':colaborador_id', $colaborador_id);
            $stmt->bindParam(':numero_orcamento', $numero_orcamento);
            $stmt->bindParam(':titulo', $dados['titulo']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':usuario_id', $colaborador_id); // Se quiser manter usuario_id igual ao colaborador
            $stmt->execute();
            $orcamento_id = $this->conn->lastInsertId();
            foreach ($dados['itens'] as $item) {
                $this->adicionarItem($orcamento_id, $item);
            }
            $this->conn->commit();
            return ['success' => true, 'message' => 'Orçamento criado com sucesso!', 'id' => $orcamento_id, 'numero' => $numero_orcamento];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro ao criar orçamento: ' . $e->getMessage()];
        }
    }
}
?>