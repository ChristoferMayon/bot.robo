# Guia de Integração: Webhook WhatsApp Pro

O Webhook permite que seu sistema receba notificações automáticas sempre que uma nova mensagem for recebida pelo robô.

## 1. Funcionamento
Sempre que o robô detectar uma mensagem de entrada (que não seja enviada por ele mesmo), ele fará uma requisição **POST** no formato **JSON** para a URL configurada no seu painel.

## 2. Estrutura do Payload (JSON)
O seu servidor receberá um objeto com as seguintes propriedades:

```json
{
  "session": "nome_da_sessao",
  "from": "5541999999999",
  "message": "Texto da mensagem recebida",
  "timestamp": "2026-03-12T21:23:50.519Z",
  "pushName": "Nome do Contato"
}
```

- `session`: Identificação da instância que recebeu a mensagem.
- `from`: Número do WhatsApp de quem enviou (sem @s.whatsapp.net).
- `message`: Conteúdo textual da mensagem.
- `timestamp`: Data e hora do recebimento em formato ISO.
- `pushName`: Nome configurado no perfil do WhatsApp do remetente.

## 3. Código de Exemplo (PHP Listener)
Abaixo, um exemplo de script para receber, processar e logar as mensagens recebidas:

```php
<?php
// webhook_handler.php

// 1. Recebe o conteúdo bruto da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data) {
    // 2. Extrai os dados
    $sessao   = $data['session'];
    $remetente = $data['from'];
    $mensagem  = $data['message'];
    $nome      = $data['pushName'];
    
    // 3. Log para verificação (Opcional)
    $log = "[" . date('Y-m-d H:i:s') . "] Msg de $nome ($remetente): $mensagem\n";
    file_put_contents('mensagens_recebidas.log', $log, FILE_APPEND);
    
    // 4. Responde ao servidor (Obrigatório responder HTTP 200)
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo "Dados inválidos";
}
?>
```

## 4. Configuração no Painel
1. Vá até a aba **API / Integração**.
2. Clique na sub-aba **Webhook**.
3. Insira a URL completa do seu script (ex: `https://meusite.com/api/webhook_handler.php`).
4. Clique em **ATUALIZAR CONFIGURAÇÃO**.

---
**Nota Técnica:** Se estiver testando localmente (localhost), certifique-se de que o servidor Node.js e o PHP consigam se comunicar. Para produção, utilize sempre HTTPS.
