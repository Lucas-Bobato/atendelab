<?php
$tituloPagina = 'Relatório de atendimentos';
require __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Relatório de atendimentos</h1>
        <p class="text-secondary mb-0">Consulta por período e status com opção de impressão.</p>
    </div>
    <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Imprimir</button>
</div>

<form class="card border-0 shadow-sm mb-4 d-print-none" method="get">
    <div class="card-body">
        <input type="hidden" name="controller" value="relatorios">
        <input type="hidden" name="action" value="atendimentos">

        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="data_inicio">Data inicial</label>
                <input class="form-control" type="date" id="data_inicio" name="data_inicio"
                    value="<?= htmlspecialchars((string) ($filtros['data_inicio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="data_fim">Data final</label>
                <input class="form-control" type="date" id="data_fim" name="data_fim"
                    value="<?= htmlspecialchars((string) ($filtros['data_fim'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <?php foreach (['Aguardando', 'Em Atendimento', 'Atendido', 'Cancelado'] as $statusOpcao): ?>
                        <option value="<?= htmlspecialchars($statusOpcao, ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($filtros['status'] ?? '') === $statusOpcao ? 'selected' : '' ?>>
                            <?= htmlspecialchars($statusOpcao, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-success" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary"
                    href="?controller=relatorios&action=atendimentos">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <strong>Total de registros:</strong> <?= count($atendimentos) ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Pessoa</th>
                    <th>Tipo</th>
                    <th>Responsável</th>
                    <th>Status</th>
                    <th>Descrição</th>
                    <th>Observação final</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$atendimentos): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">Nenhum atendimento encontrado.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($atendimentos as $atendimento): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $atendimento['id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $atendimento['data_atendimento'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(substr((string) $atendimento['horario_atendimento'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $atendimento['pessoa_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $atendimento['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $atendimento['usuario_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $atendimento['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $atendimento['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($atendimento['observacao_final'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    @media print {
        nav,
        .d-print-none,
        button {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        main {
            width: 100%;
            max-width: none;
        }
    }
</style>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
