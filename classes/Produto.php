<?php
// classes/Produto.php
require_once __DIR__ . '/../config/database.php';

class Produto {
    private $conn;
    private $table = 'produtos';
    private $table_categorias = 'categorias';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Listar produtos da empresa
    public function listarProdutos($empresa_id, $categoria_id = null, $ativo = null, $busca = null) {
        try {
            $where_conditions = ["p.empresa_id = :empresa_id"];
            $params = [':empresa_id' => $empresa_id];

            if ($categoria_id !== null) {
                $where_conditions[] = "p.categoria_id = :categoria_id";
                $params[':categoria_id'] = $categoria_id;
            }

            if ($ativo !== null) {
                $where_conditions[] = "p.ativo = :ativo";
                $params[':ativo'] = $ativo;
            }

            if (!empty($busca)) {
                $where_conditions[] = "(p.nome LIKE :busca OR p.descricao LIKE :busca OR p.codigo LIKE :busca)";
                $params[':busca'] = "%$busca%";
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query = "SELECT p.*, c.nome as categoria_nome, c.cor as categoria_cor 
                     FROM " . $this->table . " p 
                     LEFT JOIN " . $this->table_categorias . " c ON p.categoria_id = c.id
                     WHERE $where_clause 
                     ORDER BY p.nome ASC";
            
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

    // Adicionar produto
    public function adicionarProduto($dados, $empresa_id) {
        try {
            // Verificar se código já existe na empresa (se informado)
            if (!empty($dados['codigo'])) {
                $query = "SELECT id FROM " . $this->table . " WHERE codigo = :codigo AND empresa_id = :empresa_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':codigo', $dados['codigo']);
                $stmt->bindParam(':empresa_id', $empresa_id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return ['success' => false, 'message' => 'Este código já está cadastrado para outro produto.'];
                }
            }

            // Validações
            if ($dados['preco'] <= 0) {
                return ['success' => false, 'message' => 'O preço deve ser maior que zero.'];
            }

            // Upload da foto se fornecida
            $foto_nome = null;
            if (isset($dados['foto']) && $dados['foto']['error'] == 0) {
                $upload_result = $this->uploadFoto($dados['foto'], $empresa_id);
                if (!$upload_result['success']) {
                    return $upload_result;
                }
                $foto_nome = $upload_result['filename'];
            }

            $query = "INSERT INTO " . $this->table . " 
                     (empresa_id, categoria_id, nome, descricao, preco, foto, codigo, unidade, estoque_minimo, estoque_atual) 
                     VALUES (:empresa_id, :categoria_id, :nome, :descricao, :preco, :foto, :codigo, :unidade, :estoque_minimo, :estoque_atual)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':categoria_id', $dados['categoria_id']);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':preco', $dados['preco']);
            $stmt->bindParam(':foto', $foto_nome);
            $stmt->bindParam(':codigo', $dados['codigo']);
            $stmt->bindParam(':unidade', $dados['unidade']);
            $stmt->bindParam(':estoque_minimo', $dados['estoque_minimo']);
            $stmt->bindParam(':estoque_atual', $dados['estoque_atual']);
            $stmt->execute();

            $produto_id = $this->conn->lastInsertId();
            
            $this->logAction($_SESSION['user_id'], 'adicionar_produto', $this->table, $produto_id, null, $dados);
            
            return ['success' => true, 'message' => 'Produto adicionado com sucesso!', 'id' => $produto_id];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao adicionar produto: ' . $e->getMessage()];
        }
    }

    // Buscar produto por ID
    public function buscarProduto($id, $empresa_id) {
        try {
            $query = "SELECT p.*, c.nome as categoria_nome 
                     FROM " . $this->table . " p 
                     LEFT JOIN " . $this->table_categorias . " c ON p.categoria_id = c.id
                     WHERE p.id = :id AND p.empresa_id = :empresa_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'produto' => $stmt->fetch()];
            } else {
                return ['success' => false, 'message' => 'Produto não encontrado.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar produto: ' . $e->getMessage()];
        }
    }

