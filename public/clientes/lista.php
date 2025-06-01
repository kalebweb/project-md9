<?php
// public/clientes/lista.php
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
$message = '';
$message_type = '';

// Processar ações
if ($_POST) {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'ativar':
                $result = $cliente->alterarStatusCliente($_POST['id'], $_SESSION['empresa_id'], 1);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
                
            case 'desativar':
                $result = $cliente->alterarStatusCliente($_POST['id'], $_SESSION['empresa_id'], 0);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
                
            case 'excluir':
                $result = $cliente->excluirCliente($_POST['id'], $_SESSION['empresa_id']);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Filtros
$busca = $_GET['busca'] ?? '';
$ativo = isset($_GET['ativo']) && $_GET['ativo'] !== '' ? (int)$_GET['ativo'] : null;

// Buscar clientes
$clientes = $cliente->listarClientes($_SESSION['empresa_id'], $ativo, $busca);
$stats = $cliente->estatisticasClientes($_SESSION['empresa_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - <?php echo SITE_NAME; ?></title>
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
            max-width: 1200px;
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            color: #333;
            margin: 0;
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
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: 3fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        select {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            color: #333;
            margin: 0;
        }
        
        .card-body {
            padding: 0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .cliente-info {
            line-height: 1.4;
        }
        
        .cliente-nome {
            font-weight: 600;
            color: #333;
        }
        
        .cliente-fantasia {
            font-size: 0.9rem;
            color: #666;
        }
        
        .contato-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.9rem;
        }
        
        .contato-nome {
            font-weight: 500;
            color: #333;
        }
        
        .contato-cargo {
            color: #666;
            font-size: 0.85rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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
        
        .no-data {
            text-align: center;
            color: #666;
            padding: 3rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            text-align: center;
        }
        
        .modal h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .filters-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .table {
                font-size: 0.875rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .contato-info {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo SITE_NAME; ?></h1>
            <div class="user-info">
                <a href="../dashboard.php">← Voltar ao Dashboard</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> / <strong>Clientes</strong>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>Gerenciar Clientes</h2>
            <a href="adicionar.php" class="btn btn-primary">➕ Adicionar Cliente</a>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total de Clientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['ativos']; ?></div>
                <div class="stat-label">Clientes Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['inativos']; ?></div>
                <div class="stat-label">Clientes Inativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['ultimos_30_dias']; ?></div>
                <div class="stat-label">Novos (30 dias)</div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters">
            <form method="GET">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="busca">Buscar Cliente</label>
                        <input type="text" id="busca" name="busca" 
                               placeholder="Razão social, nome fantasia, CNPJ, responsável..."
                               value="<?php echo htmlspecialchars($busca); ?>">
                    </div>
                    <div class="form-group">
                        <label for="ativo">Status</label>
                        <select id="ativo" name="ativo">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $ativo === 1 ? 'selected' : ''; ?>>Ativos</option>
                            <option value="0" <?php echo $ativo === 0 ? 'selected' : ''; ?>>Inativos</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                </div>
            </form>
        </div>
        
        <!-- Lista de Clientes -->
        <div class="card">
            <div class="card-header">
                <h3>Lista de Clientes</h3>
                <span><?php echo count($clientes); ?> cliente(s) encontrado(s)</span>
            </div>
            <div class="card-body">
                <?php if (!empty($clientes)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>CNPJ</th>
                                    <th>Responsável</th>
                                    <th>Contato</th>
                                    <th>Cidade/UF</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $cli): ?>
                                    <tr>
                                        <td>
                                            <div class="cliente-info">
                                                <div class="cliente-nome"><?php echo htmlspecialchars($cli['razao_social']); ?></div>
                                                <?php if (!empty($cli['nome_fantasia'])): ?>
                                                    <div class="cliente-fantasia"><?php echo htmlspecialchars($cli['nome_fantasia']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?php echo formatCNPJ($cli['cnpj']); ?></span>
                                        </td>
                                        <td>
                                            <div class="contato-info">
                                                <span class="contato-nome"><?php echo htmlspecialchars($cli['responsavel_nome']); ?></span>
                                                <?php if (!empty($cli['responsavel_cargo'])): ?>
                                                    <span class="contato-cargo"><?php echo htmlspecialchars($cli['responsavel_cargo']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="contato-info">
                                                <span>📧 <?php echo htmlspecialchars($cli['email_responsavel']); ?></span>
                                                <?php if (!empty($cli['telefone_responsavel'])): ?>
                                                    <span>📱 <?php echo formatPhone($cli['telefone_responsavel']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($cli['cidade']) || !empty($cli['estado'])): ?>
                                                <?php echo htmlspecialchars($cli['cidade']); ?><?php echo !empty($cli['estado']) ? '/' . $cli['estado'] : ''; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $cli['ativo'] ? 'success' : 'danger'; ?>">
                                                <?php echo $cli['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="editar.php?id=<?php echo $cli['id']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                                    ✏️ Editar
                                                </a>
                                                
                                                <a href="visualizar.php?id=<?php echo $cli['id']; ?>" class="btn btn-info btn-sm" title="Visualizar">
                                                    👁️ Ver
                                                </a>
                                                
                                                <?php if ($cli['ativo']): ?>
                                                    <button onclick="confirmarAcao('desativar', <?php echo $cli['id']; ?>, '<?php echo htmlspecialchars($cli['razao_social']); ?>')" 
                                                            class="btn btn-warning btn-sm" title="Desativar">
                                                        🚫
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="confirmarAcao('ativar', <?php echo $cli['id']; ?>, '<?php echo htmlspecialchars($cli['razao_social']); ?>')" 
                                                            class="btn btn-success btn-sm" title="Ativar">
                                                        ✅
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button onclick="confirmarAcao('excluir', <?php echo $cli['id']; ?>, '<?php echo htmlspecialchars($cli['razao_social']); ?>')" 
                                                        class="btn btn-danger btn-sm" title="Excluir">
                                                    🗑️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <h3>📋 Nenhum cliente encontrado</h3>
                        <p>Adicione clientes para poder criar orçamentos para eles.</p>
                        <a href="adicionar.php" class="btn btn-primary">Adicionar Primeiro Cliente</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal de Confirmação -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-content">
            <h3 id="modalTitulo"></h3>
            <p id="modalMensagem"></p>
            <div class="modal-actions">
                <button onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
                <button onclick="executarAcao()" class="btn btn-danger" id="btnConfirmar">Confirmar</button>
            </div>
        </div>
    </div>
    
    <!-- Form oculto para ações -->
    <form id="formAcao" method="POST" style="display: none;">
        <input type="hidden" name="acao" id="inputAcao">
        <input type="hidden" name="id" id="inputId">
    </form>
    
    <script>
        let acaoAtual = '';
        let idAtual = '';
        
        function confirmarAcao(acao, id, nome) {
            acaoAtual = acao;
            idAtual = id;
            
            const modal = document.getElementById('modalConfirmacao');
            const titulo = document.getElementById('modalTitulo');
            const mensagem = document.getElementById('modalMensagem');
            const btnConfirmar = document.getElementById('btnConfirmar');
            
            switch(acao) {
                case 'ativar':
                    titulo.textContent = 'Ativar Cliente';
                    mensagem.textContent = `Tem certeza que deseja ativar o cliente "${nome}"?`;
                    btnConfirmar.textContent = 'Ativar';
                    btnConfirmar.className = 'btn btn-success';
                    break;
                    
                case 'desativar':
                    titulo.textContent = 'Desativar Cliente';
                    mensagem.textContent = `Tem certeza que deseja desativar o cliente "${nome}"? Ele não aparecerá mais na lista de clientes ativos.`;
                    btnConfirmar.textContent = 'Desativar';
                    btnConfirmar.className = 'btn btn-warning';
                    break;
                    
                case 'excluir':
                    titulo.textContent = 'Excluir Cliente';
                    mensagem.textContent = `ATENÇÃO: Tem certeza que deseja excluir permanentemente o cliente "${nome}"? Esta ação não pode ser desfeita e todos os orçamentos relacionados serão afetados.`;
                    btnConfirmar.textContent = 'Excluir';
                    btnConfirmar.className = 'btn btn-danger';
                    break;
            }
            
            modal.style.display = 'block';
        }
        
        function executarAcao() {
            document.getElementById('inputAcao').value = acaoAtual;
            document.getElementById('inputId').value = idAtual;
            document.getElementById('formAcao').submit();
        }
        
        function fecharModal() {
            document.getElementById('modalConfirmacao').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalConfirmacao');
            if (event.target == modal) {
                fecharModal();
            }
        }
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0.7';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 5000);
            });
        }, 3000);
        
        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K para focar na busca
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('busca').focus();
            }
        });
    </script>
</body>
</html>