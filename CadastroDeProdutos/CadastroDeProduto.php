<?php
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro de Produto</title>
  <link rel="stylesheet" href="CadastroDeProduto.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>

<div class="TopBar">
    <h1>Cadastro de Produtos</h1>
</div>

<div class="page">
  <div class="lado-foto">
    <div class="photo-area" onclick="document.getElementById('photo-input').click()">
      <input type="file" id="photo-input" accept="image/*">
      <img id="preview-img" alt="Preview do produto">
      <i class="ti ti-camera-plus" aria-hidden="true" id="cam-icon"></i>
      <p id="photo-label">Adicionar foto do produto</p>
      <span id="photo-hint">PNG, JPG ou WEBP</span>
    </div>
  </div>

  <!-- Coluna direita: formulário -->
  <form class="lado-form" method="POST" enctype="multipart/form-data">
    <div class="form-header">
      <div class="dot"></div>
      <h1>Cadastro de produto</h1>
    </div>

    <div class="grid-1">
      <label for="nome">Nome do produto</label>
      <input type="text" id="nome" placeholder="Ex: Tênis Nike Jordan 4 Toro Bravo">
    </div>

    <div class="grid-2">
      <div>
        <label for="categoria">Categoria</label>
        <select id="categoria">
          <option value="" disabled selected>Selecione...</option>
          <option>Futebol</option>
          <option>Basquete</option>
          <option>Corrida</option>
          <option>Academia</option>
          <option>Natação</option>
          <option>Suplementos</option>
          <option>Acessórios</option>
        </select>
      </div>
      <div>
        <label>Gênero (opcional)</label>
        <div class="gender-group">
          <button class="gender-btn" onclick="selectGender(this, 'M')">
            <i class="ti ti-man" aria-hidden="true"></i> Homem
          </button>
          <button class="gender-btn" onclick="selectGender(this, 'F')">
            <i class="ti ti-woman" aria-hidden="true"></i> Mulher
          </button>
        </div>
      </div>
    </div>

    <div class="grid-1 mt-16">
      <label>Tamanho</label>
      <div class="size-group">
        <button class="size-pill" onclick="toggleSize(this)">PP</button>
        <button class="size-pill" onclick="toggleSize(this)">P</button>
        <button class="size-pill" onclick="toggleSize(this)">M</button>
        <button class="size-pill" onclick="toggleSize(this)">G</button>
        <button class="size-pill" onclick="toggleSize(this)">GG</button>
        <button class="size-pill" onclick="toggleSize(this)">XG</button>
        <button class="size-pill" onclick="toggleSize(this)">38</button>
        <button class="size-pill" onclick="toggleSize(this)">39</button>
        <button class="size-pill" onclick="toggleSize(this)">40</button>
        <button class="size-pill" onclick="toggleSize(this)">41</button>
        <button class="size-pill" onclick="toggleSize(this)">42</button>
        <button class="size-pill" onclick="toggleSize(this)">43</button>
      </div>
    </div>

    <div class="grid-1">
      <label for="descricao">Descrição</label>
      <textarea id="descricao" placeholder="Descreva o produto: material, marca, características..."></textarea>
    </div>

    <button class="btn-submit" onclick="submitForm()">
      <i class="ti ti-device-floppy" aria-hidden="true"></i> Cadastrar produto
    </button>
  </form>

</div>

  <script src="CadastroDeProduto.js"></script>
</body>
</html>