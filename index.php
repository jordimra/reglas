<?php
class InferenceEngine {
    private $rules = [];
    private $workingMemory = []; 
    private $firedRules = [];
    private $log = [];
    private $strategies = [];
    private $cycle = 0;
    private $mode = 'forward'; 

    public function __construct($rulesInput, $memoryInput, $strategies, $mode) {
        $this->strategies = $strategies;
        $this->mode = $mode;
        
        $this->parseRules($rulesInput);
        $this->parseMemory($memoryInput);
        
        if ($this->mode === 'forward' && !empty($this->workingMemory)) {
            $this->cycle = max($this->workingMemory) + 1;
        }
    }

    // --- HELPER PARA GENERAR LOG ---
    private function addLog($message, $type = '') {
        // Formatear memoria
        $memStrParts = [];
        foreach($this->workingMemory as $k => $v) {
            $memStrParts[] = ($this->mode === 'forward') ? "$k$v" : $k;
        }
        sort($memStrParts);
        $memDisplay = implode(', ', $memStrParts);
        if (empty($memDisplay)) $memDisplay = "Vacía";

        // HTML limpio (SIN SALTO DE LÍNEA AL PRINCIPIO)
        $this->log[] = "<div class='step-log $type'><span class='log-msg'>$message</span><span class='wm-tag'>Mem: [$memDisplay]</span></div>";
    }

    private function parseRules($inputs) {
        foreach ($inputs as $index => $line) {
            if (trim($line) === '') continue;
            $parts = explode('->', $line);
            if (count($parts) !== 2) continue;
            $premises = array_map('trim', explode('+', $parts[0]));
            $conclusion = trim($parts[1]);
            $this->rules[] = ['id' => $index + 1, 'raw' => $line, 'premises' => $premises, 'conclusion' => $conclusion];
        }
    }

