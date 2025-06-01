<?php
// public/colaboradores/editar.php
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

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: lista.php');
    exit;
}

$colaborador_id = $_GET['id'];

// Buscar dados do colaborador
$result = $colaborador->buscarColaborador($colaborador_id, $_SESSION['empresa_id']);
if (!$result['success']) {
    header('Location: lista.php');
    exit;
}

$dados = $result['colaborador'];

if ($_POST) {
    $dados_form = [
        'nome' => trim($_POST['nome']),
        'email' => trim($_POST['email']),
        'senha' => $_POST['senha'],
        'tipo' => $_POST['tipo'],
        'ativo' => isset($_POST['ativo']) ? 1 : 0
    ];
    
    // Validações básicas
    if (empty($dados_form['nome']) || empty($dados_form['email']) || empty($dados_form['tipo'])) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        $result = $colaborador->editarColaborador($colaborador_id, $dados_form, $_SESSION['empresa_id']);
        if ($result['success']) {
            $success = $result['message'];
            // Atualizar dados para mostrar na tela
            $result_updated = $colaborador->buscarColaborador($colaborador_id, $_SESSION['empresa_id']);
            if ($result_updated['success']) {
                $dados = $result_updated['colaborador'];
            }
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
    <title>Editar Colaborador - <?php echo SITE_NAME; ?></title>
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
        
        .password-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .password-section h4 {
            color: #856404;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
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
            <a href="lista.php">Colaboradores</a> / 
            <strong>Editar</strong>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Editar Colaborador</h2>
                <p>Altere os dados do usuário <?php echo htmlspecialchars($dados['nome']); ?></p>
            </div>
            
            <div class="card-body">
                <div class="info-box">
                    <h4>📋 Informações do Colaborador</h4>
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
                
                <form method="POST" id="formEditarColaborador">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome Completo <span class="required">*</span></label>
                            <input type="text" id="nome" name="nome" required 
                                   value="<?php echo htmlspecialchars($dados['nome']); ?>"
                                   placeholder="Digite o nome completo">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($dados['email']); ?>"
                                   placeholder="usuario@email.com">
                            <div class="form-help">Este é o login do usuário</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">Tipo de Usuário <span class="required">*</span></label>
                        <select id="tipo" name="tipo" required>
                            <option value="admin_empresa" <?php echo $dados['tipo'] == 'admin_empresa' ? 'selected' : ''; ?>>
                                👑 Administrador da Empresa
                            </option>
                            <option value="colaborador" <?php echo $dados['tipo'] == 'colaborador' ? 'selected' : ''; ?>>
                                👤 Colaborador
                            </option>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="ativo" name="ativo" <?php echo $dados['ativo'] ? 'checked' : ''; ?>>
                        <label for="ativo">
                            <strong>Usuário Ativo</strong> - Pode acessar o sistema
                        </label>
                    </div>
                    
                    <div class="password-section">
                        <h4>🔒 Alterar Senha (Opcional)</h4>
                        <div class="form-group">
                            <label for="senha">Nova Senha</label>
                            <input type="password" id="senha" name="senha" 
                                   placeholder="Deixe vazio para manter a senha atual"
                                   onkeyup="verificarForcaSenha(this.value)">
                            <div class="form-help">Deixe em branco se não quiser alterar a senha</div>
                            <div class="password-strength" style="display: none;" id="passwordStrength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="lista.php" class="btn btn-secondary">← Voltar</a>
                        <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                    </div>
                </form>
                
                <?php if ($dados['id'] != $_SESSION['user_id']): ?>
                    <div class="danger-zone">
                        <h4>⚠️ Zona de Perigo</h4>
                        <p>As ações abaixo são irreversíveis. Tenha certeza antes de executá-las.</p>
                        
                        <?php if ($dados['ativo']): ?>
                            <button onclick="confirmarDesativar()" class="btn btn-danger">
                                🚫 Desativar Colaborador
                            </button>
                        <?php else: ?>
                            <button onclick="confirmarAtivar()" class="btn btn-primary">
                                ✅ Ativar Colaborador
                            </button>
                        <?php endif; ?>
                        
                        <button onclick="confirmarExcluir()" class="btn btn-danger" style="margin-left: 1rem;">
                            🗑️ Excluir Colaborador
                        </button>
                    </div>
                <?php endif; ?>
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
        function verificarForcaSenha(senha) {
            const passwordStrength = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            if (senha.length === 0) {
                passwordStrength.style.display = 'none';
                return;
            }
            
            passwordStrength.style.display = 'block';
            
            let forca = 0;
            let texto = '';
            let cor = '';
            
            if (senha.length < 6) {
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
        
        function confirmarDesativar() {
            if (confirm('Tem certeza que deseja DESATIVAR este colaborador?\n\nEle não conseguirá mais acessar o sistema.')) {
                document.getElementById('formDesativar').submit();
            }
        }
        
        function confirmarAtivar() {
            if (confirm('Tem certeza que deseja ATIVAR este colaborador?\n\nEle poderá acessar o sistema normalmente.')) {
                document.getElementById('formAtivar').submit();
            }
        }
        
        function confirmarExcluir() {
            const nome = '<?php echo htmlspecialchars($dados['nome']); ?>';
            if (confirm(`ATENÇÃO: Você está prestes a EXCLUIR PERMANENTEMENTE o colaborador "${nome}".\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza que deseja continuar?`)) {
                if (confirm('ÚLTIMA CONFIRMAÇÃO:\n\nVocê tem ABSOLUTA CERTEZA que deseja excluir este colaborador?\n\nClique OK para excluir PERMANENTEMENTE.')) {
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
        document.getElementById('formEditarColaborador').addEventListener('submit', function(e) {
            const senha = document.getElementById('senha').value;
            
            if (senha.length > 0 && senha.length < 6) {
                e.preventDefault();
                alert('Se você quiser alterar a senha, ela deve ter pelo menos 6 caracteres.');
                return;
            }
        });
    </script>
</body>
</html>