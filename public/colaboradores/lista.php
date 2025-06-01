<?php
// public/colaboradores/lista.php
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
$message = '';
$message_type = '';

// Processar ações
if ($_POST) {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'ativar':
                $result = $colaborador->alterarStatusColaborador($_POST['id'], $_SESSION['empresa_id'], 1);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
                
            case 'desativar':
                $result = $colaborador->alterarStatusColaborador($_POST['id'], $_SESSION['empresa_id'], 0);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
                
            case 'excluir':
                $result = $colaborador->excluirColaborador($_POST['id'], $_SESSION['empresa_id']);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Buscar colaboradores
$colaboradores = $colaborador->listarColaboradores($_SESSION['empresa_id']);
$stats = $colaborador->estatisticasColaboradores($_SESSION['empresa_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colaboradores - <?php echo SITE_NAME; ?></title>
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
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-primary { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        
        .actions {
            display: flex;
            gap: 0.5rem;
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
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .table {
                font-size: 0.875rem;
            }
            
            .actions {
                flex-direction: column;
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
            <a href="../dashboard.php">Dashboard</a> / <strong>Colaboradores</strong>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>Gerenciar Colaboradores</h2>
            <a href="adicionar.php" class="btn btn-primary">➕ Adicionar Colaborador</a>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total de Usuários</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['ativos']; ?></div>
                <div class="stat-label">Usuários Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['admins']; ?></div>
                <div class="stat-label">Administradores</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['colaboradores']; ?></div>
                <div class="stat-label">Colaboradores</div>
            </div>
        </div>
        
        <!-- Lista de Colaboradores -->
        <div class="card">
            <div class="card-header">
                <h3>Lista de Colaboradores</h3>
                <span><?php echo count($colaboradores); ?> usuário(s) encontrado(s)</span>
            </div>
            <div class="card-body">
                <?php if (!empty($colaboradores)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($colaboradores as $colab): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($colab['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($colab['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $colab['tipo'] == 'admin_empresa' ? 'primary' : 'warning'; ?>">
                                            <?php echo $colab['tipo'] == 'admin_empresa' ? 'Administrador' : 'Colaborador'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $colab['ativo'] ? 'success' : 'danger'; ?>">
                                            <?php echo $colab['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($colab['data_criacao'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="editar.php?id=<?php echo $colab['id']; ?>" class="btn btn-primary btn-sm">
                                                ✏️ Editar
                                            </a>
                                            
                                            <?php if ($colab['id'] != $_SESSION['user_id']): ?>
                                                <?php if ($colab['ativo']): ?>
                                                    <button onclick="confirmarAcao('desativar', <?php echo $colab['id']; ?>, '<?php echo htmlspecialchars($colab['nome']); ?>')" 
                                                            class="btn btn-warning btn-sm">
                                                        🚫 Desativar
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="confirmarAcao('ativar', <?php echo $colab['id']; ?>, '<?php echo htmlspecialchars($colab['nome']); ?>')" 
                                                            class="btn btn-success btn-sm">
                                                        ✅ Ativar
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button onclick="confirmarAcao('excluir', <?php echo $colab['id']; ?>, '<?php echo htmlspecialchars($colab['nome']); ?>')" 
                                                        class="btn btn-danger btn-sm">
                                                    🗑️ Excluir
                                                </button>
                                            <?php else: ?>
                                                <span class="badge badge-primary">Você</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <h3>📝 Nenhum colaborador encontrado</h3>
                        <p>Adicione colaboradores para que eles possam acessar o sistema e criar orçamentos.</p>
                        <a href="adicionar.php" class="btn btn-primary">Adicionar Primeiro Colaborador</a>
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
                    titulo.textContent = 'Ativar Colaborador';
                    mensagem.textContent = `Tem certeza que deseja ativar o colaborador "${nome}"?`;
                    btnConfirmar.textContent = 'Ativar';
                    btnConfirmar.className = 'btn btn-success';
                    break;
                    
                case 'desativar':
                    titulo.textContent = 'Desativar Colaborador';
                    mensagem.textContent = `Tem certeza que deseja desativar o colaborador "${nome}"? Ele não conseguirá mais acessar o sistema.`;
                    btnConfirmar.textContent = 'Desativar';
                    btnConfirmar.className = 'btn btn-warning';
                    break;
                    
                case 'excluir':
                    titulo.textContent = 'Excluir Colaborador';
                    mensagem.textContent = `Tem certeza que deseja excluir permanentemente o colaborador "${nome}"? Esta ação não pode ser desfeita.`;
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
    </script>
</body>
</html>