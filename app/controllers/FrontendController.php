<?php

class FrontendController
{
  // Carrega as views das páginas internas. Não usa PDO — só entrega HTML.
  public function pessoas(): void
  {
    require __DIR__ . '/../views/pessoas/index.php';
  }

  public function tipos(): void
  {
    require __DIR__ . '/../views/tipos-atendimentos/index.php';
  }

  public function atendimentos(): void
  {
    require __DIR__ . '/../views/atendimentos/index.php';
  }
}
