# Barões MC Digital

Plataforma institucional e de relacionamento para clubes, associações e comunidades que desejam apresentar sua história, fortalecer sua marca e administrar todo o conteúdo do site sem depender de alterações no código.

O projeto combina um site público responsivo com um painel administrativo seguro para gerenciar identidade visual, conteúdo, mídia, agenda, equipe, parceiros e acessos.

## Proposta de valor

- Presença digital profissional para o clube.
- Atualização de conteúdo feita pela própria administração.
- Identidade visual centralizada em um único painel.
- Galeria de fotos, eventos, diretoria e parceiros em uma experiência integrada.
- Base leve, sem dependência de serviços externos obrigatórios.
- Estrutura preparada para hospedagem PHP tradicional, incluindo Apache/WAMP.

## Recursos do produto

### Site público

- Página institucional responsiva para desktop e dispositivos móveis.
- Identidade visual configurável: logo, favicon, banner, textos e áudio opcional.
- SEO básico e labels de navegação administráveis.
- Diretoria e equipe com foto, cargo, biografia e ordenação.
- Agenda de eventos com data, local, descrição e banner.
- Galeria de imagens com descrições e visualização em modal.
- Seleção dinâmica de imagens da galeria para cada carregamento da página.
- Área de parceiros com descrição, site, rede social, WhatsApp e PDF interativo.
- Abertura de PDFs em modal e acesso à versão completa.
- Links de contato e redes sociais configuráveis.

### Painel administrativo

- Autenticação para acesso ao painel.
- Gestão de identidade do site e mídias institucionais.
- Cadastro, edição e remoção de registros.
- Cards responsivos com informações completas e ações organizadas.
- Modais de edição centralizados e adaptados ao conteúdo.
- Preview de imagens, PDFs e áudio antes do envio.
- Upload validado por extensão, MIME, tamanho e tipo de arquivo.
- Máscara de WhatsApp com `+55`, DDD e telefone de 8 ou 9 dígitos.
- Descrições opcionais claramente identificadas nos formulários.
- Alteração segura da senha do usuário autenticado.

## Público recomendado

O Barões MC Digital é indicado para:

- moto clubes e clubes esportivos;
- associações e organizações comunitárias;
- projetos sociais e culturais;
- grupos que precisam divulgar agenda, equipe, parceiros e mídia;
- empresas ou instituições que desejam um site institucional administrável.

## Stack e arquitetura

- PHP 8.1+ com tipagem estrita nas camadas internas.
- SQLite com migrations versionadas.
- PDO para acesso ao banco de dados.
- HTML semântico, CSS responsivo e JavaScript modular.
- Bootstrap para componentes administrativos.
- Font Awesome para ícones da interface.
- Apache como servidor web recomendado.

### Organização do projeto

```text
app/
├── Controllers/       Fluxos das requisições administrativas
├── Repositories/      Persistência e consultas de conteúdo
├── Services/          Uploads e regras técnicas
└── Support/           Bootstrap, banco e funções compartilhadas

config/                Configuração centralizada
database/migrations/   Evolução versionada do SQLite
public/
├── assets/            CSS, JavaScript e fontes versionados
├── media/             Conteúdo enviado pelo admin
└── *.php              Entrypoints HTTP
storage/               Banco e dados internos protegidos
tests/                 Smoke tests e verificações automatizadas
views/                 Templates público e administrativo
```

## Requisitos

- PHP 8.1 ou superior;
- extensões `PDO_SQLite`, `Fileinfo` e suporte a upload;
- Apache com `mod_rewrite` e `mod_headers` habilitados;
- permissão de escrita no diretório de armazenamento;
- HTTPS recomendado em produção.

## Instalação rápida

1. Clone ou copie o projeto para o servidor.
2. Configure o Apache para usar `public/` como `DocumentRoot`.
3. Crie um arquivo `.env` local ou defina as variáveis no ambiente do servidor.
4. Na primeira instalação, defina um usuário e uma senha administrativa com pelo menos 12 caracteres.
5. Garanta permissão de escrita para `storage/` e `public/media/`.
6. Acesse `admin.php`. O banco e as migrations serão preparados automaticamente.

Exemplo de configuração local:

```dotenv
BAROES_APP_ENV=production
BAROES_TIMEZONE=America/Sao_Paulo
BAROES_ADMIN_USERNAME=admin
BAROES_ADMIN_PASSWORD=troque-por-uma-senha-forte
BAROES_STORAGE_DIR=/caminho/fora-do-public
BAROES_DB_PATH=/caminho/fora-do-public/data.sqlite
```

As credenciais de criação inicial são usadas somente quando o banco ainda não possui usuários. Nunca publique o arquivo `.env`.

## Operação e conteúdo

Após o primeiro acesso ao painel, a administração pode configurar a identidade do site e cadastrar os conteúdos. O fluxo recomendado é:

1. Configurar logo, favicon, banner, textos e áudio.
2. Cadastrar diretoria e equipe.
3. Cadastrar eventos da agenda.
4. Cadastrar parceiros e seus materiais em PDF.
5. Enviar as imagens da galeria com suas descrições.
6. Revisar o site público em desktop e celular.

## Deploy e persistência

O diretório `public/media/` contém uploads administrados pelo sistema e não deve ser tratado como código-fonte. Ele está protegido pelo `.gitignore` e precisa ser mantido em armazenamento persistente durante deploys.

Faça backup conjunto de:

- banco SQLite em `storage/`;
- diretório `public/media/`;
- arquivo `.env` ou variáveis equivalentes do ambiente.

Em produção:

- mantenha o banco fora do `DocumentRoot`;
- mantenha `display_errors` desativado;
- use HTTPS;
- limite permissões de arquivos e diretórios;
- configure backups automáticos;
- não versione credenciais, banco, logs ou uploads.

## Verificação local

```powershell
Get-ChildItem -Recurse -File -Filter *.php | ForEach-Object { php -l $_.FullName }
php tests/smoke.php
```

## Modelo de entrega comercial

O projeto pode ser entregue em três camadas:

1. **Implantação:** configuração do servidor, banco, HTTPS, usuário inicial e identidade visual.
2. **Personalização:** cores, textos, imagens, navegação, formulários e conteúdos institucionais.
3. **Operação contínua:** suporte, backups, atualizações, segurança e evolução de funcionalidades.

O escopo de cada instalação deve definir os conteúdos fornecidos, os responsáveis pelos acessos, a política de backup e o canal de suporte.

## Licença e uso

O código original desta aplicação é **proprietário**, com direitos reservados aos respectivos titulares. A publicação do repositório permite consulta ao código, mas **não concede autorização geral para copiar, redistribuir, sublicenciar, comercializar ou implantar a solução sem autorização**. Consulte [LICENSE](LICENSE).

A autoria técnica não implica titularidade das marcas e dos conteúdos. O nome Barões MC, logotipos, imagens, materiais dos clubes e outros ativos de terceiros pertencem a seus respectivos titulares e exigem as autorizações adequadas para utilização.

Componentes de terceiros (por exemplo, Bootstrap e Font Awesome) continuam sujeitos às respectivas licenças. Contratos de implantação, suporte e cessão/licenciamento de direitos, quando existentes, prevalecem sobre este aviso na medida aplicável.
