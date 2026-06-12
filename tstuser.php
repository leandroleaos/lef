<?php
// Configurações do banco de dados
$host = 'localhost';
$db   = 'seu_banco_de_dados';
$user = 'seu_usuario';
$pass = 'sua_senha';


    
    // Dados que seriam recebidos de um formulário ($_POST)
    $email_usuario = "exemplo@email.com";
    $senha_pura = "delphi"; // Senha que o usuário digitou

    // CRIAÇÃO DO HASH (O passo mais importante)
    $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);

    // Preparar a query SQL (usando Prepared Statements para evitar SQL Injection)
    //$sql = "INSERT INTO usuarios (email, senha) VALUES (:email, :senha)";
    //$stmt = $pdo->prepare($sql);

    // Executar a inserção
    //$stmt->execute([
    //    ':email' => $email_usuario,
    //    ':senha' => $senha_hash
    //]);
    echo '||';
	echo $senha_hash;
    echo '||';
    echo "Usuário cadastrado com sucesso!";
?>
