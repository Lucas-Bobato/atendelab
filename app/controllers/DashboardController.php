<?php

// Importa a conexão com o banco de dados.
require_once __DIR__ . '/../../config/database.php';

class DashboardController
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

  public function resumo(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    // Contagens gerais usadas pelos cards do dashboard.
    $totalPessoas = (int) $this->pdo->query('SELECT COUNT(*) FROM pessoas')->fetchColumn();
    $totalTipos = (int) $this->pdo->query('SELECT COUNT(*) FROM tipos_atendimentos')->fetchColumn();
    $totalAtendimentos = (int) $this->pdo->query('SELECT COUNT(*) FROM atendimentos')->fetchColumn();

    // Os 5 atendimentos mais recentes para exibição na tabela do dashboard.
    $sql = 'SELECT a.id, p.nome AS pessoa_nome, ta.nome AS tipo_nome, a.status, a.data_atendimento
            FROM atendimentos a
            INNER JOIN pessoas p ON p.id = a.pessoa_id
            INNER JOIN tipos_atendimentos ta ON ta.id = a.tipo_atendimento_id
            ORDER BY a.id DESC LIMIT 5';
    $atendimentosRecentes = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      'indicadores' => [
        'total_pessoas'      => $totalPessoas,
        'total_tipos'        => $totalTipos,
        'total_atendimentos' => $totalAtendimentos,
      ],
      'atendimentos_recentes' => $atendimentosRecentes,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }
}
