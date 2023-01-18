# Componente `<entity-field>`
Componente que renderiza os campos de uma entidade

### Eventos
- **change** - disparado quando o método `defineNames` é chamado, após a definição do `name` e `nomeCompleto`
  
## Propriedades
- *Entity **entity*** - Entidade
- *String **prop*** - Propriedade da entidade
- *String **label*** - Label do campo
- *String **type*** - Tipo do campo
- *Boolean **hiddenLabel** = false* - Esconde o label do campo
- *Boolean **hideRequired** = false* - Esconde o label de campo obrigatório
- *Number **debounce** = 0* - 
- *String/Array/Object **classes*** - Classes a serem aplicadas no componente
- *String **fieldDescription*** - Descrição do campo

## Slots
- **default**: label do campo
- **input**: Campo

### Importando componente
```PHP
<?php 
$this->import('entity-field');
?>
```
### Exemplos de uso
```HTML
<!-- utilizaçao básica -->
<entity-field :entity="entity" prop="name"></entity-field>

<!-- utilizaçao alterando o label padrão -->
<entity-field :entity="entity" prop="name" label="Novo label do campo"></entity-field>

<!-- utilizaçao escondendo a obrigatoriedade do campo -->
<entity-field :entity="entity" prop="name" hideRequired></entity-field>

<!-- utilizaçao escondendo p label do campo -->
<entity-field :entity="entity" prop="name" hiddenLabel></entity-field>

<!-- utilização com classes personalizadas -->
<entity-field :entity="entity" prop="name" classes="classe-unica"></entity-field>

<entity-field :entity="entity" prop="name" :classes="['classe-um', 'classe-dois']"></entity-field>
```