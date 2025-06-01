<?php
// public/orcamentos/gerar_pdf.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Orcamento.php';

// Requer a biblioteca dompdf instalada via composer
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('user')) {
    header('Location: ../login.php');
    exit;
}

$orcamento = new Orcamento();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Orçamento não encontrado.');
}
$id = (int)$_GET['id'];
$dados = $orcamento->buscarOrcamento($id, $_SESSION['empresa_id']);
if (!$dados['success']) {
    die('Orçamento não encontrado.');
}
$orc = $dados['orcamento'];
$itens = $orc['itens'];

function format_money($v) { return 'R$ ' . number_format($v, 2, ',', '.'); }

$html = '<h2>Orçamento #' . $orc['id'] . ' - ' . htmlspecialchars($orc['titulo']) . '</h2>';
$html .= '<p><strong>Cliente:</strong> ' . htmlspecialchars($orc['cliente_nome']) . '</p>';
$html .= '<table border="1" width="100%" cellspacing="0" cellpadding="4">
<thead><tr><th>Produto</th><th>Descrição</th><th>Qtd</th><th>Valor Unitário</th><th>Valor Promocional</th><th>Total</th></tr></thead><tbody>';
foreach ($itens as $item) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($item['produto_nome']) . '</td>';
    $html .= '<td>' . htmlspecialchars($item['descricao']) . '</td>';
    $html .= '<td>' . $item['quantidade'] . '</td>';
    $html .= '<td>' . format_money($item['valor_unitario']) . '</td>';
    $html .= '<td>' . ($item['valor_promocional'] > 0 ? format_money($item['valor_promocional']) : '-') . '</td>';
    $html .= '<td>' . format_money($item['valor_total']) . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';
$html .= '<h3>Observações</h3><p>' . nl2br(htmlspecialchars($orc['descricao'])) . '</p>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('orcamento_' . $orc['id'] . '.pdf', ['Attachment' => false]);
exit;
