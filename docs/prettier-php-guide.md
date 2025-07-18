# Guía para usar Prettier con PHP en VSCode

Este documento explica cómo configurar y usar Prettier para formatear automáticamente el código PHP en VSCode.

## Requisitos previos

1. [Node.js](https://nodejs.org/) (v14 o superior)
2. [Composer](https://getcomposer.org/)
3. [VSCode](https://code.visualstudio.com/)
4. [Extensión Prettier para VSCode](https://marketplace.visualstudio.com/items?itemName=esbenp.prettier-vscode)
5. [Extensión PHP Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client) (recomendada para desarrollo de WordPress)

## Configuración inicial

Este proyecto ya incluye todos los archivos de configuración necesarios para usar Prettier con PHP:

- `.prettierrc`: Configuración de Prettier
- `.prettierignore`: Archivos y directorios que Prettier debe ignorar
- `.editorconfig`: Configuración del editor
- `composer.json`: Dependencias de PHP, incluyendo prettier/plugin-php
- `package.json`: Dependencias de Node.js, incluyendo prettier y @prettier/plugin-php
- `.vscode/settings.json`: Configuración de VSCode para usar Prettier
- `phpstan.neon`: Configuración de PHPStan para ignorar errores de WordPress
- `stubs.php`: Definiciones de funciones de WordPress para el IDE

## Instalación

Para configurar el entorno, ejecuta el script de instalación incluido:

```bash
./setup-prettier.sh
```

Este script instalará todas las dependencias necesarias y configurará el proyecto para usar Prettier con PHP.

## Uso en VSCode

1. Abre el proyecto en VSCode
2. Asegúrate de tener instalada la extensión Prettier
3. VSCode detectará automáticamente la configuración de Prettier y la usará para formatear el código PHP

### Formateo automático al guardar

La configuración incluida en `.vscode/settings.json` habilita el formateo automático al guardar un archivo. Esto significa que cada vez que guardes un archivo PHP, Prettier lo formateará automáticamente según las reglas definidas en `.prettierrc`.

### Formateo manual

También puedes formatear manualmente un archivo o selección de código:

1. Selecciona el código que deseas formatear (o no selecciones nada para formatear todo el archivo)
2. Presiona `Shift + Alt + F` o abre la paleta de comandos (`Ctrl + Shift + P`) y busca "Format Document" o "Format Selection"

## Uso desde la línea de comandos

Puedes formatear todos los archivos del proyecto usando uno de estos comandos:

```bash
# Usando Composer
composer run format

# Usando npm
npm run format
```

Para verificar si los archivos cumplen con el formato sin modificarlos:

```bash
npm run format:check
```

## Ejemplos

En la carpeta `examples` encontrarás dos archivos de ejemplo:

- `prettier-example.php`: Código PHP con formato incorrecto
- `prettier-example-formatted.php`: El mismo código después de ser formateado por Prettier

Puedes usar estos archivos para ver cómo Prettier formatea el código PHP según las reglas definidas en `.prettierrc`.

## Personalización

Si deseas personalizar las reglas de formateo, puedes editar el archivo `.prettierrc`. Consulta la [documentación de Prettier](https://prettier.io/docs/en/options.html) para ver todas las opciones disponibles.

## Integración con PHP_CodeSniffer

Este proyecto también incluye PHP_CodeSniffer configurado con los estándares de WordPress. Puedes verificar el código según estos estándares usando:

```bash
composer run lint
```

Y corregir automáticamente los problemas que se puedan solucionar:

```bash
composer run lint:fix
```

## Manejo de errores de diagnóstico en el IDE

Cuando desarrollas plugins de WordPress fuera del entorno de WordPress, es normal que el IDE muestre errores de diagnóstico relacionados con funciones de WordPress no definidas. Este proyecto incluye varias soluciones para este problema:

### 1. Archivo stubs.php

El archivo `stubs.php` contiene definiciones de las funciones y clases más comunes de WordPress. Este archivo no se carga durante la ejecución del plugin, solo sirve para que el IDE reconozca las funciones de WordPress.

### 2. Configuración de VSCode

El archivo `.vscode/settings.json` incluye configuración específica para PHP Intelephense, que mejora el soporte para WordPress en VSCode:

- Incluye los stubs de WordPress
- Configura las rutas de inclusión
- Desactiva la validación de PHP integrada que puede generar falsos positivos

### 3. Configuración de PHPStan

El archivo `phpstan.neon` configura PHPStan para ignorar los errores relacionados con funciones y clases de WordPress no definidas.

## Resolución de problemas

Si encuentras algún problema al usar Prettier con PHP, verifica lo siguiente:

1. Asegúrate de haber instalado todas las dependencias correctamente
2. Verifica que la extensión Prettier esté instalada y habilitada en VSCode
3. Si sigues viendo errores de diagnóstico relacionados con WordPress:
   - Instala la extensión PHP Intelephense
   - Reinicia VSCode
   - Verifica que la configuración en `.vscode/settings.json` se haya cargado correctamente
4. Verifica que no haya conflictos con otras extensiones de formateo

Si el problema persiste, consulta la [documentación de Prettier](https://prettier.io/docs/en/troubleshooting.html) o abre un issue en el repositorio del proyecto.