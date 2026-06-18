const passEl    = document.getElementById('fs-pass');
const confirmEl = document.getElementById('fs-confirm');
const slabel    = document.getElementById('fs-slabel');
const matchEl   = document.getElementById('fs-match');
const bars      = [
  document.getElementById('b1'),
  document.getElementById('b2'),
  document.getElementById('b3'),
  document.getElementById('b4'),
];

// Calcula nível de força (0–4)
function getStrength(p) {
  let score = 0;
  if (p.length >= 6)  score++;
  if (p.length >= 10) score++;
  if (/[A-Z]/.test(p) && /[0-9]/.test(p)) score++;
  if (/[^A-Za-z0-9]/.test(p)) score++;
  return score;
}

// Atualiza as barrinhas e o label
function updateStrengthUI(p) {
  const score = getStrength(p);
  const labels = ['', 'Fraca', 'Razoável', 'Boa', 'Forte'];
  const classes = ['', 'weak', 'medium', 'strong', 'strong'];
  const colors  = ['', '#e63946', '#f4a261', '#2dc653', '#2dc653'];

  bars.forEach((bar, i) => {
    bar.className = i < score ? classes[score] : '';
  });

  slabel.textContent = p.length ? labels[score] : '';
  slabel.style.color = p.length ? colors[score] : '#555';
}

// Verifica se as senhas coincidem
function checkMatch() {
  const p = passEl.value;
  const c = confirmEl.value;

  if (!c) {
    matchEl.textContent = '';
    return;
  }

  if (p === c) {
    matchEl.textContent = 'Senhas coincidem ✓';
    matchEl.style.color = '#2dc653';
  } else {
    matchEl.textContent = 'Senhas não coincidem';
    matchEl.style.color = '#e63946';
  }
}

// Eventos
passEl.addEventListener('input', () => {
  updateStrengthUI(passEl.value);
  checkMatch();
});

confirmEl.addEventListener('input', checkMatch);

// Submit
function handleSubmit(event) {
  event.preventDefault();

  const email = document.getElementById('fs-email').value.trim();
  const pass  = passEl.value;
  const conf  = confirmEl.value;

  if (!email || !pass || !conf) {
    alert('Preencha todos os campos.');
    return;
  }

  if (pass !== conf) {
    alert('As senhas não coincidem.');
    return;
  }

  if (pass.length < 6) {
    alert('A senha deve ter pelo menos 6 caracteres.');
    return;
  }

  // Enviar dados para o servidor
  const formData = new FormData();
  formData.append('email', email);
  formData.append('nova_senha', pass);
  formData.append('confirmar_senha', conf);

  fetch('NovaSenha.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Exibe tela de sucesso
      document.getElementById('card-body').style.display = 'none';
      document.getElementById('card-success').style.display = 'block';
    } else {
      alert('Erro: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Erro:', error);
    alert('Erro ao processar a solicitação.');
  });
}