    private function parseMemory($input) {
        $items = explode(',', $input);
        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item)) continue;
            if ($this->mode === 'forward') {
                if (preg_match('/^([A-Z]+)(\d+)$/', $item, $matches)) {
                    $this->workingMemory[$matches[1]] = (int)$matches[2];
                } else {
                    $this->workingMemory[$item] = 0; 
                }
            } else {
                $cleanItem = preg_replace('/[0-9]+/', '', $item); 
                $this->workingMemory[$cleanItem] = 0; 
            }
        }
    }

    public function getLog() { return $this->log; }

    private function sortRules(&$candidateRules) {
        usort($candidateRules, function($a, $b) {
            // A. Especificidad
            if (in_array('specificity', $this->strategies)) {
                $specA = count($a['premises']);
                $specB = count($b['premises']);
                if ($specA !== $specB) return $specB - $specA; 
            }
            
            // B. Recencia
            if (in_array('recency', $this->strategies)) {
                $maxCycleA = -1;
                $maxCycleB = -1;
                foreach($a['premises'] as $p) if(isset($this->workingMemory[$p])) $maxCycleA = max($maxCycleA, $this->workingMemory[$p]);
                foreach($b['premises'] as $p) if(isset($this->workingMemory[$p])) $maxCycleB = max($maxCycleB, $this->workingMemory[$p]);
                
                if ($maxCycleA !== $maxCycleB) return $maxCycleB - $maxCycleA;
            }

            // C. Orden Textual (AHORA ES OPCIONAL)
            if (in_array('order', $this->strategies)) {
                return $a['id'] - $b['id'];
            }

            // Si llegamos aquí y no hay más criterios, es un empate total (0)
            return 0; 
        });
    }

    // --- FORWARD (Ahora acepta Objetivo) ---
    public function solveForward($goal = '') {
        $goal = trim($goal);
        $msg = "<strong>Algoritmo:</strong> Encadenamiento Hacia Adelante";
        if($goal) $msg .= ". Objetivo: <strong>$goal</strong>";
        
        $this->addLog($msg);
        $stepsLimit = 50; 
        
        while ($stepsLimit-- > 0) {
            // Check si ya cumplimos el objetivo antes de seguir
            if ($goal && array_key_exists($goal, $this->workingMemory)) {
                 $this->addLog("<strong>¡Objetivo Alcanzado!</strong> El hecho <strong>$goal</strong> ha sido deducido.", "success");
                 return; // Paramos si encontramos el objetivo
            }

            $conflictSet = [];
            foreach ($this->rules as $rule) {
                if (in_array('obstinacy', $this->strategies) && in_array($rule['id'], $this->firedRules)) continue;
                
                $allPremisesMet = true;
                foreach ($rule['premises'] as $premise) {
                    if (!array_key_exists($premise, $this->workingMemory)) {
                        $allPremisesMet = false; break;
                    }
                }
                if ($allPremisesMet && !array_key_exists($rule['conclusion'], $this->workingMemory)) {
                    $conflictSet[] = $rule;
                }
            }

            if (empty($conflictSet)) {
                $this->addLog("Fin: Base de hechos saturada (no hay más reglas aplicables).");
                break;
            }

            $this->sortRules($conflictSet);
            $winner = $conflictSet[0];

            $this->workingMemory[$winner['conclusion']] = $this->cycle;
            $this->firedRules[] = $winner['id'];
            
            $this->addLog("<strong>Ciclo {$this->cycle}:</strong> Se dispara R{$winner['id']} ({$winner['raw']}) &rarr; Agrega <strong>{$winner['conclusion']}</strong>", "info");
            $this->cycle++;
        }
        
        // Comprobación final del objetivo si el bucle terminó por saturación
        if ($goal) {
            if (array_key_exists($goal, $this->workingMemory)) {
                $this->addLog("<strong>Resultado:</strong> El objetivo <strong>$goal</strong> fue encontrado en memoria.", "success");
            } else {
                $this->addLog("<strong>Resultado:</strong> No se pudo deducir el objetivo <strong>$goal</strong>.", "fail");
            }
        } else {
            $this->addLog("<strong>Proceso finalizado.</strong>", "success");
        }
    }

    // --- BACKWARD ---
    public function solveBackward($goal) {
        $goal = trim($goal);
        if (empty($goal)) { $this->addLog("Error: No se definió objetivo.", "fail"); return; }
        
        $this->addLog("<strong>Algoritmo:</strong> Hacia Atrás. Objetivo: <strong>$goal</strong>");
        
        if ($this->verify($goal)) {
            $this->addLog("<strong>CONCLUSIÓN:</strong> El hecho <strong>$goal</strong> es VERDADERO.", "success");
        } else {
            $this->addLog("<strong>CONCLUSIÓN:</strong> No se pudo demostrar <strong>$goal</strong>.", "fail");
        }
    }

    private function verify($goal, $depth = 0) {
        $indent = str_repeat("&nbsp;&nbsp;&nbsp;", $depth);
        $arrow = $depth > 0 ? "↳" : "";
        
        if (array_key_exists($goal, $this->workingMemory)) {
            $this->addLog("{$indent}{$arrow} <strong>$goal</strong> está en la base de hechos. (OK)", "success");
            return true;
        }

        $candidates = [];
        foreach ($this->rules as $rule) {
            if ($rule['conclusion'] === $goal) {
                $candidates[] = $rule;
            }
        }

        if (empty($candidates)) {
            $this->addLog("{$indent}{$arrow} No hay reglas para <strong>$goal</strong> y no es conocido. (FALLO)", "fail");
            return false;
        }

        $this->sortRules($candidates);

        foreach ($candidates as $rule) {
            $this->addLog("{$indent}{$arrow} Intentando aplicar R{$rule['id']}: <strong>{$rule['raw']}</strong>");
            
            $allPremisesTrue = true;
            foreach ($rule['premises'] as $premise) {
                if (!$this->verify($premise, $depth + 1)) {
                    $allPremisesTrue = false;
                    break;
                }
            }

            if ($allPremisesTrue) {
                $this->workingMemory[$goal] = 0; 
                $this->addLog("{$indent} <span style='color:green'>✔ Regla R{$rule['id']} válida. <strong>$goal</strong> demostrado.</span>", "success");
                return true;
            } else {
                $this->addLog("{$indent} <span style='color:red'>✖ Regla R{$rule['id']} falló.</span>");
            }
        }
        return false;
    }
}

// SETUP Y CHECKBOXES
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$selectedOptions = $_POST['options'] ?? [];
function checkOpt($val, $isPost, $selected) {
    if ($isPost) { return in_array($val, $selected) ? 'checked' : ''; }
    return ($val === 'obstinacy') ? 'checked' : '';
}

