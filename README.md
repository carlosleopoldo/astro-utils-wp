# Astro-Utils-WP

Utilidades para integrar WordPress con Astro. Expone campos de Elementor, permite filtrar páginas por template y añade CPT elementor_library a REST API.

## Características

-   Expone campos de Elementor en la API REST de WordPress
-   Permite filtrar páginas por tipo de plantilla de Elementor
-   Añade el CPT elementor_library a la API REST

## Instalación

1. Descarga el plugin y súbelo a la carpeta `/wp-content/plugins/`
2. Activa el plugin a través del menú 'Plugins' en WordPress

## Desarrollo

Este proyecto utiliza Prettier para formatear el código PHP y mantener un estilo consistente.

### Configuración de Prettier

Para configurar el entorno de desarrollo:

```bash
# Método rápido: usar el script de instalación
./setup-prettier.sh

# O manualmente:
# Instalar dependencias de Composer
composer install

# Instalar dependencias de npm
npm install
```

### Formatear código

Puedes formatear el código usando cualquiera de estos comandos:

```bash
# Usando Composer
composer run format

# Usando npm
npm run format
```

### Verificar formato

Para verificar si el código cumple con el formato sin modificarlo:

```bash
npm run format:check
```

### Linting

Para verificar el código según los estándares de WordPress:

```bash
composer run lint
```

Para corregir automáticamente los problemas que se puedan solucionar:

```bash
composer run lint:fix
```

### Solución de problemas con el IDE

Si tu IDE muestra errores de diagnóstico relacionados con funciones de WordPress no definidas, esto es normal ya que estás desarrollando un plugin fuera del entorno de WordPress. Para solucionar esto:

1. Usa VSCode con la extensión "PHP Intelephense" para obtener soporte para WordPress
2. La configuración en `.vscode/settings.json` ya incluye los stubs necesarios para WordPress
3. El archivo `stubs.php` contiene definiciones de funciones de WordPress para ayudar al IDE
4. El archivo `phpstan.neon` configura PHPStan para ignorar los errores relacionados con WordPress

Estos archivos no afectan el funcionamiento del plugin, solo mejoran la experiencia de desarrollo.

## Documentación

Para más información sobre cómo usar Prettier con PHP en este proyecto, consulta el archivo `docs/prettier-php-guide.md`.

## Licencia

GPL v2 o posterior
