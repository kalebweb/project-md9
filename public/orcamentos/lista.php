<?php
// public/orcamentos/lista.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Orcamento.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$orcamento = new Orcamento();
$orcamentos = $orcamento->listarOrcamentos($_SESSION['empresa_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamentos - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/styles.css">
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
            <strong>Orçamentos</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Orçamentos</h2>
                <a href="adicionar.php" class="btn btn-primary">+ Novo Orçamento</a>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orcamentos as $orc): ?>
                            <tr>
                                <td><?php echo $orc['id']; ?></td>
                                <td><?php echo htmlspecialchars($orc['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($orc['cliente_nome']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($orc['data_criacao'])); ?></td>
                                <td><?php echo $orc['status']; ?></td>
                                <td>
                                    <a href="visualizar.php?id=<?php echo $orc['id']; ?>" class="btn btn-sm btn-primary">👁️ Visualizar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
