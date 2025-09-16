# API IXC Pay

API REST em Symfony para gerenciamento de contas e transações financeiras: criação de contas, depósito, saque e transferência, com validações de negócio, persistência via Doctrine ORM (MySQL) e testes automatizados.

## Requisitos
- PHP 8.2+
- Composer 2+
- MySQL 8.0+ (já configurado no seu ambiente)
- Extensões PHP: pdo_mysql, intl, zip

## Tecnologias
- Symfony Framework
- Doctrine ORM (MySQL)
- PHPUnit (testes)

## Estrutura
- `src/Controller/`: Controllers HTTP (rotas)
- `src/Service/`: Regras de negócio (serviços)
- `src/Entity/`: Entidades Doctrine
- `src/Repository/`: Repositórios Doctrine
- `config/`: Configurações Symfony/Doctrine
- `migrations/`: Migrations do Doctrine
- `tests/`: Testes automatizados
- `public/`: Document root (index.php)

## Como rodar localmente (sem Docker)
1) Instale dependências PHP
```bash
composer install
```

2) Configure variáveis de ambiente
Crie um arquivo `.env.local` na raiz do projeto com a URL do seu MySQL (ajuste USUARIO, SENHA e BASE):
```env
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="mysql://USUARIO:SENHA@127.0.0.1:3306/BASE?serverVersion=8.0&charset=utf8mb4"
```

3) Crie o banco e rode as migrations
```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

4) Suba o servidor embutido do PHP
```bash
php -S 127.0.0.1:8084 -t public
```
Acesse: `http://localhost:8084`

## Endpoints
Todas as rotas aceitam e retornam JSON.

### Criar conta de usuário
- Método: POST
- URL: `/api/useraccounts`
- Body:
```json
{
  "user_type": "string",
  "full_name": "string",
  "document": "apenas dígitos (CPF/CNPJ)",
  "email": "email@exemplo.com",
  "password": "string"
}
```
- Respostas: 201 (criada), 400 (validação)

Exemplo cURL:
```bash
curl -X POST http://localhost:8084/api/useraccounts \
  -H "Content-Type: application/json" \
  -d '{
    "user_type": "PF",
    "full_name": "Fulano de Tal",
    "document": "12345678901",
    "email": "fulano@example.com",
    "password": "senha"
  }'
```

### Obter conta por ID
- Método: GET
- URL: `/api/useraccounts/{id}`
- Respostas: 200 (ok), 404 (não encontrada)

Exemplo:
```bash
curl http://localhost:8084/api/useraccounts/1
```

### Depósito
- Método: POST
- URL: `/api/deposit`
- Body:
```json
{ "accountId": 1, "amount": 100.0 }
```
- Respostas: 201 (ok), 400 (erro de validação/negócio)

Exemplo:
```bash
curl -X POST http://localhost:8084/api/deposit \
  -H "Content-Type: application/json" \
  -d '{"accountId":1, "amount":100.0}'
```

### Saque
- Método: POST
- URL: `/api/takeoutvalue`
- Body:
```json
{ "accountId": 1, "amount": 50.0 }
```
- Respostas: 201 (ok), 400 (erro de validação/negócio)

Exemplo:
```bash
curl -X POST http://localhost:8084/api/takeoutvalue \
  -H "Content-Type: application/json" \
  -d '{"accountId":1, "amount":50.0}'
```

### Transferência
- Método: POST
- URL: `/api/transfers`
- Body:
```json
{
  "from_document": "12345678901",
  "to_document":   "98765432100",
  "amount": 100.0
}
```
- Respostas: 200 (ok), 400/404 (erro de validação/negócio)

Exemplo:
```bash
curl -X POST http://localhost:8084/api/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "from_document":"12345678901",
    "to_document":"98765432100",
    "amount":100
  }'
```

### Listar transações de um usuário
- Método: GET
- URL: `/api/transfers/{document}?page=1&limit=10`
- Query params: `page` (padrão 1), `limit` (padrão 10, máx. 50)

Exemplo:
```bash
curl "http://localhost:8084/api/transfers/12345678901?page=1&limit=10"
```

## Regras de negócio (resumo)
- Documento do remetente/destinatário deve conter apenas dígitos.
- Remetente com CNPJ (14 dígitos) não pode realizar transferência.
- Valor da transferência deve ser maior que 0 e saldo suficiente é obrigatório.
- Há validação externa antes de efetivar a transferência.
- São persistidos os saldos atualizados e a entidade `Transactions` como histórico.
- Notificações são enviadas via serviço de notificação (mock por padrão em `var/log/notifications.log`).

## Testes
Rodar a suíte:
```bash
vendor/bin/phpunit
```
Arquivo relevante: `tests/Service/TransferServiceTest.php`.

## Problemas comuns
- "Access denied for user" ao criar/usar o banco: revise `DATABASE_URL` no `.env.local`.
- Erros de migrations: confirme MySQL 8 (`serverVersion=8.0`) e banco existente.
- Porta ocupada: altere a porta (ex.: `php -S 127.0.0.1:8085 -t public`).

## Docker (opcional)
Existem `Dockerfile` e `docker-compose.yml`, mas o uso requer virtualização ativa (BIOS/UEFI), WSL2 e Docker Desktop. Após atender os requisitos:
```bash
docker compose up -d --build
```
Depois rode as migrations e acesse `http://localhost:8084`.

---
Em caso de dúvida, informe a mensagem de erro e o passo executado para suporte.
