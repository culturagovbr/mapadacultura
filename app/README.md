# MapaCultural - Nova Arquitetura

Essa é a nova documentação do MapaCultural, voltada para desenvolvedores e/ou entusiastas do código.

## Instalação

Para instalar as dependências e atualizar o autoload, entre no container da aplicação e execute:
```shell
composer.phar install
```

---

## Arquitetura


### Web


--- 

### API
Para criar novos endpoints siga o passo a passo:

#### 1 - Controller
Crie uma nova classe em `/app/Controller/Api/`, por exemplo, `EventApiController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

class EventApiController
{
    
}
```

#### 2 - Método/Action
Crie seu(s) método(s) com a lógica de resposta.

> Para gerar respostas em json, estamos utilizando a implementação da `JsonResponse` fornecida pelo pacote do Symfony:

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

class EventApiController
{
    public function getList(): JsonResponse
    {
        $events = [
            ['id' => 1, 'name' => 'Palestra'],
            ['id' => 2, 'name' => 'Curso'],
        ];   
    
        return new JsonResponse($events);
    }
}
```

#### 3 - Rotas

Acesse o arquivo `/app/routes/api.php` e adicione sua rota, informando o `path`, o verbo HTTP, e apontando pro seu controller e método

```php
use App\Controller\Api\EventApiController;
use Symfony\Component\HttpFoundation\Request;

$routes = [
    //... other routes
    '/api/v2/events' => [
        Request::METHOD_GET => [EventApiController::class, 'getList']
    ],
];
```

Se preferir pode criar um arquivo no diretório `/app/routes/api/` e isolar suas novas rotas nesse arquivo, basta fazer isso:

```php
// /app/routes/api/event.php

use App\Controller\Api\EventApiController;
use Symfony\Component\HttpFoundation\Request;

return [
    '/api/v2/events' => [
        Request::METHOD_GET => [EventApiController::class, 'getList'],
        Request::METHOD_POST => [EventApiController::class, 'create'],
    ],
];
```

Esse seu novo arquivo será reconhecimento automagicamente dentro da aplicação.

#### 4 - Pronto

Feito isso, seu endpoint deverá estar disponivel em:
<http://localhost/api/v2/events>

E deve estar retornando algo como:
```json
[
  {
    "id": 1,
    "name": "Palestra"
  },
  {
    "id": 2,
    "name": "Curso"
  }
]
```

---

### Repository

A camada responsável pela comunicação entre nosso código e o banco de dados.

Para criar um novo Repository, siga o passo a passo a seguir:

#### Passo 1 - Crie sua classe no `/app/src/Repository` e extenda a classe abstrata `AbstractRepository`

```php
<?php

declare(strict_types=1);

namespace App\Repository;

class MyRepository extends AbstractRepository
{
}
```

#### Passo 2 - Defina a Entity principal que esse repositório irá gerenciar

```php

use Doctrine\Persistence\ObjectRepository;
use App\Entity\MyEntity;
...

private ObjectRepository $repository;

public function __construct()
{
    parent::__construct();
    
    $this->repository = $this->entityManager->getRepository(MyEntity::class);
}
```

caso a sua entidade não esteja mapeada nessa parte da aplicação (V8), você precisará de um `entityManager` diferente, observer a seguir:

```php
use Doctrine\Persistence\ObjectRepository;
use MapasCulturais\Entities\MyEntity;
...

private ObjectRepository $repository;

public function __construct()
{
    parent::__construct();
    
    $this->repository = $this->mapaCulturalEntityManager->getRepository(MyEntity::class);
}
```

### Command
Comandos são entradas via CLI (linha de comando) que permitem automatizar alguns processos, como rodar testes, veririfcar estilo de código, e debugar rotas

#### Passo 1 - Criar uma nova classe em `app/src/Command/`:

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;use Symfony\Component\Console\Input\InputInterface;use Symfony\Component\Console\Output\OutputInterface;

class MyCommand extends Command
{
    protected static string $defaultName = 'app:my-command';
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello World!');
        
        return Command::SUCCESS;  
    }
} 
```

#### Passo 2 - Testar seu comando no CLI

Entre no container da aplicação PHP e execute isso

```shell
php app/bin/console app:my-command
```

Você deverá ver na tela o texto `Hello World!`

#### Passo 3 - Documentação do pacote
Para criar e gerenciar os nosso commands estamos utilizando o pacote `symfony/console`, para ver sua documentação acesse:

Saiba mais em <https://symfony.com/doc/current/console.html>

---

## Testes
Estamos utilizando o PHPUnit para criar e gerenciar os testes automatizados, focando principalmente nos testes funcionais, ao invés dos testes unitários.

Documentação do PHPUnit: <https://phpunit.de/index.html>

### Criar um novo teste
Para criar um no cenário de teste funcional, basta adicionar sua nova classe no diretório `/app/tests/functional/`, com o seguinte código:

```php
<?php

namespace App\Tests;

class MeuTest extends AbstractTestCase
{
    
}
```

Adicione dentro da classe os cenários que você precisa garantir que funcionem, caso precise imprimir algo na tela para "debugar", utilize o método `dump()` fornecido pela classe `AbstractTestCase`:

```php
public function testIfOneIsOne(): void
{
    $list = ['Mar', 'Minino'];
    
    $this->dump($list); // equivalente ao print_r
    
    $this->assertEquals(
        'MarMinino',
        implode('', $list)
    );
}
```


### Execução
Para executar os testes, entre no container da aplicação e execute o seguinte comando:

```shell
php app/bin/console tests:backend {path}
```

O `path` é opcional, o padrão é "app/tests"

## Style Code
Para executar o PHP-CS-FIXER basta entrar no container da aplicação e executar
```
php app/bin/console app:code-style
```

## Fixtures
Para executar o conjunto de fixtures basta entrar no container da aplicação e executar
```
php app/bin/console app:fixtures
```

## Debug router
Para listas as routas basta entrar no container da aplicação e executar
```
php app/bin/console debug:router
```

> Podemos usar as flags --show-actions e --show-controllers