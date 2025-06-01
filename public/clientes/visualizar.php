<?php
// public/clientes/visualizar.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Cliente.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();

// Verificar se está logado e tem permissão
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$cliente = new Cliente();

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: lista.php');
    exit;
}

$cliente_id = $_GET['id'];
$result = $cliente->buscarCliente($cliente_id, $_SESSION['empresa_id']);
if (!$result['success']) {
    header('Location: lista.php');
    exit;
}
$dados = $result['cliente'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Cliente - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; }
        .user-info a { color: white; text-decoration: none; opacity: 0.9; transition: opacity 0.3s; }
        .user-info a:hover { opacity: 1; }
        .container { max-width: 1000px; margin: 0 auto; padding: 2rem; }
        .breadcrumb { margin-bottom: 2rem; }
        .breadcrumb a { color: #667eea; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; text-align: center; }
        .card-header h2 { margin: 0; font-size: 1.8rem; }
        .card-header p { margin: 0.5rem 0 0 0; opacity: 0.9; }
        .card-body { padding: 2rem; }
        .section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #eee; }
        .section:last-child { border-bottom: none; margin-bottom: 0; }
        .section h3 { color: #333; margin-bottom: 1rem; font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem; }
        .info-list { list-style: none; padding: 0; margin: 0; }
        .info-list li { margin-bottom: 0.75rem; font-size: 1.05rem; }
        .info-label { font-weight: 600; color: #667eea; margin-right: 0.5rem; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem; font-weight: 500; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .btn { padding: 0.75rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; transition: transform 0.2s, box-shadow 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; }
        @media (max-width: 768px) { .container { padding: 1rem; } .card-body { padding: 1.5rem; } .form-actions { flex-direction: column; gap: 1rem; } }
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
            <strong>Visualizar</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($dados['razao_social']); ?></h2>
                <p>ID: <?php echo $dados['id']; ?> | Cadastrado em: <?php echo date('d/m/Y H:i', strtotime($dados['data_criacao'])); ?></p>
            </div>
            <div class="card-body">
                <div class="section">
                    <h3>🏢 Dados da Empresa</h3>
                    <ul class="info-list">
                        <li><span class="info-label">Razão Social:</span> <?php echo htmlspecialchars($dados['razao_social']); ?></li>
                        <li><span class="info-label">Nome Fantasia:</span> <?php echo htmlspecialchars($dados['nome_fantasia']); ?></li>
                        <li><span class="info-label">CNPJ:</span> <?php echo formatCNPJ($dados['cnpj']); ?></li>
                        <li><span class="info-label">Email da Empresa:</span> <?php echo htmlspecialchars($dados['email_empresa']); ?></li>
                        <li><span class="info-label">Telefone da Empresa:</span> <?php echo htmlspecialchars($dados['telefone_empresa']); ?></li>
                        <li><span class="info-label">Endereço:</span> <?php echo htmlspecialchars($dados['endereco']); ?></li>
                        <li><span class="info-label">Cidade/UF:</span> <?php echo htmlspecialchars($dados['cidade']); ?><?php echo $dados['estado'] ? '/' . $dados['estado'] : ''; ?></li>
                        <li><span class="info-label">CEP:</span> <?php echo formatCEP($dados['cep']); ?></li>
                        <li><span class="info-label">Status:</span> <span class="badge badge-<?php echo $dados['ativo'] ? 'success' : 'danger'; ?>"><?php echo $dados['ativo'] ? 'Ativo' : 'Inativo'; ?></span></li>
                    </ul>
                </div>
                <div class="section">
                    <h3>👤 Responsável pelo Contato</h3>
                    <ul class="info-list">
                        <li><span class="info-label">Nome:</span> <?php echo htmlspecialchars($dados['responsavel_nome']); ?></li>
                        <li><span class="info-label">Cargo/Função:</span> <?php echo htmlspecialchars($dados['responsavel_cargo']); ?></li>
                        <li><span class="info-label">Email:</span> <?php echo htmlspecialchars($dados['email_responsavel']); ?></li>
                        <li><span class="info-label">Telefone:</span> <?php echo htmlspecialchars($dados['telefone_responsavel']); ?></li>
                    </ul>
                </div>
                <div class="section">
                    <h3>📝 Observações</h3>
                    <div style="white-space: pre-line; color: #333; background: #f8f9fa; border-radius: 8px; padding: 1rem; min-height: 60px;">
                        <?php echo $dados['observacoes'] ? htmlspecialchars($dados['observacoes']) : '<span style="color:#aaa;">Nenhuma observação registrada.</span>'; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="lista.php" class="btn btn-secondary">← Voltar</a>
                    <a href="editar.php?id=<?php echo $dados['id']; ?>" class="btn btn-primary">✏️ Editar</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
