<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Manutenção</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fafafa;
            color: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            max-width: 500px;
            padding: 30px;
        }

        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 15px;
        }

        p {
            font-size: 16px;
            color: #333333;
            margin-bottom: 20px;
        }

        .status {
            font-size: 14px;
            color: #666666;
            margin-top: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #ffbb00;
            color: #000000;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn:hover {
            background: #e6a800;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🛠️</div>

        <h1>Estamos em manutenção</h1>

        <p>
            Nosso sistema está passando por melhorias para oferecer uma experiência melhor.
            Voltamos em breve!
        </p>

        <a href="/" class="btn">Tentar novamente</a>

        <div class="status">
            Código: 503 - Serviço indisponível
        </div>
    </div>
</body>
</html>