<?php

// Importa a conexão com o banco de dados.
require_once __DIR__ . '/../../config/database.php';

class RelatoriosController
{
  // Armazena a conexão PDO.
  private PDO $pdo;

  public function __construct()
  {
    // Recupera a conexão criada em database.php.
    global $pdo;

    // Disponibiliza a conexão para os métodos da classe.
    $this->pdo = $pdo;
  }

  public function atendimentos(): void
  {
    // Coleta os filtros opcionais enviados via GET.
    $dataInicio = trim($_GET['data_inicio'] ?? '');
    $dataFim = trim($_GET['data_fim'] ?? '');
    $status = trim($_GET['status'] ?? '');

    // Constrói a cláusula WHERE dinamicamente conforme os filtros informados.
    $where = [];
    $params = [];

    if ($dataInicio !== '') {
      $where[] = 'a.data_atendimento >= :data_inicio';
      $params[':data_inicio'] = $dataInicio;
    }

    if ($dataFim !== '') {
      $where[] = 'a.data_atendimento <= :data_fim';
      $params[':data_fim'] = $dataFim;
    }

    if ($status !== '') {
      $where[] = 'a.status = :status';
      $params[':status'] = $status;
    }

    $sql = 'SELECT a.id, p.nome AS pessoa_nome, ta.nome AS tipo_nome,
                   u.nome AS usuario_nome, a.data_atendimento,
                   a.horario_atendimento, a.status, a.descricao,
                   a.observacao_final
            FROM atendimentos a
            INNER JOIN pessoas p ON p.id = a.pessoa_id
            INNER JOIN tipos_atendimentos ta ON ta.id = a.tipo_atendimento_id
            INNER JOIN usuarios u ON u.id = a.usuario_id';

    if ($where) {
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY a.data_atendimento DESC, a.horario_atendimento DESC, a.id DESC';

    // Consulta parametrizada evita SQL Injection mesmo com WHERE dinâmico.
    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $key => $value) {
      $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    // Passa os dados para a view via variáveis — o controller não renderiza HTML.
    $atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filtros = [
      'data_inicio' => $dataInicio,
      'data_fim'    => $dataFim,
      'status'      => $status,
    ];

    require __DIR__ . '/../views/relatorios/index.php';
  }
}
