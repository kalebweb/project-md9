<?php
// public/colaboradores/adicionar.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Colaborador.php';

$auth = new Auth();

// Verificar se está logado e tem permissão
if (!$auth->isLoggedIn() || !$auth->hasPermission('admin')) {
    header('Location: ../login.php');
    exit;
}

$colaborador = new Colaborador();
$error = '';
$success = '';

if ($_POST) {
    $dados = [
        'nome' => trim($_POST['nome']),
        'email' => trim($_POST['email']),
        'senha' => $_POST['senha'],
        'tipo' => $_POST['tipo']
    ];
    
    // Validações básicas
    if (empty($dados['nome']) || empty($dados['email']) || empty($dados['senha']) || empty($dados['tipo'])) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        $result = $colaborador->adicionarColaborador($dados, $_SESSION['empresa_id']);
        if ($result['success']) {
            $success = $result['message'];
            // Limpar campos após sucesso
            $dados = ['nome' => '', 'email' => '', 'senha' => '', 'tipo' => ''];
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
    <title>Adicionar Colaborador - <?php echo SITE_NAME; ?></title>
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
            max-width: 800px;
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
        input[type="password"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-help {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .tipo-info {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        
        .tipo-info h4 {
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .tipo-info ul {
            margin: 0;
            padding-left: 1.5rem;
            font-size: 0.875rem;
            color: #666;
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
        
        .password-strength {
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 5px;
            border-radius: 3px;
            background: #e9ecef;
            margin-bottom: 0.25rem;
        }
        
        .strength-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .strength-text {
            font-size: 0.8rem;
            font-weight: 500;
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
            <a href="lista.php">Colaboradores</a> / 
            <strong>Adicionar</strong>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Adicionar Novo Colaborador</h2>
                <p>Cadastre um novo usuário para acessar o sistema</p>
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
                
                <form method="POST" id="formColaborador">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome Completo <span class="required">*</span></label>
                            <input type="text" id="nome" name="nome" required 
                                   value="<?php echo isset($dados['nome']) ? htmlspecialchars($dados['nome']) : ''; ?>"
                                   placeholder="Digite o nome completo">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo isset($dados['email']) ? htmlspecialchars($dados['email']) : ''; ?>"
                                   placeholder="usuario@email.com">
                            <div class="form-help">Este será o login do usuário</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="senha">Senha <span class="required">*</span></label>
                            <input type="password" id="senha" name="senha" required 
                                   placeholder="Mínimo 6 caracteres"
                                   onkeyup="verificarForcaSenha(this.value)">
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText">Digite uma senha</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo">Tipo de Usuário <span class="required">*</span></label>
                            <select id="tipo" name="tipo" required onchange="mostrarInfoTipo(this.value)">
                                <option value="">Selecione...</option>
                                <option value="admin_empresa" <?php echo (isset($dados['tipo']) && $dados['tipo'] == 'admin_empresa') ? 'selected' : ''; ?>>
                                    Administrador
                                </option>
                                <option value="colaborador" <?php echo (isset($dados['tipo']) && $dados['tipo'] == 'colaborador') ? 'selected' : ''; ?>>
                                    Colaborador
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="tipo-info" id="tipoInfo" style="display: none;">
                        <div id="adminInfo" style="display: none;">
                            <h4>👑 Administrador da Empresa</h4>
                            <ul>
                                <li>Pode gerenciar todos os colaboradores</li>
                                <li>Acesso a dados da empresa e configurações</li>
                                <li>Pode fazer upgrade do plano</li>
                                <li>Visualiza todos os orçamentos da empresa</li>
                            </ul>
                        </div>
                        
                        <div id="colaboradorInfo" style="display: none;">
                            <h4>👤 Colaborador</h4>
                            <ul>
                                <li>Pode criar e gerenciar seus próprios orçamentos</li>
                                <li>Visualiza apenas orçamentos criados por ele</li>
                                <li>Acesso limitado às configurações</li>
                                <li>Não pode gerenciar outros usuários</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="lista.php" class="btn btn-secondary">← Cancelar</a>
                        <button type="submit" class="btn btn-primary">✅ Adicionar Colaborador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function verificarForcaSenha(senha) {
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let forca = 0;
            let texto = '';
            let cor = '';
            
            if (senha.length === 0) {
                texto = 'Digite uma senha';
                cor = '#e9ecef';
            } else if (senha.length < 6) {
                forca = 20;
                texto = 'Muito fraca';
                cor = '#dc3545';
            } else {
                forca = 40;
                texto = 'Fraca';
                cor = '#fd7e14';
                
                if (senha.length >= 8) forca += 20;
                if (/[A-Z]/.test(senha)) forca += 15;
                if (/[0-9]/.test(senha)) forca += 15;
                if (/[^A-Za-z0-9]/.test(senha)) forca += 10;
                
                if (forca >= 80) {
                    texto = 'Muito forte';
                    cor = '#198754';
                } else if (forca >= 60) {
                    texto = 'Forte';
                    cor = '#20c997';
                } else if (forca >= 40) {
                    texto = 'Moderada';
                    cor = '#ffc107';
                }
            }
            
            strengthFill.style.width = forca + '%';
            strengthFill.style.backgroundColor = cor;
            strengthText.textContent = texto;
            strengthText.style.color = cor;
        }
        
        function mostrarInfoTipo(tipo) {
            const tipoInfo = document.getElementById('tipoInfo');
            const adminInfo = document.getElementById('adminInfo');
            const colaboradorInfo = document.getElementById('colaboradorInfo');
            
            // Esconder todas as infos
            adminInfo.style.display = 'none';
            colaboradorInfo.style.display = 'none';
            
            if (tipo === 'admin_empresa') {
                tipoInfo.style.display = 'block';
                adminInfo.style.display = 'block';
            } else if (tipo === 'colaborador') {
                tipoInfo.style.display = 'block';
                colaboradorInfo.style.display = 'block';
            } else {
                tipoInfo.style.display = 'none';
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
        document.getElementById('formColaborador').addEventListener('submit', function(e) {
            const senha = document.getElementById('senha').value;
            const tipo = document.getElementById('tipo').value;
            
            if (senha.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres.');
                return;
            }
            
            if (!tipo) {
                e.preventDefault();
                alert('Selecione o tipo de usuário.');
                return;
            }
        });
    </script>
</body>
</html>