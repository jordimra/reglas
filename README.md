# ⚙️ Motor de Inferencia (Sistema Basado en Reglas)

Una herramienta web ligera y pedagógica para generar y resolver sistemas expertos basados en reglas. Desarrollada en **PHP**, **JS** y **CSS** puro, sin dependencias externas.

![Licencia](https://img.shields.io/badge/license-MIT-blue.svg) ![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)

## 📋 Características

Este proyecto implementa un motor de inferencia simbólico completo con las siguientes capacidades:

### ⚙️ Algoritmos de Inferencia
- **Encadenamiento Hacia Adelante (Forward Chaining):** Deduce nuevos hechos a partir de los conocidos hasta saturar la base de conocimiento.
- **Encadenamiento Hacia Atrás (Backward Chaining):** Verifica hipótesis (objetivos) descomponiéndolas en sub-metas hasta llegar a los hechos conocidos.

### ⚔️ Estrategias de Resolución de Conflictos
Cuando múltiples reglas pueden aplicarse, el sistema decide cuál ejecutar basándose en:
1. **Obstinancia:** Evita que una misma regla se dispare más de una vez (para evitar bucles).
2. **Especificidad:** Prioriza las reglas con mayor número de premisas (más específicas).
3. **Recencia:** Da prioridad a reglas que utilizan hechos descubiertos más recientemente.
4. **Orden Textual:** Criterio de desempate por defecto basado en el orden de declaración (R1, R2...).

### 🛠️ Funcionalidades de la Interfaz
- **Gestión Dinámica de Reglas:** Añade, edita y elimina reglas (sintaxis `A + B -> C`) desde la UI.
- **Trazas Detalladas:** Visualización paso a paso de la ejecución, mostrando qué regla se dispara y cómo evoluciona la Memoria de Trabajo en cada ciclo.
- **Importar / Exportar:** Guarda y carga configuraciones completas (reglas, hechos y opciones) mediante archivos JSON.
- **Diseño Responsivo:** Interfaz limpia y moderna.

## 🚀 Instalación y Uso

Al ser una aplicación PHP nativa, solo necesitas un servidor web local.

1. **Clona el repositorio:**
   ```bash
   git clone https://github.com/jordimra/reglas
   ```

2. **Estructura de archivos:**
   Asegúrate de tener los tres archivos en la misma carpeta de tu servidor (`htdocs`, `www`, etc.):
   - `index.php`: Lógica del motor y vista principal.
   - `script.js`: Lógica del cliente (DOM, I/O).
   - `style.css`: Estilos visuales.

3. **Ejecuta:**
   Abre tu navegador y ve a `http://localhost/tu-carpeta/index.php`.

## 📖 Ejemplo de Uso

### Sintaxis
- **Reglas:** `A + B -> C` (Si A y B son ciertos, entonces C).
- **Hechos (Adelante):** `A0, B1` (Hecho A conocido en ciclo 0, B en ciclo 1).
- **Hechos (Atrás):** `A, B` (Hechos conocidos).

### Caso de Prueba
Intenta importar este JSON o configurar manualmente:
- **Algoritmo:** Hacia Atrás
- **Objetivo:** `Z`
- **Hechos:** `A, B`
- **Reglas:**
  - R1: `F + C -> Z`
  - R2: `A + B -> C`
  - R3: `D -> F`

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor, abre un *issue* o un *pull request* para sugerencias.

## ✒️ Autor

* **jordimra** - [Perfil de GitHub](https://github.com/jordimra)

---
*Proyecto creado con fines educativos para la comprensión de la IA Simbólica.*
