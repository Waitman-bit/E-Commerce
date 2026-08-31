<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Recuperar senha - Titan Sports</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>Recuperar senha</h1>

    <p>
        Digite o e-mail cadastrado na sua conta.
    </p>

    <form method="POST" action="enviar_codigo.php">

        <label for="email">
            E-mail
        </label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Digite seu e-mail"
            required
        >

        <button type="submit">
            Enviar código
        </button>

    </form>

    <a href="../Login/Login.php">
        Voltar para o login
    </a>

</body>

</html>
