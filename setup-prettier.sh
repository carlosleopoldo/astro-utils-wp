#!/bin/bash

# Script para configurar Prettier con PHP en el proyecto

echo "Configurando Prettier para PHP en Astro-Utils-WP..."

# Verificar si composer está instalado
if ! command -v composer &> /dev/null; then
    echo "Error: Composer no está instalado. Por favor, instálalo primero."
    echo "Visita https://getcomposer.org/download/ para instrucciones."
    exit 1
fi

# Verificar si npm está instalado
if ! command -v npm &> /dev/null; then
    echo "Error: npm no está instalado. Por favor, instálalo primero."
    echo "Visita https://nodejs.org/ para instrucciones."
    exit 1
fi

# Instalar dependencias de Composer
echo "Instalando dependencias de Composer..."
composer install

# Instalar dependencias de npm
echo "Instalando dependencias de npm..."
npm install

# Verificar si la instalación fue exitosa
if [ $? -eq 0 ]; then
    echo "\nInstalación completada con éxito."
    echo "\nPuedes formatear el código PHP con cualquiera de estos comandos:"
    echo "  - composer run format"
    echo "  - npm run format"
    echo "\nPara verificar el formato sin modificar los archivos:"
    echo "  - npm run format:check"
    echo "\nPara verificar el código según los estándares de WordPress:"
    echo "  - composer run lint"
    echo "\nPara corregir automáticamente los problemas de linting:"
    echo "  - composer run lint:fix"
    
    # Hacer el script ejecutable
    chmod +x "$(dirname "$0")/setup-prettier.sh"
    
    echo "\n¡Listo! Ahora puedes usar Prettier con PHP en tu proyecto."
else
    echo "\nHubo un problema durante la instalación. Por favor, revisa los errores anteriores."
    exit 1
fi