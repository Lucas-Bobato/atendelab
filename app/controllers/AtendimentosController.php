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

    $sql = 'SELECT a.id, a.pessoa_id, p.nome AS pessoa_nome, a.tipo_atendimento_id, ta.nome AS tipo_atendimento_nome, a.usuario_id, u.nome AS usuario_nome, a.data_atendimento, a.horario_atendimento, a.descricao, a.observacao_final, a.status, a.criado_em FROM atendimentos a INNER JOIN pessoas p ON p.id = a.pessoa_id INNER JOIN tipos_atendimentos ta ON ta.id = a.tipo_atendimento_id INNER JOIN usuarios u ON u.id = a.usuario_id ORDER BY a.id DESC';
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

    $sql = 'SELECT a.id, a.pessoa_id, p.nome AS pessoa_nome, a.tipo_atendimento_id, ta.nome AS tipo_atendimento_nome, a.usuario_id, u.nome AS usuario_nome, a.data_atendimento, a.horario_atendimento, a.descricao, a.observacao_final, a.status, a.criado_em FROM atendimentos a INNER JOIN pessoas p ON p.id = a.pessoa_id INNER JOIN tipos_atendimentos ta ON ta.id = a.tipo_atendimento_id INNER JOIN usuarios u ON u.id = a.usuario_id WHERE a.id = :id';
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
    $tipoAtendimentoId = filter_input(INPUT_POST, 'tipo_atendimento_id', FILTER_VALIDATE_INT);
    $usuarioId = (int) ($_SESSION['usuario']['id'] ?? 0);
    $dataAtendimento = trim($_POST['data_atendimento'] ?? '');
    $horarioAtendimento = trim($_POST['horario_atendimento'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $observacaoFinal = trim($_POST['observacao_final'] ?? '');
    $status = trim($_POST['status'] ?? 'Aguardando');

    if (!$pessoaId || !$tipoAtendimentoId || !$usuarioId || $dataAtendimento === '' || $horarioAtendimento === '' || $descricao === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'Pessoa, tipo de atendimento, usuário autenticado, data, horário e descrição são obrigatórios.']);
      return;
    }

    $dataValidada = \DateTime::createFromFormat('Y-m-d', $dataAtendimento);
    if (!$dataValidada || $dataValidada->format('Y-m-d') !== $dataAtendimento) {
      http_response_code(400);
      echo json_encode(['erro' => 'Data de atendimento inválida.']);
      return;
    }

    $horaValidada = \DateTime::createFromFormat('H:i:s', $horarioAtendimento) ?: \DateTime::createFromFormat('H:i', $horarioAtendimento);
    if (!$horaValidada) {
      http_response_code(400);
      echo json_encode(['erro' => 'Horário de atendimento inválido.']);
      return;
    }

    if (!in_array($status, ['Aguardando', 'Em Atendimento', 'Cancelado', 'Atendido'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: Aguardando, Em Atendimento, Cancelado ou Atendido.']);
      return;
    }

    try {
      $sql = 'INSERT INTO atendimentos (pessoa_id, tipo_atendimento_id, usuario_id, data_atendimento, horario_atendimento, descricao, observacao_final, status) VALUES (:pessoa_id, :tipo_atendimento_id, :usuario_id, :data_atendimento, :horario_atendimento, :descricao, :observacao_final, :status)';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':pessoa_id', $pessoaId, PDO::PARAM_INT);
      $stmt->bindValue(':tipo_atendimento_id', $tipoAtendimentoId, PDO::PARAM_INT);
      $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
      $stmt->bindValue(':data_atendimento', $dataAtendimento);
      $stmt->bindValue(':horario_atendimento', $horarioAtendimento);
      $stmt->bindValue(':descricao', $descricao);
      $stmt->bindValue(':observacao_final', $observacaoFinal !== '' ? $observacaoFinal : null);
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
    $observacaoFinal = trim($_POST['observacao_final'] ?? '');

    if (!$id || $status === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'ID e status são obrigatórios.']);
      return;
    }

    if (!in_array($status, ['Aguardando', 'Em Atendimento', 'Cancelado', 'Atendido'], true)) {
      http_response_code(400);
      echo json_encode(['erro' => 'Status inválido. Use: Aguardando, Em Atendimento, Cancelado ou Atendido.']);
      return;
    }

    if ($status === 'Atendido' && $observacaoFinal === '') {
      http_response_code(400);
      echo json_encode(['erro' => 'A observação final é obrigatória ao concluir o atendimento.']);
      return;
    }

    try {
      $sql = 'UPDATE atendimentos SET status = :status, observacao_final = COALESCE(NULLIF(:observacao_final, ""), observacao_final) WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindValue(':status', $status);
      $stmt->bindValue(':observacao_final', $observacaoFinal !== '' ? $observacaoFinal : null);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      echo json_encode(['mensagem' => 'Status do atendimento atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['erro' => 'Erro ao atualizar status do atendimento.']);
    }
  }
}