// PROCESAMIENTO
$logs = [];
if ($isPost) {
    $chaining = $_POST['chaining'] ?? 'forward';
    $memory = $_POST['memory'] ?? '';
    $rules = $_POST['rules'] ?? [];
    $options = $_POST['options'] ?? [];
    $goal = $_POST['goal'] ?? '';

    $rules = array_filter($rules, function($v) { return !empty(trim($v)); });
    $engine = new InferenceEngine($rules, $memory, $options, $chaining);

    if ($chaining === 'forward') {
        $engine->solveForward($goal); // Pasamos el objetivo aquí
    } else {
        $engine->solveBackward($goal);
    }
    
    $logs = $engine->getLog();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motor de Inferencia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>⚙️ Motor de Inferencia</h1>
    
    <form method="POST" action="index.php">
        
        <div class="control-group">
            <h2>📂 Gestión de Archivos</h2>
            <div class="btn-group">
                <button type="button" id="btn-export" class="btn-export">💾 Exportar Configuración</button>
                <button type="button" id="btn-import" class="btn-import">📂 Importar Configuración</button>
                <input type="file" id="import-file" accept=".json">
            </div>
        </div>
        
        <div class="control-group">
            <h2>1. Estrategia</h2>
            <div class="radio-group">
                <label><input type="radio" name="chaining" value="forward" <?php echo (!isset($_POST['chaining']) || $_POST['chaining']=='forward')?'checked':''; ?>> Hacia adelante</label>
                <label><input type="radio" name="chaining" value="backward" <?php echo (isset($_POST['chaining']) && $_POST['chaining']=='backward')?'checked':''; ?>> Hacia atrás</label>
            </div>
            <hr>
            <div class="checkbox-group">
                <label>Resolución de Conflictos:</label><br>
                <label><input type="checkbox" name="options[]" value="obstinacy" <?php echo checkOpt('obstinacy', $isPost, $selectedOptions); ?>> Obstinancia</label>
                <label><input type="checkbox" name="options[]" value="specificity" <?php echo checkOpt('specificity', $isPost, $selectedOptions); ?>> Especificidad</label>
                <label><input type="checkbox" name="options[]" value="recency" <?php echo checkOpt('recency', $isPost, $selectedOptions); ?>> Recencia</label>
                
                <label>
                    <input type="checkbox" name="options[]" value="order" <?php echo checkOpt('order', $isPost, $selectedOptions); ?>> 
                    Orden Textual
                </label>
            </div>
        </div>

        <div class="control-group">
            <h2>2. Datos</h2>
            <label id="memory-label" for="memory">Elementos conocidos:</label>
            <input type="text" id="memory" name="memory" 
                   value="<?php echo isset($_POST['memory']) ? htmlspecialchars($_POST['memory']) : 'A0, F1'; ?>">
            
            <div id="goal-container">
                <label for="goal" style="color: var(--accent);">Objetivo a demostrar (Opcional en Forward):</label>
                <input type="text" id="goal" name="goal" 
                       value="<?php echo isset($_POST['goal']) ? htmlspecialchars($_POST['goal']) : 'Z'; ?>" placeholder="Ej: Z">
            </div>
        </div>

        <div class="control-group">
            <h2>3. Reglas</h2>
            <div id="rules-container">
                <?php 
                $displayRules = !empty($_POST['rules']) ? $_POST['rules'] : ['A + F -> Z', 'B -> Z'];
                foreach($displayRules as $i => $r): ?>
                <div class="rule-item">
                    <span>R<?php echo $i + 1; ?>: </span>
                    <input type="text" name="rules[]" value="<?php echo htmlspecialchars($r); ?>" required>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-rule-btn" class="btn-secondary" style="margin-top:10px;">+ Añadir Regla</button>
        </div>

        <button type="submit">🚀 Ejecutar</button>
    </form>

    <?php if (!empty($logs)): ?>
    <div class="control-group">
        <h2>📊 Trazas de ejecución</h2>
        <?php foreach ($logs as $line): ?>
            <?php echo $line; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script src="script.js"></script>
</body>
</html>