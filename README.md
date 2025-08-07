# Astro-Utils-WP

Utilidades para integrar WordPress con Astro. Expone campos de Elementor, permite filtrar páginas por template y añade CPT elementor_library a REST API.

## Características

-   Expone campos de Elementor en la API REST de WordPress
-   Permite filtrar páginas por tipo de plantilla de Elementor
-   Añade el CPT elementor_library a la API REST
-   **Nuevo:** Panel de administración para configurar y ejecutar webhooks
-   **Nuevo:** Configuración de URL y secret para webhooks
-   **Nuevo:** Interfaz para ejecutar webhooks manualmente desde el admin

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

## Uso del Webhook

El plugin incluye funcionalidad para configurar y ejecutar webhooks desde el panel de administración de WordPress.

### Configuración

1. Ve a **Astro Utils > Configuración** en el panel de administración
2. Configura los siguientes campos:
   - **URL del Webhook**: La URL completa donde se enviará el webhook
   - **Secret Webhook**: El secret utilizado para autenticar el webhook
3. Guarda la configuración

### Ejecución del Webhook

1. Ve a **Astro Utils > Ejecutar Webhook** en el panel de administración
2. Haz clic en el botón "Ejecutar Webhook"
3. El sistema enviará una petición POST con los siguientes datos:

```json
{
    "event": "publish",
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Headers de la petición

- `X-Webhook-Secret`: El secret configurado
- `Content-Type`: application/json

### Ejemplo de petición cURL equivalente

```bash
curl --location 'https://tu-url-configurada.com/webhook' \
--header 'X-Webhook-Secret: tu-secret-configurado' \
--header 'Content-Type: application/json' \
--data '{
    "event": "publish",
    "timestamp": "2024-01-15T10:30:00Z"
}'
```

## Documentación

Para más información sobre cómo usar Prettier con PHP en este proyecto, consulta el archivo `docs/prettier-php-guide.md`.

## Licencia

GPL v2 o posterior
