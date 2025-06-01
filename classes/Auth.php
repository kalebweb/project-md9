<?php
// classes/Auth.php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    private $table_usuarios = 'usuarios';
    private $table_empresas = 'empresas';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Login do usuário
    public function login($email, $senha) {
        try {
            $query = "SELECT u.*, e.razao_social, e.plano, e.ativo as empresa_ativa 
                     FROM " . $this->table_usuarios . " u 
                     LEFT JOIN " . $this->table_empresas . " e ON u.empresa_id = e.id 
                     WHERE u.email = :email AND u.ativo = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                
                if (password_verify($senha, $user['senha'])) {
                    // Verificar se empresa está ativa (exceto super admin)
                    if ($user['tipo'] != 'super_admin' && !$user['empresa_ativa']) {
                        return ['success' => false, 'message' => 'Empresa inativa. Entre em contato com o suporte.'];
                    }

                    // Criar sessão
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nome'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user['tipo'];
                    $_SESSION['empresa_id'] = $user['empresa_id'];
                    $_SESSION['empresa_nome'] = $user['razao_social'];
                    $_SESSION['empresa_plano'] = $user['plano'];
                    $_SESSION['logged_in'] = true;

                    $this->logAction($user['id'], 'login', 'usuarios', $user['id']);
                    
                    return ['success' => true, 'user' => $user];
                } else {
                    return ['success' => false, 'message' => 'Senha incorreta.'];
                }
            } else {
                return ['success' => false, 'message' => 'Usuário não encontrado.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    // Logout
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logAction($_SESSION['user_id'], 'logout', 'usuarios', $_SESSION['user_id']);
        }
        session_destroy();
        return true;
    }

    // Verificar se está logado
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Verificar permissão
    public function hasPermission($required_type) {
        if (!$this->isLoggedIn()) return false;
        
        $user_type = $_SESSION['user_type'];
        
        switch ($required_type) {
            case 'super_admin':
                return $user_type === 'super_admin';
            case 'admin':
                return in_array($user_type, ['super_admin', 'admin_empresa']);
            case 'user':
                return in_array($user_type, ['super_admin', 'admin_empresa', 'colaborador']);
            default:
                return false;
        }
    }

    // Cadastrar empresa e admin
    public function cadastrarEmpresa($dados) {
        try {
            $this->conn->beginTransaction();

            // Verificar se CNPJ já existe
            $query = "SELECT id FROM " . $this->table_empresas . " WHERE cnpj = :cnpj";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cnpj', $dados['cnpj']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'CNPJ já cadastrado no sistema.'];
            }

            // Verificar se email já existe
            $query = "SELECT id FROM " . $this->table_usuarios . " WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $dados['admin_email']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Email já cadastrado no sistema.'];
            }

            // Inserir empresa
            $query = "INSERT INTO " . $this->table_empresas . " 
                     (razao_social, nome_fantasia, cnpj, email, telefone, endereco, cidade, estado, cep) 
                     VALUES (:razao_social, :nome_fantasia, :cnpj, :email, :telefone, :endereco, :cidade, :estado, :cep)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':razao_social', $dados['razao_social']);
            $stmt->bindParam(':nome_fantasia', $dados['nome_fantasia']);
            $stmt->bindParam(':cnpj', $dados['cnpj']);
            $stmt->bindParam(':email', $dados['empresa_email']);
            $stmt->bindParam(':telefone', $dados['telefone']);
            $stmt->bindParam(':endereco', $dados['endereco']);
            $stmt->bindParam(':cidade', $dados['cidade']);
            $stmt->bindParam(':estado', $dados['estado']);
            $stmt->bindParam(':cep', $dados['cep']);
            $stmt->execute();

            $empresa_id = $this->conn->lastInsertId();

            // Inserir usuário admin
            $senha_hash = password_hash($dados['admin_senha'], PASSWORD_DEFAULT);
            
            $query = "INSERT INTO " . $this->table_usuarios . " 
                     (nome, email, senha, tipo, empresa_id) 
                     VALUES (:nome, :email, :senha, 'admin_empresa', :empresa_id)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $dados['admin_nome']);
            $stmt->bindParam(':email', $dados['admin_email']);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->execute();

            $this->conn->commit();
            
            $this->logAction(null, 'cadastro_empresa', 'empresas', $empresa_id);
            
            return ['success' => true, 'message' => 'Empresa cadastrada com sucesso!'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro ao cadastrar empresa: ' . $e->getMessage()];
        }
    }

    // Adicionar colaborador
    public function adicionarColaborador($dados) {
        try {
            // Verificar se email já existe
            $query = "SELECT id FROM " . $this->table_usuarios . " WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email já cadastrado no sistema.'];
            }

            $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $query = "INSERT INTO " . $this->table_usuarios . " 
                     (nome, email, senha, tipo, empresa_id) 
                     VALUES (:nome, :email, :senha, 'colaborador', :empresa_id)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':empresa_id', $_SESSION['empresa_id']);
            $stmt->execute();

            $this->logAction($_SESSION['user_id'], 'adicionar_colaborador', 'usuarios', $this->conn->lastInsertId());
            
            return ['success' => true, 'message' => 'Colaborador adicionado com sucesso!'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao adicionar colaborador: ' . $e->getMessage()];
        }
    }

    // Log de ações
    private function logAction($user_id, $acao, $tabela, $registro_id, $dados_antigos = null, $dados_novos = null) {
        try {
            $query = "INSERT INTO logs_sistema 
                     (usuario_id, empresa_id, acao, tabela_afetada, registro_id, dados_antigos, dados_novos, ip_address, user_agent) 
                     VALUES (:usuario_id, :empresa_id, :acao, :tabela, :registro_id, :dados_antigos, :dados_novos, :ip, :user_agent)";
            
            $stmt = $this->conn->prepare($query);
            
            // Criar variáveis para bindParam (não pode ser valor direto)
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

    // Validar CNPJ (função básica)
    public function validarCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14) return false;
        
        // Verificar se não são todos números iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
        
        // Aqui você pode implementar a validação completa do CNPJ
        // Por simplicidade, retorno true se tem 14 dígitos
        return true;
    }
}
?>