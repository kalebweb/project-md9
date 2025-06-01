<?php
// public/orcamentos/adicionar.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Orcamento.php';
require_once __DIR__ . '/../../classes/Produto.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$orcamento = new Orcamento();
$produto = new Produto();
$error = '';
$success = '';

// Buscar clientes e produtos
$clientes = $orcamento->listarClientes($_SESSION['empresa_id']);
$produtos = $produto->listarProdutosParaOrcamento($_SESSION['empresa_id']);

// Inicializar variáveis do formulário
$dados = [
    'cliente_id' => '',
    'titulo' => '',
    'descricao' => '',
    'itens' => []
];

if ($_POST) {
    $dados['cliente_id'] = $_POST['cliente_id'] ?? '';
    $dados['titulo'] = trim($_POST['titulo'] ?? '');
    $dados['descricao'] = trim($_POST['descricao'] ?? '');
    $dados['itens'] = $_POST['itens'] ?? [];

    if (empty($dados['cliente_id']) || empty($dados['titulo']) || empty($dados['itens'])) {
        $error = 'Preencha todos os campos obrigatórios e adicione pelo menos um produto.';
    } else {
        $result = $orcamento->adicionarOrcamento($dados, $_SESSION['empresa_id'], $_SESSION['user_id']);
        if ($result['success']) {
            $success = $result['message'];
            header('Location: visualizar.php?id=' . $result['id']);
            exit;
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
    <title>Novo Orçamento - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .itens-table th, .itens-table td { padding: 0.5rem; text-align: left; }
        .itens-table input[type='number'], .itens-table input[type='text'] { width: 80px; }
        .itens-table select { width: 180px; }
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
            <a href="lista.php">Orçamentos</a> /
            <strong>Novo Orçamento</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Novo Orçamento</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="section">
                        <h3>Cliente e Cabeçalho</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cliente_id">Cliente <span class="required">*</span></label>
                                <select id="cliente_id" name="cliente_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($clientes as $cli): ?>
                                        <option value="<?php echo $cli['id']; ?>" <?php echo ($dados['cliente_id'] == $cli['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cli['razao_social']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="titulo">Título <span class="required">*</span></label>
                                <input type="text" id="titulo" name="titulo" required value="<?php echo htmlspecialchars($dados['titulo']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($dados['descricao']); ?></textarea>
                        </div>
                    </div>
                    <div class="section">
                        <h3>Produtos do Orçamento</h3>
                        <table class="itens-table" style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Descrição</th>
                                    <th>Quantidade</th>
                                    <th>Valor Unitário</th>
                                    <th>Valor Promocional</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itensBody">
                                <!-- Linhas de itens serão adicionadas via JS -->
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-secondary" onclick="adicionarItem()">+ Adicionar Produto</button>
                    </div>
                    <div class="form-actions">
                        <a href="lista.php" class="btn btn-secondary">← Cancelar</a>
                        <button type="submit" class="btn btn-primary">💾 Salvar Orçamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const produtos = <?php echo json_encode($produtos); ?>;
        let itemIndex = 0;
        function adicionarItem(item = null) {
            const tbody = document.getElementById('itensBody');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <select name="itens[${itemIndex}][produto_id]" required onchange="atualizarValorUnitario(this, ${itemIndex})">
                        <option value="">Selecione...</option>
                        ${produtos.map(p => `<option value="${p.id}">${p.nome}</option>`).join('')}
                    </select>
                </td>
                <td><input type="text" name="itens[${itemIndex}][descricao]" value="" placeholder="Descrição"></td>
                <td><input type="number" name="itens[${itemIndex}][quantidade]" value="1" min="1" required onchange="atualizarTotal(${itemIndex})"></td>
                <td><input type="text" name="itens[${itemIndex}][valor_unitario]" value="" required oninput="atualizarTotal(${itemIndex})"></td>
                <td><input type="text" name="itens[${itemIndex}][valor_promocional]" value="" oninput="atualizarTotal(${itemIndex})"></td>
                <td><input type="text" name="itens[${itemIndex}][valor_total]" value="" readonly></td>
                <td><button type="button" onclick="removerItem(this)">🗑️</button></td>
            `;
            tbody.appendChild(tr);
            itemIndex++;
        }
        function removerItem(btn) {
            btn.closest('tr').remove();
        }
        function atualizarValorUnitario(select, idx) {
            const produto = produtos.find(p => p.id == select.value);
            if (produto) {
                select.closest('tr').querySelector(`[name='itens[${idx}][valor_unitario]']`).value = produto.preco;
                atualizarTotal(idx);
            }
        }
        function atualizarTotal(idx) {
            const row = document.querySelector(`[name='itens[${idx}][produto_id]']`).closest('tr');
            const qtd = parseFloat(row.querySelector(`[name='itens[${idx}][quantidade]']`).value) || 0;
            let valor = parseFloat(row.querySelector(`[name='itens[${idx}][valor_unitario]']`).value.replace(',', '.')) || 0;
            let promo = parseFloat(row.querySelector(`[name='itens[${idx}][valor_promocional]']`).value.replace(',', '.'));
            if (!isNaN(promo) && promo > 0) valor = promo;
            row.querySelector(`[name='itens[${idx}][valor_total]']`).value = (valor * qtd).toFixed(2);
        }
        // Adiciona uma linha inicial
        adicionarItem();
    </script>
</body>
</html>