    // Editar produto
    public function editarProduto($id, $dados, $empresa_id) {
        try {
            // Buscar dados atuais
            $result = $this->buscarProduto($id, $empresa_id);
            if (!$result['success']) {
                return $result;
            }
            $dados_antigos = $result['produto'];

            // Verificar se código já existe (exceto o próprio)
            if (!empty($dados['codigo'])) {
                $query = "SELECT id FROM " . $this->table . " WHERE codigo = :codigo AND empresa_id = :empresa_id AND id != :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':codigo', $dados['codigo']);
                $stmt->bindParam(':empresa_id', $empresa_id);
                $stmt->bindParam(':id', $id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return ['success' => false, 'message' => 'Este código já está cadastrado para outro produto.'];
                }
            }

            // Validações
            if ($dados['preco'] <= 0) {
                return ['success' => false, 'message' => 'O preço deve ser maior que zero.'];
            }

            // Upload da foto se fornecida
            $foto_nome = $dados_antigos['foto']; // Manter foto atual por padrão
            if (isset($dados['foto']) && $dados['foto']['error'] == 0) {
                $upload_result = $this->uploadFoto($dados['foto'], $empresa_id);
                if (!$upload_result['success']) {
                    return $upload_result;
                }
                
                // Remover foto anterior se existir
                if (!empty($dados_antigos['foto'])) {
                    $this->removerFoto($dados_antigos['foto']);
                }
                
                $foto_nome = $upload_result['filename'];
            }

            $query = "UPDATE " . $this->table . " 
                     SET categoria_id = :categoria_id, nome = :nome, descricao = :descricao, preco = :preco,
                         foto = :foto, codigo = :codigo, unidade = :unidade, estoque_minimo = :estoque_minimo,
                         estoque_atual = :estoque_atual, ativo = :ativo
                     WHERE id = :id AND empresa_id = :empresa_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':categoria_id', $dados['categoria_id']);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':preco', $dados['preco']);
            $stmt->bindParam(':foto', $foto_nome);
            $stmt->bindParam(':codigo', $dados['codigo']);
            $stmt->bindParam(':unidade', $dados['unidade']);
            $stmt->bindParam(':estoque_minimo', $dados['estoque_minimo']);
            $stmt->bindParam(':estoque_atual', $dados['estoque_atual']);
            $stmt->bindParam(':ativo', $dados['ativo']);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->logAction($_SESSION['user_id'], 'editar_produto', $this->table, $id, $dados_antigos, $dados);
                return ['success' => true, 'message' => 'Produto atualizado com sucesso!'];
            } else {
                return ['success' => false, 'message' => 'Nenhuma alteração foi feita.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao editar produto: ' . $e->getMessage()];
        }
    }

    // Excluir produto
    public function excluirProduto($id, $empresa_id) {
        try {
            // Verificar se o produto está em algum orçamento
            $query = "SELECT COUNT(*) as total FROM orcamento_itens WHERE produto_id = :produto_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':produto_id', $id);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result['total'] > 0) {
                return ['success' => false, 'message' => 'Não é possível excluir este produto pois ele está sendo usado em orçamentos.'];
            }

            // Buscar dados atuais
            $result = $this->buscarProduto($id, $empresa_id);
            if (!$result['success']) {
                return $result;
            }
            $dados_antigos = $result['produto'];

            // Remover foto se existir
            if (!empty($dados_antigos['foto'])) {
                $this->removerFoto($dados_antigos['foto']);
            }

            $query = "DELETE FROM " . $this->table . " 
                     WHERE id = :id AND empresa_id = :empresa_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->logAction($_SESSION['user_id'], 'excluir_produto', $this->table, $id, $dados_antigos, null);
                return ['success' => true, 'message' => 'Produto excluído com sucesso!'];
            } else {
                return ['success' => false, 'message' => 'Produto não encontrado.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir produto: ' . $e->getMessage()];
        }
    }

    // Listar produtos para orçamento (apenas ativos)
    public function listarProdutosParaOrcamento($empresa_id, $categoria_id = null) {
        try {
            $where_conditions = ["p.empresa_id = :empresa_id", "p.ativo = 1"];
            $params = [':empresa_id' => $empresa_id];

            if ($categoria_id !== null) {
                $where_conditions[] = "p.categoria_id = :categoria_id";
                $params[':categoria_id'] = $categoria_id;
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query = "SELECT p.id, p.nome, p.descricao, p.preco, p.unidade, p.foto, c.nome as categoria_nome, c.cor as categoria_cor
                     FROM " . $this->table . " p 
                     LEFT JOIN " . $this->table_categorias . " c ON p.categoria_id = c.id
                     WHERE $where_clause 
                     ORDER BY c.nome ASC, p.nome ASC";
            
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

    // Gerenciar categorias
    public function listarCategorias($empresa_id, $ativo = null) {
        try {
            $where_conditions = ["empresa_id = :empresa_id"];
            $params = [':empresa_id' => $empresa_id];

            if ($ativo !== null) {
                $where_conditions[] = "ativo = :ativo";
                $params[':ativo'] = $ativo;
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query = "SELECT * FROM " . $this->table_categorias . " 
                     WHERE $where_clause 
                     ORDER BY nome ASC";
            
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

    public function adicionarCategoria($dados, $empresa_id) {
        try {
            $query = "INSERT INTO " . $this->table_categorias . " 
                     (empresa_id, nome, descricao, cor) 
                     VALUES (:empresa_id, :nome, :descricao, :cor)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':cor', $dados['cor']);
            $stmt->execute();

            $categoria_id = $this->conn->lastInsertId();
            
            $this->logAction($_SESSION['user_id'], 'adicionar_categoria', $this->table_categorias, $categoria_id, null, $dados);
            
            return ['success' => true, 'message' => 'Categoria adicionada com sucesso!', 'id' => $categoria_id];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao adicionar categoria: ' . $e->getMessage()];
        }
    }

    // Upload de foto
    private function uploadFoto($arquivo, $empresa_id) {
        try {
            // Criar diretório se não existir
            $upload_dir = __DIR__ . '/../uploads/produtos/' . $empresa_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Validar arquivo
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($arquivo['type'], $tipos_permitidos)) {
                return ['success' => false, 'message' => 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.'];
            }

            if ($arquivo['size'] > 5242880) { // 5MB
                return ['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB.'];
            }

            // Gerar nome único
            $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
            $nome_arquivo = uniqid() . '_' . time() . '.' . $extensao;
            $caminho_completo = $upload_dir . $nome_arquivo;

            if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
                return ['success' => true, 'filename' => $nome_arquivo];
            } else {
                return ['success' => false, 'message' => 'Erro ao fazer upload do arquivo.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro no upload: ' . $e->getMessage()];
        }
    }

    // Remover foto
    private function removerFoto($nome_arquivo) {
        if (empty($nome_arquivo)) return;
        
        $empresa_id = $_SESSION['empresa_id'];
        $caminho_arquivo = __DIR__ . '/../uploads/produtos/' . $empresa_id . '/' . $nome_arquivo;
        
        if (file_exists($caminho_arquivo)) {
            unlink($caminho_arquivo);
        }
    }

    // Estatísticas de produtos
    public function estatisticasProdutos($empresa_id) {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
                        SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) as inativos,
                        AVG(preco) as preco_medio,
                        COUNT(DISTINCT categoria_id) as total_categorias
                     FROM " . $this->table . " 
                     WHERE empresa_id = :empresa_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return [
                'total' => 0,
                'ativos' => 0,
                'inativos' => 0,
                'preco_medio' => 0,
                'total_categorias' => 0
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