<?php
// public/clientes/adicionar.php
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

if ($_POST) {
    $dados = [
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
        'observacoes' => trim($_POST['observacoes'])
    ];
    
    // Validações básicas
    $campos_obrigatorios = ['razao_social', 'cnpj', 'responsavel_nome', 'email_responsavel'];
    $campos_vazios = [];
    
    foreach ($campos_obrigatorios as $campo) {
        if (empty($dados[$campo])) {
            $campos_vazios[] = $campo;
        }
    }
    
    if (!empty($campos_vazios)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        $result = $cliente->adicionarCliente($dados, $_SESSION['empresa_id']);
        if ($result['success']) {
            $success = $result['message'];
            // Limpar campos após sucesso
            $dados = array_fill_keys(array_keys($dados), '');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Cliente - <?php echo SITE_NAME; ?></title>
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
            <strong>Adicionar</strong>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Adicionar Novo Cliente</h2>
                <p>Cadastre um cliente para poder criar orçamentos</p>
            </div>
            
            <div class="card-body">
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
                
                <form method="POST" id="formCliente">
                    <!-- Dados da Empresa Cliente -->
                    <div class="section">
                        <h3>🏢 Dados da Empresa</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="razao_social">Razão Social <span class="required">*</span></label>
                                <input type="text" id="razao_social" name="razao_social" required 
                                       value="<?php echo isset($dados['razao_social']) ? htmlspecialchars($dados['razao_social']) : ''; ?>"
                                       placeholder="Nome empresarial completo">
                            </div>
                            
                            <div class="form-group">
                                <label for="nome_fantasia">Nome Fantasia</label>
                                <input type="text" id="nome_fantasia" name="nome_fantasia" 
                                       value="<?php echo isset($dados['nome_fantasia']) ? htmlspecialchars($dados['nome_fantasia']) : ''; ?>"
                                       placeholder="Nome comercial (opcional)">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cnpj">CNPJ <span class="required">*</span></label>
                                <input type="text" id="cnpj" name="cnpj" required 
                                       placeholder="00.000.000/0000-00"
                                       value="<?php echo isset($dados['cnpj']) ? htmlspecialchars($dados['cnpj']) : ''; ?>">
                                <div class="form-help">Apenas números ou formato com pontuação</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email_empresa">Email da Empresa</label>
                                <input type="email" id="email_empresa" name="email_empresa" 
                                       placeholder="contato@empresa.com"
                                       value="<?php echo isset($dados['email_empresa']) ? htmlspecialchars($dados['email_empresa']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefone_empresa">Telefone da Empresa</label>
                            <input type="tel" id="telefone_empresa" name="telefone_empresa" 
                                   placeholder="(00) 0000-0000"
                                   value="<?php echo isset($dados['telefone_empresa']) ? htmlspecialchars($dados['telefone_empresa']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Dados do Responsável -->
                    <div class="section">
                        <h3>👤 Responsável pelo Contato</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="responsavel_nome">Nome do Responsável <span class="required">*</span></label>
                                <input type="text" id="responsavel_nome" name="responsavel_nome" required 
                                       value="<?php echo isset($dados['responsavel_nome']) ? htmlspecialchars($dados['responsavel_nome']) : ''; ?>"
                                       placeholder="Nome completo">
                            </div>
                            
                            <div class="form-group">
                                <label for="responsavel_cargo">Cargo/Função</label>
                                <input type="text" id="responsavel_cargo" name="responsavel_cargo" 
                                       value="<?php echo isset($dados['responsavel_cargo']) ? htmlspecialchars($dados['responsavel_cargo']) : ''; ?>"
                                       placeholder="Ex: Gerente, Diretor, Proprietário">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email_responsavel">Email do Responsável <span class="required">*</span></label>
                                <input type="email" id="email_responsavel" name="email_responsavel" required 
                                       placeholder="responsavel@empresa.com"
                                       value="<?php echo isset($dados['email_responsavel']) ? htmlspecialchars($dados['email_responsavel']) : ''; ?>">
                                <div class="form-help">Email principal para envio de orçamentos</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefone_responsavel">Telefone do Responsável</label>
                                <input type="tel" id="telefone_responsavel" name="telefone_responsavel" 
                                       placeholder="(00) 00000-0000"
                                       value="<?php echo isset($dados['telefone_responsavel']) ? htmlspecialchars($dados['telefone_responsavel']) : ''; ?>">
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
                                   value="<?php echo isset($dados['endereco']) ? htmlspecialchars($dados['endereco']) : ''; ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" 
                                       value="<?php echo isset($dados['cidade']) ? htmlspecialchars($dados['cidade']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado">
                                    <option value="">Selecione...</option>
                                    <option value="AC" <?php echo (isset($dados['estado']) && $dados['estado'] == 'AC') ? 'selected' : ''; ?>>Acre</option>
                                    <option value="AL" <?php echo (isset($dados['estado']) && $dados['estado'] == 'AL') ? 'selected' : ''; ?>>Alagoas</option>
                                    <option value="AP" <?php echo (isset($dados['estado']) && $dados['estado'] == 'AP') ? 'selected' : ''; ?>>Amapá</option>
                                    <option value="AM" <?php echo (isset($dados['estado']) && $dados['estado'] == 'AM') ? 'selected' : ''; ?>>Amazonas</option>
                                    <option value="BA" <?php echo (isset($dados['estado']) && $dados['estado'] == 'BA') ? 'selected' : ''; ?>>Bahia</option>
                                    <option value="CE" <?php echo (isset($dados['estado']) && $dados['estado'] == 'CE') ? 'selected' : ''; ?>>Ceará</option>
                                    <option value="DF" <?php echo (isset($dados['estado']) && $dados['estado'] == 'DF') ? 'selected' : ''; ?>>Distrito Federal</option>
                                    <option value="ES" <?php echo (isset($dados['estado']) && $dados['estado'] == 'ES') ? 'selected' : ''; ?>>Espírito Santo</option>
                                    <option value="GO" <?php echo (isset($dados['estado']) && $dados['estado'] == 'GO') ? 'selected' : ''; ?>>Goiás</option>
                                    <option value="MA" <?php echo (isset($dados['estado']) && $dados['estado'] == 'MA') ? 'selected' : ''; ?>>Maranhão</option>
                                    <option value="MT" <?php echo (isset($dados['estado']) && $dados['estado'] == 'MT') ? 'selected' : ''; ?>>Mato Grosso</option>
                                    <option value="MS" <?php echo (isset($dados['estado']) && $dados['estado'] == 'MS') ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                                    <option value="MG" <?php echo (isset($dados['estado']) && $dados['estado'] == 'MG') ? 'selected' : ''; ?>>Minas Gerais</option>
                                    <option value="PA" <?php echo (isset($dados['estado']) && $dados['estado'] == 'PA') ? 'selected' : ''; ?>>Pará</option>
                                    <option value="PB" <?php echo (isset($dados['estado']) && $dados['estado'] == 'PB') ? 'selected' : ''; ?>>Paraíba</option>
                                    <option value="PR" <?php echo (isset($dados['estado']) && $dados['estado'] == 'PR') ? 'selected' : ''; ?>>Paraná</option>
                                    <option value="PE" <?php echo (isset($dados['estado']) && $dados['estado'] == 'PE') ? 'selected' : ''; ?>>Pernambuco</option>
                                    <option value="PI" <?php echo (isset($dados['estado']) && $dados['estado'] == 'PI') ? 'selected' : ''; ?>>Piauí</option>
                                    <option value="RJ" <?php echo (isset($dados['estado']) && $dados['estado'] == 'RJ') ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                    <option value="RN" <?php echo (isset($dados['estado']) && $dados['estado'] == 'RN') ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                                    <option value="RS" <?php echo (isset($dados['estado']) && $dados['estado'] == 'RS') ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                                    <option value="RO" <?php echo (isset($dados['estado']) && $dados['estado'] == 'RO') ? 'selected' : ''; ?>>Rondônia</option>
                                    <option value="RR" <?php echo (isset($dados['estado']) && $dados['estado'] == 'RR') ? 'selected' : ''; ?>>Roraima</option>
                                    <option value="SC" <?php echo (isset($dados['estado']) && $dados['estado'] == 'SC') ? 'selected' : ''; ?>>Santa Catarina</option>
                                    <option value="SP" <?php echo (isset($dados['estado']) && $dados['estado'] == 'SP') ? 'selected' : ''; ?>>São Paulo</option>
                                    <option value="SE" <?php echo (isset($dados['estado']) && $dados['estado'] == 'SE') ? 'selected' : ''; ?>>Sergipe</option>
                                    <option value="TO" <?php echo (isset($dados['estado']) && $dados['estado'] == 'TO') ? 'selected' : ''; ?>>Tocantins</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" 
                                   placeholder="00000-000"
                                   value="<?php echo isset($dados['cep']) ? htmlspecialchars($dados['cep']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Observações -->
                    <div class="section">
                        <h3>📝 Observações</h3>
                        
                        <div class="form-group">
                            <label for="observacoes">Observações Gerais</label>
                            <textarea id="observacoes" name="observacoes" 
                                      placeholder="Informações adicionais sobre o cliente, preferências, histórico, etc."><?php echo isset($dados['observacoes']) ? htmlspecialchars($dados['observacoes']) : ''; ?></textarea>
                            <div class="form-help">Informações que podem ser úteis para orçamentos futuros</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="lista.php" class="btn btn-secondary">← Cancelar</a>
                        <button type="submit" class="btn btn-primary">✅ Cadastrar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
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
        document.getElementById('formCliente').addEventListener('submit', function(e) {
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
                            document.getElementById('endereco').value = data.logradouro + (data.complemento ? ', ' + data.complemento : '');
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
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