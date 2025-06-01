<?php
// public/produtos/lista.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Produto.php';

$auth = new Auth();

// Verificar se está logado e tem permissão
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$produto = new Produto();
$message = '';
$message_type = '';

// Processar ações
if ($_POST) {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'ativar':
                $result = $produto->alterarStatusProduto($_POST['id'], $_SESSION['empresa_id'], 1);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
                
            case 'desativar':
                $result = $produto->alterarStatusProduto($_POST['id'], $_SESSION['empresa_id'], 0);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
                
            case 'excluir':
                $result = $produto->excluirProduto($_POST['id'], $_SESSION['empresa_id']);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Filtros
$busca = $_GET['busca'] ?? '';
$categoria_id = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int)$_GET['categoria'] : null;
$ativo = isset($_GET['ativo']) && $_GET['ativo'] !== '' ? (int)$_GET['ativo'] : null;

// Buscar produtos e categorias
$produtos = $produto->listarProdutos($_SESSION['empresa_id'], $categoria_id, $ativo, $busca);
$categorias = $produto->listarCategorias($_SESSION['empresa_id'], 1);
$stats = $produto->estatisticasProdutos($_SESSION['empresa_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - <?php echo SITE_NAME; ?></title>
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
        
        .header-actions {
            display: flex;
            gap: 1rem;
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
        
        .btn-secondary {
            background: #6c757d;
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
            grid-template-columns: 2fr 1fr 1fr auto;
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
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image {
            color: #999;
            font-size: 3rem;
        }
        
        .category-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .product-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        
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
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            
            .header-actions {
                justify-content: stretch;
            }
            
            .filters-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-actions {
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
            <a href="../dashboard.php">Dashboard</a> / <strong>Produtos</strong>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>Gerenciar Produtos</h2>
            <div class="header-actions">
                <a href="categorias.php" class="btn btn-secondary">🏷️ Categorias</a>
                <a href="adicionar.php" class="btn btn-primary">➕ Adicionar Produto</a>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total de Produtos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['ativos']; ?></div>
                <div class="stat-label">Produtos Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_categorias']; ?></div>
                <div class="stat-label">Categorias</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">R$ <?php echo number_format($stats['preco_medio'] ?? 0, 2, ',', '.'); ?></div>
                <div class="stat-label">Preço Médio</div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters">
            <form method="GET">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="busca">Buscar Produto</label>
                        <input type="text" id="busca" name="busca" 
                               placeholder="Nome, descrição, código..."
                               value="<?php echo htmlspecialchars($busca); ?>">
                    </div>
                    <div class="form-group">
                        <label for="categoria">Categoria</label>
                        <select id="categoria" name="categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $categoria_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
        
        <!-- Grid de Produtos -->
        <?php if (!empty($produtos)): ?>
            <div class="products-grid">
                <?php foreach ($produtos as $prod): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($prod['foto'])): ?>
                                <img src="../../uploads/produtos/<?php echo $_SESSION['empresa_id']; ?>/<?php echo $prod['foto']; ?>" 
                                     alt="<?php echo htmlspecialchars($prod['nome']); ?>">
                            <?php else: ?>
                                <div class="no-image">📦</div>
                            <?php endif; ?>
                            
                            <?php if (!empty($prod['categoria_nome'])): ?>
                                <div class="category-badge" style="background-color: <?php echo $prod['categoria_cor']; ?>">
                                    <?php echo htmlspecialchars($prod['categoria_nome']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($prod['nome']); ?></div>
                            
                            <?php if (!empty($prod['descricao'])): ?>
                                <div class="product-description"><?php echo htmlspecialchars($prod['descricao']); ?></div>
                            <?php endif; ?>
                            
                            <div class="product-price">R$ <?php echo number_format($prod['preco'], 2, ',', '.'); ?></div>
                            
                            <div class="product-meta">
                                <span>Código: <?php echo $prod['codigo'] ?: 'N/A'; ?></span>
                                <span class="badge badge-<?php echo $prod['ativo'] ? 'success' : 'danger'; ?>">
                                    <?php echo $prod['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </div>
                            
                            <div class="product-actions">
                                <a href="editar.php?id=<?php echo $prod['id']; ?>" class="btn btn-primary btn-sm">
                                    ✏️ Editar
                                </a>
                                
                                <?php if ($prod['ativo']): ?>
                                    <button onclick="confirmarAcao('desativar', <?php echo $prod['id']; ?>, '<?php echo htmlspecialchars($prod['nome']); ?>')" 
                                            class="btn btn-warning btn-sm">
                                        🚫 Desativar
                                    </button>
                                <?php else: ?>
                                    <button onclick="confirmarAcao('ativar', <?php echo $prod['id']; ?>, '<?php echo htmlspecialchars($prod['nome']); ?>')" 
                                            class="btn btn-success btn-sm">
                                        ✅ Ativar
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="confirmarAcao('excluir', <?php echo $prod['id']; ?>, '<?php echo htmlspecialchars($prod['nome']); ?>')" 
                                        class="btn btn-danger btn-sm">
                                    🗑️ Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <h3>📦 Nenhum produto encontrado</h3>
                <p>Adicione produtos para poder incluí-los em seus orçamentos.</p>
                <a href="adicionar.php" class="btn btn-primary">Adicionar Primeiro Produto</a>
            </div>
        <?php endif; ?>
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
            
            let mensagem = '';
            
            switch(acao) {
                case 'ativar':
                    mensagem = `Tem certeza que deseja ativar o produto "${nome}"?`;
                    break;
                case 'desativar':
                    mensagem = `Tem certeza que deseja desativar o produto "${nome}"?`;
                    break;
                case 'excluir':
                    mensagem = `ATENÇÃO: Tem certeza que deseja excluir PERMANENTEMENTE o produto "${nome}"?\n\nEsta ação não pode ser desfeita!`;
                    break;
            }
            
            if (confirm(mensagem)) {
                document.getElementById('inputAcao').value = acaoAtual;
                document.getElementById('inputId').value = idAtual;
                document.getElementById('formAcao').submit();
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