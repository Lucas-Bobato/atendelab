<?php

class AtendimentosController
{
  private PDO $pdo;

  public function __construct()
  {
    require __DIR__ . '/../../config/database.php';
    $this->pdo = $pdo;
  }

  public function listar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $sql = 'SELECT a.id, a.pessoa_id, p.nome AS pessoa_nome, a.tipo_atendimento, ta.nome AS tipo_atendimento_nome, a.usuario_id, u.nome AS usuario_nome, a.data_atendimento, a.hora_atendimento, a.descricao, a.observacao, a.status, a.criado_em FROM atendimentos a INNER JOIN pessoas p ON p.id = a.pessoa_id INNER JOIN tipos_atendimentos ta ON ta.id = a.tipo_atendimento INNER JOIN usuarios u ON u.id = a.usuario_id ORDER BY a.id DESC';
    $stmt = $this->pdo->query($sql);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function visualizar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
      http_response_code(400);
      echo json_encode(['erro' => 'ID inválido.']);
      return;
    }

    $sql = 'SELECT a.id, a.pessoa_id, p.nome AS pessoa_nome, a.tipo_atendimento, ta.nome AS tipo_atendimento_nome, a.usuario_id, u.nome AS usuario_nome, a.data_atendimento, a.hora_atendimento, a.descricao, a.observacao, a.status, a.criado_em FROM atendimentos a INNER JOIN pessoas p ON p.id = a.pessoa_id INNER JOIN tipos_atendimentos ta ON ta.id = a.tipo_atendimento INNER JOIN usuarios u ON u.id = a.usuario_id WHERE a.id = :id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$atendimento) {
      http_response_code(404);
      echo json_encode(['erro' => 'Atendimento não encontrado.']);
      return;
    }

    echo json_encode($atendimento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function criar(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $pessoaId = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
    $tipoAtendimentoId = filter_input(INPUT_POST, 'tipo_atendimento', FILTER_VALIDATE_INT);
    $usuarioId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    $dataAtendimento = trim($_POST['data_atendimento'] ?? '');
    $horaAtendimento = trim($_POST['hora_atendimento'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $observacao = trim($_POST['observacao'] ?? '');
    $status = trim($_POST['status'] ?? 'Aguardando');

    if (!$pessoaId || !$tipoAtendimentoId || !$usuarioId || $dataAtendimento === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'Pessoa, tipo de atendimento, usuário e data são obrigatórios.']);
      return;
    }

    $dataValidada = \DateTime::createFromFormat('Y-m-d', $dataAtendimento);
    if (!$dataValidada || $dataValidada->format('Y-m-d') !== $dataAtendimento) {
      http_response_code(400);
      echo json_encode(['erro' => 'Data de atendimento inválida.']);
      return;
    }

    if ($horaAtendimento !== '') {
      $horaValidada = \DateTime::createFromFormat('H:i:s', $horaAtendimento) ?: \DateTime::createFromFormat('H:i', $horaAtendimento);
      if (!$horaValidada) {
        http_response_code(400);
        echo json_encode(['erro' => 'Hora de atendimento inválida.']);
        return;
      }
    }

    if (!in_array($status, ['Aguardando', 'Em Atendimento', 'Cancelado', 'Atendido'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido.']);
      return;
    }

    try {
      $sql = 'INSERT INTO atendimentos (pessoa_id, tipo_atendimento, usuario_id, data_atendimento, hora_atendimento, descricao, observacao, status) VALUES (:pessoa_id, :tipo_atendimento, :usuario_id, :data_atendimento, :hora_atendimento, :descricao, :observacao, :status)';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':pessoa_id', $pessoaId, PDO::PARAM_INT);
      $stmt->bindValue(':tipo_atendimento', $tipoAtendimentoId, PDO::PARAM_INT);
      $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
      $stmt->bindValue(':data_atendimento', $dataAtendimento);
      $stmt->bindValue(':hora_atendimento', $horaAtendimento !== '' ? $horaAtendimento : null);
      $stmt->bindValue(':descricao', $descricao !== '' ? $descricao : null);
      $stmt->bindValue(':observacao', $observacao !== '' ? $observacao : null);
      $stmt->bindValue(':status', $status);
      $stmt->execute();

      http_response_code(201);
      echo json_encode(['sucesso' => 'Atendimento criado com sucesso.', 'id' => $this->pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao criar atendimento.']);
    }
  }

  public function atualizarStatus(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = trim($_POST['status'] ?? '');
    $observacao = trim($_POST['observacao'] ?? '');

    if (!$id || $status === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'ID e status são obrigatórios.']);
      return;
    }

    if (!in_array($status, ['Aguardando', 'Em Atendimento', 'Cancelado', 'Atendido'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido.']);
      return;
    }

    try {
      $sql = 'UPDATE atendimentos SET status = :status, observacao = COALESCE(NULLIF(:observacao, ""), observacao) WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':status', $status);
      $stmt->bindValue(':observacao', $observacao);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Status do atendimento atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao atualizar status do atendimento.']);
    }
  }
}