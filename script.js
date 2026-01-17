// script.js
document.addEventListener('DOMContentLoaded', () => {
    const rulesContainer = document.getElementById('rules-container');
    const addRuleBtn = document.getElementById('add-rule-btn');
    
    // Elementos de UI
    const radios = document.getElementsByName('chaining');
    // const goalContainer ... (Ya no necesitamos ocultarlo)
    const memoryLabel = document.getElementById('memory-label');
    const memoryInput = document.getElementById('memory');
    const goalInput = document.getElementById('goal');

    // Botones de I/O
    const btnExport = document.getElementById('btn-export');
    const btnImport = document.getElementById('btn-import');
    const fileInput = document.getElementById('import-file');

    // 1. Lógica de Cambio de Modo (SOLO TEXTOS)
    function toggleMode() {
        let mode = 'forward';
        for (const r of radios) { if (r.checked) mode = r.value; }

        if (mode === 'backward') {
            // Modo Atrás
            memoryLabel.innerText = "Hechos conocidos (Ej: A, B, F):";
            if(!memoryInput.value) memoryInput.placeholder = "A, B, F";
        } else {
            // Modo Adelante
            memoryLabel.innerText = "Elementos conocidos (Ej: A0, F1):";
            if(!memoryInput.value) memoryInput.placeholder = "A0, F1";
        }
    }

    radios.forEach(r => r.addEventListener('change', toggleMode));
    toggleMode(); 

    // 2. Función Helper para Crear Reglas
    function createRuleElement(value = '') {
        const count = rulesContainer.children.length; 
        const div = document.createElement('div');
        div.className = 'rule-item';
        
        const label = document.createElement('span');
        label.innerText = `R${count + 1}: `; 
        label.style.fontWeight = 'bold';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'rules[]';
        input.placeholder = 'Ej: A + B -> C';
        input.value = value;
        input.required = true;

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.innerText = 'X';
        delBtn.className = 'btn-secondary';
        delBtn.style.backgroundColor = '#e74c3c';
        delBtn.onclick = function() { rulesContainer.removeChild(div); updateLabels(); };

        div.appendChild(label);
        div.appendChild(input);
        div.appendChild(delBtn);
        rulesContainer.appendChild(div);
    }

    function updateLabels() {
        const items = rulesContainer.querySelectorAll('.rule-item');
        items.forEach((item, index) => {
            item.querySelector('span').innerText = `R${index + 1}: `;
        });
    }

    addRuleBtn.addEventListener('click', () => createRuleElement());

    // 3. EXPORTAR DATOS
    btnExport.addEventListener('click', () => {
        const ruleInputs = document.querySelectorAll('input[name="rules[]"]');
        const rulesList = Array.from(ruleInputs).map(input => input.value);
        const optionsList = [];
        document.querySelectorAll('input[name="options[]"]:checked').forEach(cb => optionsList.push(cb.value));

        let chainingMode = 'forward';
        document.getElementsByName('chaining').forEach(r => { if(r.checked) chainingMode = r.value; });

        const data = {
            chaining: chainingMode,
            options: optionsList,
            memory: memoryInput.value,
            goal: goalInput.value,
            rules: rulesList
        };

        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(data, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "sistema_experto_config.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });

    // 4. IMPORTAR DATOS
    btnImport.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = JSON.parse(e.target.result);
                
                if (data.chaining) {
                    document.querySelector(`input[name="chaining"][value="${data.chaining}"]`).checked = true;
                    toggleMode();
                }

                document.querySelectorAll('input[name="options[]"]').forEach(cb => cb.checked = false);
                if (data.options && Array.isArray(data.options)) {
                    data.options.forEach(opt => {
                        const cb = document.querySelector(`input[name="options[]"][value="${opt}"]`);
                        if (cb) cb.checked = true;
                    });
                }

                if (data.memory !== undefined) memoryInput.value = data.memory;
                if (data.goal !== undefined) goalInput.value = data.goal;

                if (data.rules && Array.isArray(data.rules)) {
                    rulesContainer.innerHTML = ''; 
                    data.rules.forEach(ruleText => createRuleElement(ruleText));
                }
                alert('Configuración importada con éxito.');

            } catch (err) { alert('Error JSON: ' + err); }
            fileInput.value = '';
        };
        reader.readAsText(file);
    });

        // ... (resto del código anterior) ...

    // 5. VALIDACIÓN DEL FORMULARIO (NUEVO)
    const form = document.querySelector('form');
    form.addEventListener('submit', (e) => {
        const checkboxes = document.querySelectorAll('input[name="options[]"]:checked');
        
        if (checkboxes.length === 0) {
            e.preventDefault(); // Detener el envío
            alert('⚠️ Debes seleccionar al menos una estrategia de resolución de conflictos (ej: Orden Textual).');
            
            // Efecto visual para destacar el error
            const group = document.querySelector('.checkbox-group');
            group.style.border = "2px solid #e74c3c";
            group.style.padding = "10px";
            group.style.borderRadius = "5px";
            
            setTimeout(() => {
                group.style.border = "none";
                group.style.padding = "0"; // O el padding que tuviera en CSS
            }, 3000);
        }
    });
});