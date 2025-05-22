# Changelog

All notable changes to the WooCommerce Busca CEP por Endereço project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Suporte para WooCommerce 8.0+
- Opção para desativar o modal no checkout

### Fixed

- Compatibilidade com temas que modificam o checkout

## [1.1.1] - 2023-11-15

### Added

- Shortcode `[busca_cep]` para uso em qualquer página
- Validação adicional nos campos de entrada
- Suporte básico para traduções (en_US)

### Changed

- Melhorias na estrutura de código
- Atualização das dependências

### Fixed

- Correção de bug ao lidar com caracteres especiais
- Problema com campos de bairro em alguns temas

## [1.1.0] - 2023-10-20

### Added

- Página de configurações no painel admin
- Personalização de texto do link
- Opções de posicionamento do link
- Seleção de cores personalizadas
- Sistema de notificação de erros

### Changed

- Refatoração completa do código JavaScript
- Melhor tratamento de respostas da API

### Fixed

- Problema com cache de navegador
- Conflito com outros plugins de checkout

## [1.0.0] - 2023-09-01

### Added

- Funcionalidade básica de busca por CEP
- Integração com checkout do WooCommerce
- Modal de busca com formulário
- Exibição de múltiplos resultados
- Preenchimento automático de campos
