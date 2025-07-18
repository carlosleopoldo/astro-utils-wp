<?php
/**
 * Ejemplo de archivo PHP para demostrar el formateo con Prettier
 *
 * Este archivo muestra cómo Prettier formateará automáticamente el código PHP
 * según las reglas definidas en .prettierrc
 *
 * @package Astro-Utils-WP
 */

// Ejemplo de función con formato correcto (aplicado por Prettier)
function ejemplo_funcion_sin_formato($param1, $param2 = null, $param3 = [])
{
    if ($param1) {
        echo "Valor de param1: " . $param1;
    } else {
        foreach ($param3 as $key => $value) {
            if ($value > 10) {
                echo "Valor grande: " . $value;
            } else {
                echo "Valor pequeño: " . $value;
            }
        }
    }
    
    return [
        'param1' => $param1,
        'param2' => $param2,
        'param3' => $param3,
    ];
}

// Ejemplo de clase con formato correcto (aplicado por Prettier)
class EjemploClase
{
    private $propiedad1;
    protected $propiedad2;
    public $propiedad3;
    
    public function __construct($prop1, $prop2, $prop3)
    {
        $this->propiedad1 = $prop1;
        $this->propiedad2 = $prop2;
        $this->propiedad3 = $prop3;
    }
    
    public function metodoEjemplo($param)
    {
        if ($param === $this->propiedad1) {
            return true;
        } elseif ($param === $this->propiedad2) {
            return false;
        } else {
            return null;
        }
    }
}

// Este código ya está formateado correctamente según las reglas de Prettier
// definidas en .prettierrc