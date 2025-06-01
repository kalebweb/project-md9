<?php
// public/clientes/editar.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Cliente.php';

$auth = new Auth();

// Verificar se está logado e tem permissão
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$cliente = new Cliente();
$error = '';
$success = '';

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: lista.php');
    exit;
}

$cliente_id = $_GET['id'];

// Buscar dados do cliente
$result = $cliente->buscarCliente($cliente_id, $_SESSION['empresa_id']);
if (!$result['success']) {
    header('Location: lista.php');
    exit;
}

$dados = $result['cliente'];

if ($_POST) {
    $dados_form = [
        'razao_social' => trim($_POST['razao_social']),
        'nome_fantasia' => trim($_POST['nome_fantasia']),
        'cnpj' => preg_replace('/[^0-9]/', '', $_POST['cnpj']),
        'responsavel_nome' => trim($_POST['responsavel_nome']),
        'responsavel_cargo' => trim($_POST['responsavel_cargo']),
        'telefone_empresa' => trim($_POST['telefone_empresa']),
        'telefone_responsavel' => trim($_POST['telefone_responsavel']),
        'email_empresa' => trim($_POST['email_empresa']),
        'email_responsavel' => trim($_POST['email_responsavel']),
        'endereco' => trim($_POST['endereco']),
        'cidade' => trim($_POST['cidade']),
        'estado' => trim($_POST['estado']),
        'cep' => preg_replace('/[^0-9]/', '', $_POST['cep']),
        'observacoes' => trim($_POST['observacoes']),
        'ativo' => isset($_POST['ativo']) ? 1 : 0
    ];
    
    // Validações básicas
    $campos_obrigatorios = ['razao_social', 'cnpj', 'responsavel_nome', 'email_responsavel'];
    $campos_vazios = [];
    
    foreach ($campos_obrigatorios as $campo) {
        if (empty($dados_form[$campo])) {
            $campos_vazios[] = $campo;
        }
    }
    
    if (!empty($campos_vazios)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        $result = $cliente->editarCliente($cliente_id, $dados_form, $_SESSION['empresa_id']);
        if ($result['success']) {
            $success = $result['message'];
            // Atualizar dados para mostrar na tela
            $result_updated = $cliente->buscarCliente($cliente_id, $_SESSION['empresa_id']);
            if ($result_updated['success']) {
                $dados = $result_updated['cliente'];
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Função para formatar CNPJ
function formatCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) == 14) {
        return substr($cnpj, 0, 2) . '.' . 
               substr($cnpj, 2, 3) . '.' . 
               substr($cnpj, 5, 3) . '/' . 
               substr($cnpj, 8, 4) . '-' . 
               substr($cnpj, 12, 2);
    }
    return $cnpj;
}

// Função para formatar CEP
function formatCEP($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    if (strlen($cep) == 8) {
        return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }
    return $cep;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .user-info a {
            color: white;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s;
        }
        
        .user-info a:hover {
            opacity: 1;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .breadcrumb {
            margin-bottom: 2rem;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .card-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-box h4 {
            color: #004085;
            margin-bottom: 0.5rem;
        }
        
        .info-box p {
            color: #004085;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row.single {
            grid-template-columns: 1fr;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .required {
            color: #e74c3c;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-help {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: normal;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .danger-zone {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .danger-zone h4 {
            color: #721c24;
            margin-bottom: 1rem;
        }
        
        .danger-zone p {
            color: #721c24;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo SITE_NAME; ?></h1>
            <div class="user-info">
                <a href="../dashboard.php">← Dashboard</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> / 
            <a href="lista.php">Clientes</a> / 
            <strong>Editar</strong>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Editar Cliente</h2>
                <p>Altere os dados de <?php echo htmlspecialchars($dados['razao_social']); ?></p>
            </div>
            
            <div class="card-body">
                <div class="info-box">
                    <h4>📋 Informações do Cliente</h4>
                    <p><strong>ID:</strong> <?php echo $dados['id']; ?> | 
                       <strong>Cadastrado em:</strong> <?php echo date('d/m/Y H:i', strtotime($dados['data_criacao'])); ?></p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="formEditarCliente">
                    <!-- Dados da Empresa Cliente -->
                    <div class="section">
                        <h3>🏢 Dados da Empresa</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="razao_social">Razão Social <span class="required">*</span></label>
                                <input type="text" id="razao_social" name="razao_social" required 
                                       value="<?php echo htmlspecialchars($dados['razao_social']); ?>"
                                       placeholder="Nome empresarial completo">
                            </div>
                            
                            <div class="form-group">
                                <label for="nome_fantasia">Nome Fantasia</label>
                                <input type="text" id="nome_fantasia" name="nome_fantasia" 
                                       value="<?php echo htmlspecialchars($dados['nome_fantasia']); ?>"
                                       placeholder="Nome comercial (opcional)">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cnpj">CNPJ <span class="required">*</span></label>
                                <input type="text" id="cnpj" name="cnpj" required 
                                       placeholder="00.000.000/0000-00"
                                       value="<?php echo formatCNPJ($dados['cnpj']); ?>">
                                <div class="form-help">Apenas números ou formato com pontuação</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email_empresa">Email da Empresa</label>
                                <input type="email" id="email_empresa" name="email_empresa" 
                                       placeholder="contato@empresa.com"
                                       value="<?php echo htmlspecialchars($dados['email_empresa']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefone_empresa">Telefone da Empresa</label>
                            <input type="tel" id="telefone_empresa" name="telefone_empresa" 
                                   placeholder="(00) 0000-0000"
                                   value="<?php echo htmlspecialchars($dados['telefone_empresa']); ?>">
                        </div>
                    </div>
                    
                    <!-- Dados do Responsável -->
                    <div class="section">
                        <h3>👤 Responsável pelo Contato</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="responsavel_nome">Nome do Responsável <span class="required">*</span></label>
                                <input type="text" id="responsavel_nome" name="responsavel_nome" required 
                                       value="<?php echo htmlspecialchars($dados['responsavel_nome']); ?>"
                                       placeholder="Nome completo">
                            </div>
                            
                            <div class="form-group">
                                <label for="responsavel_cargo">Cargo/Função</label>
                                <input type="text" id="responsavel_cargo" name="responsavel_cargo" 
                                       value="<?php echo htmlspecialchars($dados['responsavel_cargo']); ?>"
                                       placeholder="Ex: Gerente, Diretor, Proprietário">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email_responsavel">Email do Responsável <span class="required">*</span></label>
                                <input type="email" id="email_responsavel" name="email_responsavel" required 
                                       placeholder="responsavel@empresa.com"
                                       value="<?php echo htmlspecialchars($dados['email_responsavel']); ?>">
                                <div class="form-help">Email principal para envio de orçamentos</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefone_responsavel">Telefone do Responsável</label>
                                <input type="tel" id="telefone_responsavel" name="telefone_responsavel" 
                                       placeholder="(00) 00000-0000"
                                       value="<?php echo htmlspecialchars($dados['telefone_responsavel']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Endereço -->
                    <div class="section">
                        <h3>📍 Endereço</h3>
                        
                        <div class="form-group">
                            <label for="endereco">Endereço Completo</label>
                            <input type="text" id="endereco" name="endereco" 
                                   placeholder="Rua, número, complemento"
                                   value="<?php echo htmlspecialchars($dados['endereco']); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" 
                                       value="<?php echo htmlspecialchars($dados['cidade']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado">
                                    <option value="">Selecione...</option>
                                    <option value="AC" <?php echo $dados['estado'] == 'AC' ? 'selected' : ''; ?>>Acre</option>
                                    <option value="AL" <?php echo $dados['estado'] == 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                                    <option value="AP" <?php echo $dados['estado'] == 'AP' ? 'selected' : ''; ?>>Amapá</option>
                                    <option value="AM" <?php echo $dados['estado'] == 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                                    <option value="BA" <?php echo $dados['estado'] == 'BA' ? 'selected' : ''; ?>>Bahia</option>
                                    <option value="CE" <?php echo $dados['estado'] == 'CE' ? 'selected' : ''; ?>>Ceará</option>
                                    <option value="DF" <?php echo $dados['estado'] == 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                                    <option value="ES" <?php echo $dados['estado'] == 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                                    <option value="GO" <?php echo $dados['estado'] == 'GO' ? 'selected' : ''; ?>>Goiás</option>
                                    <option value="MA" <?php echo $dados['estado'] == 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                                    <option value="MT" <?php echo $dados['estado'] == 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                                    <option value="MS" <?php echo $dados['estado'] == 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                                    <option value="MG" <?php echo $dados['estado'] == 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                                    <option value="PA" <?php echo $dados['estado'] == 'PA' ? 'selected' : ''; ?>>Pará</option>
                                    <option value="PB" <?php echo $dados['estado'] == 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                                    <option value="PR" <?php echo $dados['estado'] == 'PR' ? 'selected' : ''; ?>>Paraná</option>
                                    <option value="PE" <?php echo $dados['estado'] == 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                                    <option value="PI" <?php echo $dados['estado'] == 'PI' ? 'selected' : ''; ?>>Piauí</option>
                                    <option value="RJ" <?php echo $dados['estado'] == 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                    <option value="RN" <?php echo $dados['estado'] == 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                                    <option value="RS" <?php echo $dados['estado'] == 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                                    <option value="RO" <?php echo $dados['estado'] == 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                                    <option value="RR" <?php echo $dados['estado'] == 'RR' ? 'selected' : ''; ?>>Roraima</option>
                                    <option value="SC" <?php echo $dados['estado'] == 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                                    <option value="SP" <?php echo $dados['estado'] == 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                                    <option value="SE" <?php echo $dados['estado'] == 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                                    <option value="TO" <?php echo $dados['estado'] == 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" 
                                   placeholder="00000-000"
                                   value="<?php echo formatCEP($dados['cep']); ?>">
                        </div>
                    </div>
                    
                    <!-- Observações -->
                    <div class="section">
                        <h3>📝 Observações</h3>
                        
                        <div class="form-group">
                            <label for="observacoes">Observações Gerais</label>
                            <textarea id="observacoes" name="observacoes" 
                                      placeholder="Informações adicionais sobre o cliente, preferências, histórico, etc."><?php echo htmlspecialchars($dados['observacoes']); ?></textarea>
                            <div class="form-help">Informações que podem ser úteis para orçamentos futuros</div>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="ativo" name="ativo" <?php echo $dados['ativo'] ? 'checked' : ''; ?>>
                            <label for="ativo">
                                <strong>Cliente Ativo</strong> - Pode ser usado em novos orçamentos
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="lista.php" class="btn btn-secondary">← Voltar</a>
                        <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                    </div>
                </form>
                
                <div class="danger-zone">
                    <h4>⚠️ Zona de Perigo</h4>
                    <p>As ações abaixo são irreversíveis. Tenha certeza antes de executá-las.</p>
                    
                    <?php if ($dados['ativo']): ?>
                        <button onclick="confirmarDesativar()" class="btn btn-danger">
                            🚫 Desativar Cliente
                        </button>
                    <?php else: ?>
                        <button onclick="confirmarAtivar()" class="btn btn-primary">
                            ✅ Ativar Cliente
                        </button>
                    <?php endif; ?>
                    
                    <button onclick="confirmarExcluir()" class="btn btn-danger" style="margin-left: 1rem;">
                        🗑️ Excluir Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Forms ocultos para ações da zona de perigo -->
    <form id="formDesativar" method="POST" style="display: none;" action="lista.php">
        <input type="hidden" name="acao" value="desativar">
        <input type="hidden" name="id" value="<?php echo $dados['id']; ?>">
    </form>
    
    <form id="formAtivar" method="POST" style="display: none;" action="lista.php">
        <input type="hidden" name="acao" value="ativar">
        <input type="hidden" name="id" value="<?php echo $dados['id']; ?>">
    </form>
    
    <form id="formExcluir" method="POST" style="display: none;" action="lista.php">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" value="<?php echo $dados['id']; ?>">
    </form>
    
    <script>
        // Máscaras para os campos
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
                e.target.value = value;
            }
        });
        
        document.getElementById('telefone_empresa').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
            } else {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            }
            e.target.value = value;
        });
        
        document.getElementById('telefone_responsavel').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
            } else {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            }
            e.target.value = value;
        });
        
        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d{3})$/, '$1-$2');
            e.target.value = value;
        });
        
        function confirmarDesativar() {
            if (confirm('Tem certeza que deseja DESATIVAR este cliente?\n\nEle não aparecerá mais na lista de clientes ativos para novos orçamentos.')) {
                document.getElementById('formDesativar').submit();
            }
        }
        
        function confirmarAtivar() {
            if (confirm('Tem certeza que deseja ATIVAR este cliente?\n\nEle poderá ser usado em novos orçamentos.')) {
                document.getElementById('formAtivar').submit();
            }
        }
        
        function confirmarExcluir() {
            const nome = '<?php echo htmlspecialchars($dados['razao_social']); ?>';
            if (confirm(`ATENÇÃO: Você está prestes a EXCLUIR PERMANENTEMENTE o cliente "${nome}".\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza que deseja continuar?`)) {
                if (confirm('ÚLTIMA CONFIRMAÇÃO:\n\nVocê tem ABSOLUTA CERTEZA que deseja excluir este cliente?\n\nClique OK para excluir PERMANENTEMENTE.')) {
                    document.getElementById('formExcluir').submit();
                }
            }
        }
        
        // Auto-hide success alerts
        setTimeout(function() {
            const successAlerts = document.querySelectorAll('.alert-success');
            successAlerts.forEach(function(alert) {
                alert.style.opacity = '0.7';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 3000);
            });
        }, 3000);
        
        // Validação do formulário
        document.getElementById('formEditarCliente').addEventListener('submit', function(e) {
            const cnpj = document.getElementById('cnpj').value.replace(/\D/g, '');
            const emailResponsavel = document.getElementById('email_responsavel').value;
            
            if (cnpj.length !== 14) {
                e.preventDefault();
                alert('CNPJ deve ter 14 dígitos.');
                return;
            }
            
            if (!emailResponsavel || !emailResponsavel.includes('@')) {
                e.preventDefault();
                alert('Email do responsável é obrigatório e deve ser válido.');
                return;
            }
        });
        
        // Buscar endereço por CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            if (!document.getElementById('endereco').value) {
                                document.getElementById('endereco').value = data.logradouro + (data.complemento ? ', ' + data.complemento : '');
                            }
                            if (!document.getElementById('cidade').value) {
                                document.getElementById('cidade').value = data.localidade;
                            }
                            if (!document.getElementById('estado').value) {
                                document.getElementById('estado').value = data.uf;
                            }
                        }
                    })
                    .catch(error => {
                        console.log('Erro ao buscar CEP:', error);
                    });
            }
        });
    </script>
</body>
</html>