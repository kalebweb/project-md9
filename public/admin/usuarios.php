<?php
// public/admin/usuarios.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('super_admin')) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$error = '';
$success = '';

// Buscar empresas para o select
$stmt = $conn->query('SELECT id, razao_social FROM empresas ORDER BY razao_social ASC');
$empresas = $stmt->fetchAll();

// Adicionar usuário vinculado a empresa
if ($_POST && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $dados = [
        'empresa_id' => (int)$_POST['empresa_id'],
        'nome' => trim($_POST['nome']),
        'email' => trim($_POST['email']),
        'senha' => $_POST['senha'],
        'tipo' => $_POST['tipo']
    ];
    if (empty($dados['empresa_id']) || empty($dados['nome']) || empty($dados['email']) || empty($dados['senha']) || empty($dados['tipo'])) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        // Verifica se já existe email
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->bindParam(':email', $dados['email']);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $error = 'E-mail já cadastrado.';
        } else {
            $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            $query = "INSERT INTO usuarios (empresa_id, nome, email, senha, tipo, ativo, data_criacao) VALUES (:empresa_id, :nome, :email, :senha, :tipo, 1, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':empresa_id', $dados['empresa_id']);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':tipo', $dados['tipo']);
            if ($stmt->execute()) {
                $success = 'Usuário cadastrado e vinculado à empresa com sucesso!';
            } else {
                $error = 'Erro ao cadastrar usuário.';
            }
        }
    }
}

// Listar usuários e empresas
$stmt = $conn->query('SELECT u.id, u.nome, u.email, u.tipo, u.ativo, u.data_criacao, e.razao_social FROM usuarios u LEFT JOIN empresas e ON u.empresa_id = e.id ORDER BY u.data_criacao DESC');
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo SITE_NAME; ?> - Admin</h1>
            <div class="user-info">
                <a href="../dashboard.php">← Dashboard</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="breadcrumb">
            <a href="../dashboard.php">Dashboard</a> /
            <strong>Usuários</strong>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Cadastrar Novo Usuário</h2>
                <p>Vincule um usuário a uma empresa</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="acao" value="adicionar">
                    <div class="section">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="empresa_id">Empresa <span class="required">*</span></label>
                                <select id="empresa_id" name="empresa_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['razao_social']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="nome">Nome <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">E-mail <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="senha">Senha <span class="required">*</span></label>
                                <input type="password" id="senha" name="senha" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo">Tipo <span class="required">*</span></label>
                                <select id="tipo" name="tipo" required>
                                    <option value="admin_empresa">Administrador</option>
                                    <option value="colaborador">Colaborador</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Cadastrar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card" style="margin-top:2rem;">
            <div class="card-header">
                <h2>Usuários Cadastrados</h2>
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>Empresa</th>
                            <th>Status</th>
                            <th>Data Criação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['nome']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo $u['tipo'] == 'admin_empresa' ? 'Administrador' : 'Colaborador'; ?></td>
                                <td><?php echo htmlspecialchars($u['razao_social']); ?></td>
                                <td><?php echo $u['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($u['data_criacao'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
