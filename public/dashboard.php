<?php
// public/dashboard.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// Verificar se está logado
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Conectar ao banco para buscar dados
$database = new Database();
$conn = $database->getConnection();

// Buscar dados específicos baseado no tipo de usuário
$stats = [];

if ($_SESSION['user_type'] == 'super_admin') {
    // Stats para super admin
    $query = "SELECT 
                (SELECT COUNT(*) FROM empresas WHERE ativo = 1) as total_empresas,
                (SELECT COUNT(*) FROM empresas WHERE plano = 'premium' AND ativo = 1) as empresas_premium,
                (SELECT COUNT(*) FROM usuarios WHERE tipo IN ('admin_empresa', 'colaborador') AND ativo = 1) as total_usuarios,
                (SELECT COUNT(*) FROM orcamentos WHERE MONTH(data_criacao) = MONTH(CURRENT_DATE)) as orcamentos_mes,
                (SELECT SUM(valor_total) FROM orcamentos WHERE status = 'aprovado' AND MONTH(data_criacao) = MONTH(CURRENT_DATE)) as faturamento_mes";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats = $stmt->fetch();
    
    // Buscar empresas recentes
    $query = "SELECT razao_social, cnpj, data_criacao, plano FROM empresas ORDER BY data_criacao DESC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $empresas_recentes = $stmt->fetchAll();
    
} else {
    // Stats para empresa
    $query = "SELECT 
                e.razao_social,
                e.plano,
                e.limite_orcamentos,
                e.orcamentos_utilizados,
                (SELECT COUNT(*) FROM usuarios WHERE empresa_id = e.id AND ativo = 1) as total_colaboradores,
                (SELECT COUNT(*) FROM clientes WHERE empresa_id = e.id AND ativo = 1) as total_clientes,
                (SELECT COUNT(*) FROM produtos WHERE empresa_id = e.id AND ativo = 1) as total_produtos,
                (SELECT COUNT(*) FROM orcamentos WHERE empresa_id = e.id AND MONTH(data_criacao) = MONTH(CURRENT_DATE)) as orcamentos_mes,
                (SELECT COUNT(*) FROM orcamentos WHERE empresa_id = e.id AND status = 'aprovado') as orcamentos_aprovados,
                (SELECT SUM(valor_total) FROM orcamentos WHERE empresa_id = e.id AND status = 'aprovado' AND MONTH(data_criacao) = MONTH(CURRENT_DATE)) as faturamento_mes
              FROM empresas e WHERE e.id = :empresa_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':empresa_id', $_SESSION['empresa_id']);
    $stmt->execute();
    $stats = $stmt->fetch();
    
    // Buscar orçamentos recentes da empresa (como ainda não temos orçamentos, vamos simular)
    $orcamentos_recentes = []; // Array vazio até implementarmos orçamentos
}

// Logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info span {
            opacity: 0.9;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .welcome {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .welcome h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .welcome p {
            color: #666;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h3 {
            color: #333;
            margin: 0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-aprovado { background: #d4edda; color: #155724; }
        .status-enviado { background: #d1ecf1; color: #0c5460; }
        .status-rascunho { background: #f8d7da; color: #721c24; }
        .status-rejeitado { background: #f5c6cb; color: #721c24; }
        
        .plano-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .plano-gratuito { background: #e2e3e5; color: #383d41; }
        .plano-premium { background: #d4edda; color: #155724; }
        
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 10px;
            margin: 0.5rem 0;
            overflow: hidden;
        }
        
        .progress-fill {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            transition: width 0.3s;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo SITE_NAME; ?></h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <?php if ($_SESSION['user_type'] != 'super_admin'): ?>
                    <span>|</span>
                    <span><?php echo htmlspecialchars($_SESSION['empresa_nome']); ?></span>
                <?php endif; ?>
                <a href="?logout=1" class="btn-logout">Sair</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <p>
                <?php if ($_SESSION['user_type'] == 'super_admin'): ?>
                    Você está logado como Super Administrador. Gerencie todas as empresas e usuários do sistema.
                <?php else: ?>
                    Você está logado como <?php echo $_SESSION['user_type'] == 'admin_empresa' ? 'Administrador' : 'Colaborador'; ?> 
                    da empresa <?php echo htmlspecialchars($_SESSION['empresa_nome']); ?>.
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <?php if ($_SESSION['user_type'] == 'super_admin'): ?>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_empresas']); ?></div>
                    <div class="stat-label">Empresas Ativas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['empresas_premium']); ?></div>
                    <div class="stat-label">Empresas Premium</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_usuarios']); ?></div>
                    <div class="stat-label">Total de Usuários</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['orcamentos_mes']); ?></div>
                    <div class="stat-label">Orçamentos este Mês</div>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['orcamentos_utilizados']; ?>/<?php echo $stats['limite_orcamentos']; ?></div>
                    <div class="stat-label">Orçamentos Utilizados</div>
                    <?php 
                    $percentage = ($stats['orcamentos_utilizados'] / $stats['limite_orcamentos']) * 100;
                    ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_clientes']); ?></div>
                    <div class="stat-label">Clientes Cadastrados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_produtos']); ?></div>
                    <div class="stat-label">Produtos Cadastrados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_colaboradores']); ?></div>
                    <div class="stat-label">Colaboradores</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['orcamentos_mes']); ?></div>
                    <div class="stat-label">Orçamentos este Mês</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">R$ <?php echo number_format($stats['faturamento_mes'] ?? 0, 2, ',', '.'); ?></div>
                    <div class="stat-label">Faturamento do Mês</div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Lista Principal -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <?php if ($_SESSION['user_type'] == 'super_admin'): ?>
                            Empresas Recentes
                        <?php else: ?>
                            Orçamentos Recentes
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($_SESSION['user_type'] == 'super_admin'): ?>
                        <?php if (!empty($empresas_recentes)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>CNPJ</th>
                                        <th>Plano</th>
                                        <th>Data Cadastro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($empresas_recentes as $empresa): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($empresa['razao_social']); ?></td>
                                            <td><?php echo htmlspecialchars($empresa['cnpj']); ?></td>
                                            <td>
                                                <span class="plano-badge plano-<?php echo $empresa['plano']; ?>">
                                                    <?php echo ucfirst($empresa['plano']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($empresa['data_criacao'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <p>Nenhuma empresa cadastrada ainda.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!empty($orcamentos_recentes)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Colaborador</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orcamentos_recentes as $orcamento): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($orcamento['numero_orcamento']); ?></td>
                                            <td><?php echo htmlspecialchars($orcamento['cliente_nome'] ?: 'Cliente não informado'); ?></td>
                                            <td>R$ <?php echo number_format($orcamento['valor_total'], 2, ',', '.'); ?></td>
                                            <td>
                                                <span class="status status-<?php echo $orcamento['status']; ?>">
                                                    <?php echo ucfirst($orcamento['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($orcamento['colaborador_nome']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <p>Nenhum orçamento criado ainda.</p>
                                <p style="color: #999; font-size: 0.9rem; margin-top: 0.5rem;">Cadastre clientes e produtos para começar a criar orçamentos.</p>
                                <div style="margin-top: 1rem;">
                                    <a href="clientes/lista.php" class="btn btn-primary" style="margin-right: 0.5rem;">👥 Ver Clientes</a>
                                    <a href="produtos/lista.php" class="btn btn-primary">📦 Ver Produtos</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Menu de Ações -->
            <div class="card">
                <div class="card-header">
                    <h3>Ações Rápidas</h3>
                </div>
                <div class="card-body">
                    <?php if ($_SESSION['user_type'] == 'super_admin'): ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <a href="admin/empresas.php" class="btn btn-primary">
                                📊 Gerenciar Empresas
                            </a>
                            <a href="admin/usuarios.php" class="btn btn-primary">
                                👥 Gerenciar Usuários
                            </a>
                            <a href="admin/pagamentos.php" class="btn btn-warning">
                                💳 Controle de Pagamentos
                            </a>
                            <a href="admin/relatorios.php" class="btn btn-success">
                                📈 Relatórios
                            </a>
                            <a href="admin/logs.php" class="btn btn-primary">
                                📋 Logs do Sistema
                            </a>
                        </div>
                    <?php elseif ($_SESSION['user_type'] == 'admin_empresa'): ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <a href="orcamentos/novo.php" class="btn btn-success">
                                ➕ Novo Orçamento
                            </a>
                            <a href="orcamentos/lista.php" class="btn btn-primary">
                                📋 Meus Orçamentos
                            </a>
                            <a href="clientes/lista.php" class="btn btn-primary">
                                👥 Gerenciar Clientes
                            </a>
                            <a href="produtos/lista.php" class="btn btn-primary">
                                📦 Gerenciar Produtos
                            </a>
                            <a href="colaboradores/lista.php" class="btn btn-primary">
                                👨‍💼 Gerenciar Colaboradores
                            </a>
                            <a href="empresa/perfil.php" class="btn btn-primary">
                                🏢 Dados da Empresa
                            </a>
                            <?php if ($stats['plano'] == 'gratuito'): ?>
                                <a href="empresa/upgrade.php" class="btn btn-warning">
                                    ⭐ Upgrade para Premium
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Alertas -->
                        <?php if ($stats['orcamentos_utilizados'] >= $stats['limite_orcamentos'] * 0.8): ?>
                            <div style="margin-top: 1.5rem; padding: 1rem; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; color: #856404;">
                                <strong>⚠️ Atenção!</strong><br>
                                Você está próximo do limite de orçamentos.
                                <?php if ($stats['plano'] == 'gratuito'): ?>
                                    <br><a href="empresa/upgrade.php" style="color: #667eea;">Faça upgrade para Premium</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: // colaborador ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <a href="orcamentos/novo.php" class="btn btn-success">
                                ➕ Novo Orçamento
                            </a>
                            <a href="orcamentos/lista.php" class="btn btn-primary">
                                📋 Meus Orçamentos
                            </a>
                            <a href="clientes/lista.php" class="btn btn-primary">
                                👥 Ver Clientes
                            </a>
                            <a href="produtos/lista.php" class="btn btn-primary">
                                📦 Ver Produtos
                            </a>
                            <a href="usuario/perfil.php" class="btn btn-primary">
                                👤 Meu Perfil
                            </a>
                        </div>
                        
                        <!-- Alertas para colaborador -->
                        <?php if ($stats['orcamentos_utilizados'] >= $stats['limite_orcamentos']): ?>
                            <div style="margin-top: 1.5rem; padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; color: #721c24;">
                                <strong>🚫 Limite Atingido!</strong><br>
                                Sua empresa atingiu o limite de orçamentos. Entre em contato com o administrador.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Informações do Plano (apenas para empresa) -->
        <?php if ($_SESSION['user_type'] != 'super_admin'): ?>
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Informações do Plano</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <div>
                            <strong>Plano Atual:</strong><br>
                            <span class="plano-badge plano-<?php echo $stats['plano']; ?>">
                                <?php echo ucfirst($stats['plano']); ?>
                            </span>
                        </div>
                        <div>
                            <strong>Limite de Orçamentos:</strong><br>
                            <?php if ($stats['plano'] == 'premium'): ?>
                                Ilimitado
                            <?php else: ?>
                                <?php echo $stats['limite_orcamentos']; ?> por mês
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong>Orçamentos Utilizados:</strong><br>
                            <?php echo $stats['orcamentos_utilizados']; ?>
                        </div>
                        <div>
                            <strong>Orçamentos Aprovados:</strong><br>
                            <?php echo $stats['orcamentos_aprovados']; ?>
                        </div>
                    </div>
                    
                    <?php if ($stats['plano'] == 'gratuito'): ?>
                        <div style="margin-top: 1.5rem; padding: 1rem; background: #e7f3ff; border: 1px solid #b8daff; border-radius: 8px;">
                            <h4 style="color: #004085; margin-bottom: 0.5rem;">🌟 Upgrade para Premium</h4>
                            <p style="color: #004085; margin-bottom: 1rem;">
                                Por apenas R$ 199,90/mês tenha orçamentos ilimitados, relatórios avançados e suporte prioritário.
                            </p>
                            <a href="empresa/upgrade.php" class="btn btn-warning">Fazer Upgrade Agora</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>