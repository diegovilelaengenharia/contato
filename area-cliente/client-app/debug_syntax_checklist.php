<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Syntax Checker: Checklist (Client)</h1>";

// We use output buffering to prevent the included file from messing up the screen if it runs partially
ob_start();

try {
    // Attempt to load the suspicious file
    // Syntax errors in the included file will throw a ParseError here
    include 'documentos_iniciais.php';
    
    ob_end_clean();
    echo "<h2 style='color:green'>✅ No Syntax Errors found!</h2>";
    echo "<p>The file parsed correctly. The crash must be runtime (logic).</p>";
    
} catch (ParseError $e) {
    ob_end_clean();
    echo "<h2 style='color:red'>❌ SINTAXE INVÁLIDA (PARSE ERROR)</h2>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    
} catch (Throwable $e) {
    ob_end_clean();
    echo "<h2 style='color:orange'>⚠️ Runtime Error</h2>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
}
?>
