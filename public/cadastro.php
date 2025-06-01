<?php
// public/cadastro.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// Se já está logado, redirecionar
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $dados = [
        'razao_social' => trim($_POST['razao_social']),
        'nome_fantasia' => trim($_POST['nome_fantasia']),
        'cnpj' => preg_replace('/[^0-9]/', '', $_POST['cnpj']),
        'empresa_email' => trim($_POST['empresa_email']),
        'telefone' => trim($_POST['telefone']),
        'endereco' => trim($_POST['endereco']),
        'cidade' => trim($_POST['cidade']),
        'estado' => trim($_POST['estado']),
        'cep' => preg_replace('/[^0-9]/', '', $_POST['cep']),
        'admin_nome' => trim($_POST['admin_nome']),
        'admin_email' => trim($_POST['admin_email']),
        'admin_senha' => $_POST['admin_senha']
    ];
    
    // Validações básicas
    $campos_obrigatorios = ['razao_social', 'cnpj', 'empresa_email', 'admin_nome', 'admin_email', 'admin_senha'];
    $campos_vazios = [];
    
    foreach ($campos_obrigatorios as $campo) {
        if (empty($dados[$campo])) {
            $campos_vazios[] = $campo;
        }
    }
    
    if (!empty($campos_vazios)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } elseif (!$auth->validarCNPJ($dados['cnpj'])) {
        $error = 'CNPJ inválido.';
    } elseif (!filter_var($dados['empresa_email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Email da empresa inválido.';
    } elseif (!filter_var($dados['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Email do administrador inválido.';
    } elseif (strlen($dados['admin_senha']) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        $result = $auth->cadastrarEmpresa($dados);
        if ($result['success']) {
            $success = $result['message'] . ' Você pode fazer login agora.';
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
    <title>Cadastro de Empresa - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .form-container {
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
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group.full {
            flex: 100%;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        label .required {
            color: #e74c3c;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background-color: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cadastro de Empresa</h1>
            <p>Crie sua conta e comece a usar nosso sistema de orçamentos</p>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="cadastroForm">
                <!-- Dados da Empresa -->
                <div class="section">
                    <h3>Dados da Empresa</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="razao_social">Razão Social <span class="required">*</span></label>
                            <input type="text" id="razao_social" name="razao_social" required 
                                   value="<?php echo isset($_POST['razao_social']) ? htmlspecialchars($_POST['razao_social']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="nome_fantasia">Nome Fantasia</label>
                            <input type="text" id="nome_fantasia" name="nome_fantasia" 
                                   value="<?php echo isset($_POST['nome_fantasia']) ? htmlspecialchars($_POST['nome_fantasia']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cnpj">CNPJ <span class="required">*</span></label>
                            <input type="text" id="cnpj" name="cnpj" required placeholder="00.000.000/0000-00"
                                   value="<?php echo isset($_POST['cnpj']) ? htmlspecialchars($_POST['cnpj']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="empresa_email">Email da Empresa <span class="required">*</span></label>
                            <input type="email" id="empresa_email" name="empresa_email" required 
                                   value="<?php echo isset($_POST['empresa_email']) ? htmlspecialchars($_POST['empresa_email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000"
                                   value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" placeholder="00000-000"
                                   value="<?php echo isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group full">
                        <label for="endereco">Endereço</label>
                        <input type="text" id="endereco" name="endereco" 
                               value="<?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" 
                                   value="<?php echo isset($_POST['cidade']) ? htmlspecialchars($_POST['cidade']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="">Selecione...</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Dados do Administrador -->
                <div class="section">
                    <h3>Dados do Administrador</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin_nome">Nome Completo <span class="required">*</span></label>
                            <input type="text" id="admin_nome" name="admin_nome" required 
                                   value="<?php echo isset($_POST['admin_nome']) ? htmlspecialchars($_POST['admin_nome']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="admin_email">Email <span class="required">*</span></label>
                            <input type="email" id="admin_email" name="admin_email" required 
                                   value="<?php echo isset($_POST['admin_email']) ? htmlspecialchars($_POST['admin_email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_senha">Senha <span class="required">*</span></label>
                        <input type="password" id="admin_senha" name="admin_senha" required 
                               placeholder="Mínimo 6 caracteres">
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="login.php" class="btn btn-secondary">« Voltar ao Login</a>
                    <button type="submit" class="btn btn-primary">Cadastrar Empresa</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Máscaras para os campos
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
            e.target.value = value;
        });
        
        document.getElementById('telefone').addEventListener('input', function(e) {
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
        
        // Manter valor selecionado do estado
        <?php if (isset($_POST['estado'])): ?>
        document.getElementById('estado').value = '<?php echo $_POST['estado']; ?>';
        <?php endif; ?>
    </script>
</body>
</html>