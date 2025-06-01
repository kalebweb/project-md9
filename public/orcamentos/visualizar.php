<?php
// public/orcamentos/visualizar.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Orcamento.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$orcamento = new Orcamento();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: lista.php');
    exit;
}
$id = (int)$_GET['id'];
$dados = $orcamento->buscarOrcamento($id, $_SESSION['empresa_id']);
if (!$dados['success']) {
    header('Location: lista.php');
    exit;
}
$orc = $dados['orcamento'];
$itens = $orc['itens'];

function format_money($v) { return 'R$ ' . number_format($v, 2, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #<?php echo $orc['id']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo SITE_NAME; ?></h1>
            <div class="user-info">
                <a href="lista.php">← Orçamentos</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> /
            <a href="lista.php">Orçamentos</a> /
            <strong>Visualizar</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Orçamento #<?php echo $orc['id']; ?> - <?php echo htmlspecialchars($orc['titulo']); ?></h2>
                <a href="gerar_pdf.php?id=<?php echo $orc['id']; ?>" class="btn btn-secondary" target="_blank">📄 Gerar PDF</a>
            </div>
            <div class="card-body">
                <h3>Cliente</h3>
                <p><strong><?php echo htmlspecialchars($orc['cliente_nome']); ?></strong></p>
                <h3>Produtos</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Descrição</th>
                            <th>Quantidade</th>
                            <th>Valor Unitário</th>
                            <th>Valor Promocional</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['produto_nome']); ?></td>
                                <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                <td><?php echo $item['quantidade']; ?></td>
                                <td><?php echo format_money($item['valor_unitario']); ?></td>
                                <td><?php echo $item['valor_promocional'] > 0 ? format_money($item['valor_promocional']) : '-'; ?></td>
                                <td><?php echo format_money($item['valor_total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <h3>Observações</h3>
                <p><?php echo nl2br(htmlspecialchars($orc['descricao'])); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
