<?php
// FunÃ§Ã£o para carregar variÃ¡veis do .env
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Arquivo .env nÃ£o encontrado");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Carrega as variÃ¡veis de ambiente
loadEnv(__DIR__ . '/.env');

$GOOGLE_API_KEY = getenv('GOOGLE_API_KEY');
$GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1 = getenv('GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1');
$GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2 = getenv('GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2');
$GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar = getenv('GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar');
// Inicia a sessÃ£o
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Scripts existentes -->
    <script src="https://kit.fontawesome.com/7e7eda2fa6.js" crossorigin="anonymous"></script>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financeiro</title>
    <script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    html {
        overflow-x: hidden;
    }
    
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
        color: #e2e8f0;
        overflow-x: hidden;
        min-height: 100vh;
        width: 100%;
    }
    
    /* Background animado */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 200%;
        height: 200%;
        background-image: repeating-linear-gradient(
            45deg,
            transparent,
            transparent 10px,
            rgba(255, 255, 255, 0.01) 10px,
            rgba(255, 255, 255, 0.01) 20px
        );
        opacity: 0.3;
        z-index: -1;
        animation: moveBackground 60s linear infinite;
    }
    
    @keyframes moveBackground {
        0% { transform: translate(0, 0); }
        100% { transform: translate(-50%, -50%); }
    }
    
    .dashboard-container {
        display: flex;
        min-height: 100vh;
        width: 100%;
        overflow-x: hidden;
    }
    
    /* Sidebar com tema escuro e dourado */
    .sidebar {
        width: 260px;
        background: rgba(15, 15, 35, 0.95);
        backdrop-filter: blur(20px);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        padding: 0;
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        box-shadow: 8px 0 32px rgba(0, 0, 0, 0.4);
        transition: transform 0.3s ease;
        z-index: 1000;
    }
    
    .sidebar-header {
        padding: 32px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
        background: rgba(255, 215, 0, 0.05);
    }
    
    .sidebar-header h1 {
        font-size: 1.4em;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    
    .sidebar-header p {
        font-size: 0.8em;
        color: #94a3b8;
        font-weight: 500;
    }
    
    .sidebar-menu {
        list-style: none;
        padding: 24px 0;
    }
    
    .sidebar-menu li {
        margin: 4px 0;
    }
    
    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 3px solid transparent;
        font-weight: 500;
        font-size: 14px;
        position: relative;
        overflow: hidden;
    }
    
    .sidebar-menu a::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 165, 0, 0.05));
        opacity: 0;
        transition: opacity 0.3s;
        z-index: -1;
    }
    
    .sidebar-menu a:hover {
        color: #ffffff;
        border-left-color: #FFD700;
        transform: translateX(4px);
    }
    
    .sidebar-menu a:hover::before {
        opacity: 1;
    }
    
    .sidebar-menu a.active {
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 165, 0, 0.1));
        border-left-color: #FFD700;
        color: #FFD700;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
    }
    
    .sidebar-menu a .icon {
        font-size: 1.2em;
        width: 20px;
        text-align: center;
    }
    
    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: 260px;
        padding: 24px;
        background: transparent;
        min-height: 100vh;
        width: calc(100vw - 260px);
        overflow-x: hidden;
    }
    
    .content-wrapper {
        max-width: 100%;
        margin: 0 auto;
        width: 100%;
        overflow-x: hidden;
    }
    
    .page-section {
        display: none;
        animation: fadeInUp 0.6s ease-out;
    }
    
    .page-section.active {
        display: block;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .page-header {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 20px 24px;
        border-radius: 20px;
        margin-bottom: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        position: relative;
        overflow: hidden;
        width: 100%;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #FFD700, #FFA500);
    }
    
.page-header h2 {
    color: #ffffff; /* Branco */
    font-size: 1.8em;
    margin-bottom: 6px;
    font-weight: 700;
    letter-spacing: -0.5px;
}
    
    .page-header p {
        color: #94a3b8;
        font-size: 0.95em;
        font-weight: 500;
    }
    
    .controls {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 20px;
        border-radius: 20px;
        margin-bottom: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    
    .date-toggle {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        justify-content: center;
        flex-wrap: wrap;
        background: rgba(255, 255, 255, 0.05);
        padding: 8px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .date-toggle label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        color: #94a3b8;
        cursor: pointer;
        padding: 12px 24px;
        background: transparent;
        border-radius: 10px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
        font-size: 14px;
        position: relative;
        overflow: hidden;
    }
    
    .date-toggle label::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        opacity: 0;
        transition: opacity 0.3s;
        z-index: -1;
    }
    
    .date-toggle label:hover {
        color: #ffffff;
        transform: translateY(-2px);
    }
    
    .date-toggle label:hover::before {
        opacity: 0.1;
    }
    
    .date-toggle input[type="radio"]:checked + label {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        color: #0f0f23;
        font-weight: 600;
        border-color: transparent;
        box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
    }
    
    .date-range-section {
        background: rgba(255, 255, 255, 0.03);
        padding: 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    
    .date-range-section h3 {
        color: #FFD700;
        margin-bottom: 18px;
        font-size: 1em;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
    }
    
    .date-range-inputs {
        display: grid;
        grid-template-columns: 1fr auto 1fr auto;
        gap: 12px;
        align-items: center;
        width: 100%;
    }
    
    .date-input-group {
        display: flex;
        flex-direction: column;
    }
    
    .date-input-group label {
        font-weight: 600;
        margin-bottom: 10px;
        color: #94a3b8;
        font-size: 0.85em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .date-input-group input[type="month"],
    .date-input-group select {
        padding: 10px 14px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        font-size: 13px;
        background: rgba(15, 15, 35, 0.6) !important;
        backdrop-filter: blur(10px);
        color: #e2e8f0 !important;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        width: 100%;
        max-width: 100%;
    }
    
    .date-input-group input[type="month"]:focus,
    .date-input-group select:focus {
        outline: none;
        border-color: rgba(255, 215, 0, 0.5);
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
        background: rgba(15, 15, 35, 0.8) !important;
    }
    
    /* CorreÃ§Ã£o para calendÃ¡rio do input month */
    .date-input-group input[type="month"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }
    
    .date-range-divider {
        color: #FFD700;
        font-weight: bold;
        font-size: 1.3em;
        padding-top: 20px;
    }
    
    .clear-date-btn,
    button[onclick*="load"],
    button[onclick*="reset"] {
        padding: 10px 20px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        align-self: end;
        margin-bottom: 2px;
        font-size: 13px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        font-family: 'Inter', sans-serif;
        white-space: nowrap;
    }
    
    button[onclick*="load"] {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        color: #0f0f23;
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
    }
    
    .clear-date-btn:hover,
    button[onclick*="load"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }
    
    button[onclick*="reset"] {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    
    .filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        width: 100%;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-group label {
        font-weight: 600;
        margin-bottom: 10px;
        color: #94a3b8;
        font-size: 0.85em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        overflow-wrap: break-word;
        word-wrap: break-word;
        hyphens: auto;
    }
    
    .filter-group select {
        padding: 10px 14px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        font-size: 13px;
        background: rgba(15, 15, 35, 0.6) !important;
        backdrop-filter: blur(10px);
        color: #e2e8f0 !important;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        width: 100%;
        max-width: 100%;
    }
    
    .filter-group select option {
        background: #1a1a2e !important;
        color: #e2e8f0 !important;
        padding: 10px;
    }
    
    .filter-group select:focus {
        outline: none;
        border-color: rgba(255, 215, 0, 0.5);
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
        background: rgba(15, 15, 35, 0.8) !important;
    }
    
    .kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
        width: 100%;
    }
    
    .kpi-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 18px 20px;
        border-radius: 16px;
        color: white;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        min-height: 110px;
    }
    
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #FFD700, #FFA500);
    }
    
    .kpi-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        border-color: rgba(255, 215, 0, 0.3);
    }
    
    .kpi-card.negative::before {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }
    
    .kpi-card.positive::before {
        background: linear-gradient(90deg, #22c55e, #16a34a);
    }
    
    .kpi-card h3 {
        font-size: .65em;
        margin-bottom: 8px;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        line-height: 1.3;
    }
    
    .kpi-card .value {
        font-size: 1.6em;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: -0.5px;
        word-break: break-word;
        line-height: 1.2;
        overflow-wrap: break-word;
        word-wrap: break-word;
        hyphens: auto;
    }
    
    .chart {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 20px;
        border-radius: 20px;
        margin-bottom: 28px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
    }
    
    .chart::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #FFD700, #FFA500);
    }
    
    .chart:hover {
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        border-color: rgba(255, 215, 0, 0.2);
    }
    
    .chart h2 {
    color: #ffffff; /* Branco */
    margin-bottom: 24px;
    font-size: 1.4em;
    font-weight: 700;
    letter-spacing: -0.5px;
}
    
    .chart > div[style*="overflow-x: auto"] {
        background: rgba(15, 15, 35, 0.4);
        padding: 8px;
        border-radius: 12px;
        max-width: 100%;
        overflow-x: auto;
    }
    
    .charts-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
        width: 100%;
    }
    
    .loading {
        text-align: center;
        padding: 60px;
        font-size: 1.2em;
        color: #FFD700;
        font-weight: 600;
    }
    
    .error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
        backdrop-filter: blur(10px);
        font-weight: 500;
    }
    
    .placeholder-content {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 80px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .placeholder-content h3 {
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 2em;
        margin-bottom: 16px;
        font-weight: 700;
    }
    
    .placeholder-content p {
        color: #94a3b8;
        font-size: 1.1em;
        font-weight: 500;
    }
    
    /* Sliders */
    #slidersContainer {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 16px;
        padding: 16px;
        width: 100%;
    }
    
    .slider-control {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s;
    }
    
    .slider-control:hover {
        border-color: rgba(255, 215, 0, 0.3);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    .slider-control h4 {
        margin: 0 0 16px 0;
        color: #e2e8f0;
        font-size: 0.95em;
        font-weight: 600;
    }
    
    .slider-wrapper {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .slider-input {
        flex: 1;
        height: 6px;
        border-radius: 5px;
        background: rgba(255, 255, 255, 0.1);
        outline: none;
        -webkit-appearance: none;
    }
    
    .slider-input::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(255, 215, 0, 0.4);
        transition: all 0.2s;
    }
    
    .slider-input::-webkit-slider-thumb:hover {
        transform: scale(1.2);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.6);
    }
    
    .slider-input::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 10px rgba(255, 215, 0, 0.4);
        transition: all 0.2s;
    }
    
    .slider-value {
        min-width: 80px;
        text-align: center;
        font-weight: 700;
        font-size: 1em;
        color: #FFD700;
        background: rgba(255, 215, 0, 0.1);
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid rgba(255, 215, 0, 0.3);
    }
    
    /* Tabelas */
    table {
        color: #e2e8f0;
        background: transparent !important;
        font-size: 0.85em;
        width: auto;
        min-width: 100%;
        display: table;
        table-layout: auto;
    }
    
    table thead {
        background: rgba(15, 15, 35, 0.8) !important;
    }
    
    table th {
        background: rgba(15, 15, 35, 0.8) !important;
        color: #FFD700 !important;
        font-weight: 600;
        padding: 10px 12px;
        text-align: left;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        font-size: 0.8em;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }
    
    table td {
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        transition: background 0.3s;
        color: #cbd5e1 !important;
        background: rgba(255, 255, 255, 0.02) !important;
        font-size: 0.85em;
        white-space: nowrap;
        overflow-wrap: break-word;
        word-wrap: break-word;
        hyphens: auto;
    }
    
    table tbody tr {
        background: rgba(255, 255, 255, 0.02) !important;
    }
    
    table tbody tr:hover {
        background: rgba(255, 255, 255, 0.05) !important;
    }
    
    table tbody tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.03) !important;
    }
    
    table tbody tr:nth-child(even):hover {
        background: rgba(255, 255, 255, 0.06) !important;
    }
    
    /* Ajustar cores especÃ­ficas das tabelas */
    table td[style*="background: #f5f5f5"],
    table td[style*="background: #ffffff"],
    table td[style*="background: white"],
    table tr[style*="background: #f5f5f5"],
    table tr[style*="background: #ffffff"],
    table tr[style*="background: white"] {
        background: rgba(255, 255, 255, 0.03) !important;
    }
    
    table tr[style*="background: #e3f2fd"],
    table tr[style*="background: #e8f5e9"],
    table tr[style*="background: #ffebee"] {
        background: rgba(255, 255, 255, 0.05) !important;
    }
    
    table td[style*="background: #e3f2fd"],
    table td[style*="background: #e8f5e9"],
    table td[style*="background: #ffebee"],
    table td[style*="background: #bbdefb"],
    table td[style*="background: #fafafa"] {
        background: rgba(255, 255, 255, 0.05) !important;
    }
    
    table tr[style*="background: #c8e6c9"],
    table tr[style*="background: #ffcdd2"] {
        background: rgba(255, 255, 255, 0.08) !important;
    }
    
    /* ForÃ§ar todas as cores escuras para claras */
    table td[style*="color: #333"],
    table td[style*="color: #666"],
    table td[style*="color: #555"],
    table td[style*="color: #000"],
    table td[style*="color: black"],
    table td[style*="color: #0f0f23"] {
        color: #cbd5e1 !important;
    }
    
    /* Cores de texto nas tabelas - valores positivos/negativos */
    table td[style*="color: #2e7d32"],
    table td[style*="color: #1b5e20"] {
        color: #4ade80 !important;
    }
    
    table td[style*="color: #c62828"],
    table td[style*="color: #d32f2f"],
    table td[style*="color: #b71c1c"] {
        color: #f87171 !important;
    }
    
    table td[style*="color: #1565c0"],
    table td[style*="color: #0d47a1"] {
        color: #60a5fa !important;
    }
    
    /* Garantir que todo texto em tabelas seja claro por padrÃ£o */
    table *,
    table span,
    table div,
    table p {
        color: #cbd5e1 !important;
    }
    
    /* ExceÃ§Ãµes para cores especÃ­ficas jÃ¡ claras */
    table *[style*="color: #2e7d32"],
    table *[style*="color: #1b5e20"],
    table *[style*="color: #4ade80"] {
        color: #4ade80 !important;
    }
    
    table *[style*="color: #c62828"],
    table *[style*="color: #d32f2f"],
    table *[style*="color: #b71c1c"],
    table *[style*="color: #f87171"] {
        color: #f87171 !important;
    }
    
    table *[style*="color: #1565c0"],
    table *[style*="color: #0d47a1"],
    table *[style*="color: #60a5fa"] {
        color: #60a5fa !important;
    }
    
    /* Ajustar header das tabelas quando sticky */
    table th[style*="position: sticky"] {
        background: rgba(15, 15, 35, 0.95) !important;
        backdrop-filter: blur(10px);
    }
    
    table td[style*="position: sticky"] {
        background: rgba(15, 15, 35, 0.95) !important;
        backdrop-filter: blur(10px);
    }
    
    /* ===== CORREÃ‡ÃƒO DRE - Fundo escuro ===== */
#tableDRE { background: transparent !important; }
#tableDRE td { color: #e2e8f0 !important; }
#tableDRE tr { background: rgba(15, 15, 35, 0.6) !important; }

#tableDRE tr[style*="background: e8f5e9"],
#tableDRE tr[style*="background:e8f5e9"] { background: rgba(34, 197, 94, 0.12) !important; }

#tableDRE tr[style*="background: ffebee"],
#tableDRE tr[style*="background:ffebee"] { background: rgba(248, 113, 113, 0.12) !important; }

#tableDRE tr[style*="background: e3f2fd"],
#tableDRE tr[style*="background:e3f2fd"] { background: rgba(96, 165, 250, 0.12) !important; }

#tableDRE tr[style*="background: fff3e0"],
#tableDRE tr[style*="background:fff3e0"] { background: rgba(251, 191, 36, 0.08) !important; }

#tableDRE tr[style*="background: f5f5f5"],
#tableDRE tr[style*="background:f5f5f5"] { background: rgba(255, 255, 255, 0.05) !important; }

#tableDRE tr[style*="background: c8e6c9"],
#tableDRE tr[style*="background:c8e6c9"] { background: rgba(34, 197, 94, 0.20) !important; }

#tableDRE tr[style*="background: fce4ec"],
#tableDRE tr[style*="background:fce4ec"] { background: rgba(248, 113, 113, 0.10) !important; }

#tableDRE tr[style*="background: f3e5f5"],
#tableDRE tr[style*="background:f3e5f5"] { background: rgba(167, 139, 250, 0.10) !important; }

#tableDRE tr[style*="background: d1c4e9"],
#tableDRE tr[style*="background:d1c4e9"] { background: rgba(139, 92, 246, 0.15) !important; }

#tableDRE tr[style*="background: e8eaf6"],
#tableDRE tr[style*="background:e8eaf6"] { background: rgba(99, 102, 241, 0.10) !important; }

#tableDRE td[style*="position: sticky"],
#tableDRE th[style*="position: sticky"] { background: rgba(15, 15, 35, 0.97) !important; }


    /* Ajustar fundo dos grÃ¡ficos Plotly */
    .js-plotly-plot {
        background: transparent !important;
    }
    
    .js-plotly-plot .plotly .main-svg {
        background: transparent !important;
    }
    
    .js-plotly-plot .bg {
        fill: transparent !important;
    }
    
    /* Ajustar cores dos grÃ¡ficos para tema escuro */
    .js-plotly-plot .gridlayer .x,
    .js-plotly-plot .gridlayer .y {
        stroke: rgba(255, 255, 255, 0.1) !important;
    }
    
    .js-plotly-plot .zerolinelayer .xzl,
    .js-plotly-plot .zerolinelayer .yzl {
        stroke: rgba(255, 255, 255, 0.2) !important;
    }
    
    /* Texto dos grÃ¡ficos */
    .js-plotly-plot text {
        fill: #ffffff !important;
    }
    
    .js-plotly-plot .xtick text,
    .js-plotly-plot .ytick text {
        fill: #cbd5e1 !important;
    }
    
    /* Selects mÃºltiplos com melhor aparÃªncia */
.filter-group select[multiple] {
    padding: 10px 14px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    font-size: 13px;
    background: rgba(15, 15, 35, 0.6) !important;
    backdrop-filter: blur(10px);
    color: #e2e8f0 !important;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    width: 100%;
    max-width: 100%;
    min-height: 100px;
}

.filter-group select[multiple] option {
    background: #1a1a2e !important;
    color: #e2e8f0 !important;
    padding: 8px 12px;
    margin: 2px 0;
    border-radius: 4px;
}

.filter-group select[multiple] option:checked {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.3), rgba(255, 165, 0, 0.2)) !important;
    color: #FFD700 !important;
    font-weight: 600;
}

.filter-group select[multiple]:focus {
    outline: none;
    border-color: rgba(255, 215, 0, 0.5);
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
    background: rgba(15, 15, 35, 0.8) !important;
}

    
    /* Mobile Toggle */
    .mobile-toggle {
        display: none;
        position: fixed;
        top: 24px;
        left: 24px;
        z-index: 1001;
        background: linear-gradient(135deg, #FFD700, #FFA500);
        color: #0f0f23;
        border: none;
        padding: 14px 18px;
        border-radius: 12px;
        cursor: pointer;
        font-size: 1.2em;
        box-shadow: 0 8px 32px rgba(255, 215, 0, 0.4);
        transition: all 0.3s;
    }
    
    .mobile-toggle:hover {
        transform: scale(1.05);
        box-shadow: 0 12px 40px rgba(255, 215, 0, 0.6);
    }
    
    /* Ajustes responsivos */
    @media (max-width: 1400px) {
        .main-content {
            padding: 20px;
        }
        
        .kpi-card .value {
            font-size: 1.4em;
        }
        
        table {
            font-size: 0.8em;
        }
        
        table th {
            font-size: 0.75em;
            padding: 8px 10px;
        }
        
        table td {
            font-size: 0.8em;
            padding: 7px 10px;
        }
    }
    
    @media (max-width: 1200px) {
        .charts-row {
            grid-template-columns: 1fr;
        }
        
        .kpi-card .value {
            font-size: 1.3em;
        }
        
        .kpi-card h3 {
            font-size: 0.65em;
        }
        
        .filters {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        
        #slidersContainer {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
            padding: 80px 15px 15px;
            width: 100vw;
        }
        
        .mobile-toggle {
            display: block;
        }
        
        .date-range-inputs {
            grid-template-columns: 1fr;
        }
        
        .date-range-divider {
            display: none;
        }
        
        .filters {
            grid-template-columns: 1fr;
        }
        
        .kpis {
            grid-template-columns: 1fr;
        }
        
        .page-header h2 {
            font-size: 1.5em;
        }
        
        .kpi-card .value {
            font-size: 1.4em;
        }
    }
    
    /* Logo na sidebar */
.logo-image {
    width: 100px;
    height: auto;
    margin-bottom: 12px;
    display: block;
    margin-left: auto;
    margin-right: auto;
    filter: brightness(1.2);
    transition: all 0.3s ease;
}

.logo-image:hover {
    transform: scale(1.05);
    filter: brightness(1.4);
}

.sidebar-header {
    padding: 32px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
    background: rgba(255, 215, 0, 0.05);
}

.sidebar-header p {
    font-size: 0.9em;
    color: #94a3b8;
    font-weight: 500;
    margin-top: 8px;
}

/* InstruÃ§Ã£o de filtros */
.filter-instruction {
    background: rgba(255, 215, 0, 0.1);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 12px;
    padding: 12px 16px;
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.85em;
    color: #e2e8f0;
    animation: fadeIn 0.5s ease-in-out;
}

.instruction-icon {
    font-size: 1.3em;
    flex-shrink: 0;
}

.instruction-text {
    line-height: 1.5;
    color: #cbd5e1;
}

.filter-instruction kbd {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 215, 0, 0.4);
    border-radius: 4px;
    padding: 3px 8px;
    font-family: 'Inter', monospace;
    font-size: 0.9em;
    font-weight: 600;
    color: #FFD700;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    display: inline-block;
    margin: 0 2px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Canvas dos grÃ¡ficos Chart.js */
canvas {
    max-height: 400px;
}

.chart canvas {
    background: transparent !important;
}

/* Menu Superior - Igual ao Soc.ia */
/* Menu Superior - Centralizado */
/* Menu Superior - Logo Ã  esquerda, botÃµes centralizados */
/* Menu Superior - Com altura maior */
.topbar {
    background: rgba(15, 15, 35, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 20px 32px;  /* AUMENTADO: 16px â†’ 20px */
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 2000;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    min-height: 70px;  /* ADICIONADO: altura mÃ­nima */
}

.logo {
    font-size: 18px;  /* AUMENTADO: 16px â†’ 18px */
    font-weight: 700;
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.5px;
    white-space: nowrap;
}

.menu-buttons {
    display: flex;
    gap: 8px;
    background: rgba(255, 255, 255, 0.05);
    padding: 8px;  /* AUMENTADO: 6px â†’ 8px */
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.menu-button {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 15px;  /* AUMENTADO: 14px â†’ 15px */
    font-weight: 500;
    padding: 12px 24px;  /* AUMENTADO: 10px 20px â†’ 12px 24px */
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.menu-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    opacity: 0;
    transition: opacity 0.3s;
    z-index: -1;
}

.menu-button:hover {
    color: #ffffff;
    transform: translateY(-1px);
}

.menu-button:hover::before {
    opacity: 0.1;
}

.menu-button.active {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #0f0f23;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.menu-button img {
    height: 18px;  /* AUMENTADO: 16px â†’ 18px */
    margin-right: 4px;
}

/* Ajustar body e sidebar para compensar a altura maior */
body {
    padding-top: 90px;  /* AUMENTADO: 80px â†’ 90px */
}

.dashboard-container {
    margin-top: 0;
}

.sidebar {
    top: 90px;  /* AUMENTADO: 80px â†’ 90px */
    height: calc(100vh - 90px);  /* AJUSTADO */
}

.mobile-toggle {
    top: 100px;  /* AUMENTADO: 90px â†’ 100px */
}

.pdf-loading-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(15, 15, 35, 0.95);
    backdrop-filter: blur(10px);
    z-index: 9999;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #FFD700;
}
.pdf-loading-overlay h2 { font-size: 1.8em; margin-bottom: 20px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.pdf-loading-spinner { border: 4px solid rgba(255, 215, 0, 0.3); border-top: 4px solid #FFD700; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }



</style>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
        <!-- Menu Superior -->
<div class="topbar">
        <div class="logo">Mais Dois Financeiro - Soc.ia</div>
        
        <div class="menu-buttons">
            <a href="Casa_Cuidar_1.php" class="menu-button">
                <img src="Socia2.png" alt="Soc.ia" style="height: 16px; margin-right: 4px;">
                Soc.ia
            </a>
            <button class="menu-button active">
                <i class="fas fa-chart-bar"></i>
                Indicadores Financeiros
            </button>
        </div>
        
        <!-- Div vazia para balancear o espaÃ§o -->
        <div style="width: 200px;"></div>
    </div>

    <button class="mobile-toggle" onclick="toggleSidebar()">â˜°</button>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                 <img src="formas.png" alt="Logo" class="logo-image">
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="#visao-geral" class="menu-link active" onclick="showPage('visao-geral', event)">
                        <span class="icon">ðŸ“Š</span>
                        <span>VisÃ£o Geral</span>
                    </a>
                </li>
                <li>
                    <a href="#receita" class="menu-link" onclick="showPage('receita', event)">
                        <span class="icon">ðŸ’°</span>
                        <span>Contas a Receber</span>
                    </a>
                </li>
                <li>
                    <a href="#despesa" class="menu-link" onclick="showPage('despesa', event)">
                        <span class="icon">ðŸ’¸</span>
                        <span>Contas a Pagar</span>
                    </a>
                </li>
                <li>
                    <a href="#fluxo-caixa" class="menu-link" onclick="showPage('fluxo-caixa', event)">
                        <span class="icon">ðŸ’µ</span>
                        <span>Fluxo de Caixa</span>
                    </a>
                </li>
                <li>
                    <a href="#centro-custo" class="menu-link" onclick="showPage('centro-custo', event)">
                        <span class="icon">ðŸ¢</span>
                        <span>Centro de Custo</span>
                    </a>
                </li>
                <li>
                    <a href="#indicadores" class="menu-link" onclick="showPage('indicadores', event)">
                        <span class="icon">ðŸ“ˆ</span>
                        <span>Indicadores</span>
                    </a>
                </li>
                
                <li>
    <a href="#rentabilidade" class="menu-link" onclick="showPage('rentabilidade', event)">
        <span class="icon">ðŸ’Ž</span>
        <span>AnÃ¡lise de Rentabilidade</span>
    </a>
</li>

                <li>
                    <a href="#dre" class="menu-link" onclick="showPage('dre', event)">
                        <span class="icon">ðŸ“‹</span>
                        <span>DRE</span>
                    </a>
                </li>
                <!--// <li>
                   // <a href="#simulador" class="menu-link" onclick="showPage('simulador', event)">
                   //     <span class="icon">ðŸŽ¯</span>
                       // <span>Simulador DRE</span>
                   // </a>
             //  </li> -->
                <li>
  <a href="#simulador-fluxo" class="menu-link" onclick="showPage('simulador-fluxo', event)">
    <span class="icon">ðŸ’°</span>
    <span>Simulador Fluxo de Caixa</span>
  </a>
</li>

<li>
  <a href="#exportar-png" class="menu-link" onclick="exportCurrentPageToPNG(event)">
    <span class="icon">ðŸ–¼ï¸</span>
    <span>Exportar como Imagem</span>
  </a>
</li>


            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="content-wrapper">
                <!-- VisÃ£o Geral -->
                <section id="visao-geral" class="page-section active">
                    <div class="page-header">
                        <h2>VisÃ£o Geral</h2>
                        <p>Panorama completo das suas finanÃ§as</p>
                    </div>
                    
                    <div class="controls">
                        <div class="date-toggle">
  <!-- NOVO: Adicione esta opÃ§Ã£o PRIMEIRO -->
  <input type="radio" name="dateType" id="realizadoProjetado" value="realizadoProjetado" checked hidden>
  <label for="realizadoProjetado">ðŸ“… Realizado + Projetado</label>
  
  <!-- OpÃ§Ãµes existentes - remova o 'checked' do dueDate -->
  <input type="radio" name="dateType" id="dueDate" value="dueDate" hidden>
  <label for="dueDate">ðŸ“… Data de Vencimento</label>
  
  <input type="radio" name="dateType" id="compDate" value="financialEvent.competenceDate" hidden>
  <label for="compDate">ðŸ“… Data de CompetÃªncia</label>
</div>
                        
                        <div class="date-range-section">
                            <h3>ðŸ“† PerÃ­odo</h3>
                            <div class="date-range-inputs">
                                <div class="date-input-group">
                                    <label for="startMonth">Data Inicial:</label>
                                    <input type="month" id="startMonth" name="startMonth">
                                </div>
                                
                                <div class="date-range-divider">â†’</div>
                                
                                <div class="date-input-group">
                                    <label for="endMonth">Data Final:</label>
                                    <input type="month" id="endMonth" name="endMonth">
                                </div>
                                
                                <button class="clear-date-btn" onclick="clearDateRange()">ðŸ—‘ï¸ Limpar</button>
                            </div>
                        </div>
                        
                        <div class="filters">
                            <div class="filter-group">
                                <label>Status:</label>
                                <select id="statusFilter" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Centro de Custo:</label>
                                <select id="costCenterFilter" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Categoria:</label>
                                <select id="categoryFilter" multiple>
                                    <option value="">Todas</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Cliente/Fornecedor:</label>
                                <select id="negotiatorFilter" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                        </div>
                        <!-- Adicione esta instruÃ§Ã£o aqui -->
<div class="filter-instruction">
    <span class="instruction-icon">ðŸ’¡</span>
    <span class="instruction-text">Use <kbd>Ctrl</kbd> + <kbd>Clique</kbd> (ou <kbd>Cmd</kbd> no Mac) para selecionar mÃºltiplas opÃ§Ãµes nos filtros</span>
</div>
                    </div>
                    
                    
                    <div id="loading" class="loading">
                        <p>â³ Carregando dados...</p>
                    </div>
                    
                    <div id="error" class="error" style="display:none;"></div>
                    
                    <div id="dashboard" style="display:none;">
                        <div class="kpis">
                            <div class="kpi-card positive">
                                <h3>ðŸ’° Receber Total</h3>
                                <div class="value" id="receitaTotal">R$ 0,00</div>
                            </div>
                            
                            <div class="kpi-card negative">
                                <h3>ðŸ’¸ Pagar Total</h3>
                                <div class="value" id="despesaTotal">R$ 0,00</div>
                            </div>
                            
                            <div class="kpi-card" id="resultadoCard">
                                <h3>ðŸ“ˆ Total do PerÃ­odo</h3>
                                <div class="value" id="resultadoLiquido">R$ 0,00</div>
                            </div>
                            
                            <div class="kpi-card">
                                <h3>ðŸ“Š Taxa da Margem</h3>
                                <div class="value" id="taxaMargem">0%</div>
                            </div>
                        </div>
                        
                        <div class="chart">
                            <h2>Receber e a Pagar</h2>
                            <div id="chartReceitasDespesas"></div>
                        </div>
                        
                        <div class="chart">
                            <h2>Total do PerÃ­odo</h2>
                            <div id="chartValorLiquido"></div>
                        </div>
                    </div>
                </section>
                
                <!-- Receita -->
                <section id="receita" class="page-section">
                    <div class="page-header">
                        <h2>Contas a Receber</h2>
                        <p>AnÃ¡lise detalhada a Receber</p>
                    </div>
                    
                    <div class="controls">
                        <div class="date-toggle">
  <input type="radio" name="dateTypeReceita" id="realizadoProjetadoReceita" value="realizadoProjetado" checked hidden>
  <label for="realizadoProjetadoReceita">ðŸ“… Realizado + Projetado</label>
  
  <input type="radio" name="dateTypeReceita" id="dueDateReceita" value="dueDate" hidden>
  <label for="dueDateReceita">ðŸ“… Data de Vencimento</label>
  
  <input type="radio" name="dateTypeReceita" id="compDateReceita" value="financialEvent.competenceDate" hidden>
  <label for="compDateReceita">ðŸ“… Data de CompetÃªncia</label>
</div>

                        
                        <div class="date-range-section">
                            <h3>ðŸ“† PerÃ­odo</h3>
                            <div class="date-range-inputs">
                                <div class="date-input-group">
                                    <label for="startMonthReceita">Data Inicial:</label>
                                    <input type="month" id="startMonthReceita" name="startMonthReceita">
                                </div>
                                
                                <div class="date-range-divider">â†’</div>
                                
                                <div class="date-input-group">
                                    <label for="endMonthReceita">Data Final:</label>
                                    <input type="month" id="endMonthReceita" name="endMonthReceita">
                                </div>
                                
                                <button class="clear-date-btn" onclick="clearDateRangeReceita()">ðŸ—‘ï¸ Limpar</button>
                            </div>
                        </div>
                        
                        <div class="filters">
                            <div class="filter-group">
                                <label>Status:</label>
                                <select id="statusFilterReceita" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Centro de Custo:</label>
                                <select id="costCenterFilterReceita" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Categoria:</label>
                                <select id="categoryFilterReceita" multiple>
                                    <option value="">Todas</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Cliente:</label>
                                <select id="negotiatorFilterReceita" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpis">
                        <div class="kpi-card positive">
                            <h3>ðŸ’° A Receber Total</h3>
                            <div class="value" id="receitaTotalPage">R$ 0,00</div>
                        </div>
                        
                        <div class="kpi-card">
                            <h3>ðŸŽ¯ Ticket MÃ©dio</h3>
                            <div class="value" id="ticketMedioReceita">R$ 0,00</div>
                        </div>
                        
                        <div class="kpi-card">
                            <h3>ðŸ‘¥ MÃ©dia de Clientes</h3>
                            <div class="value" id="mediaClientesReceita">0</div>
                        </div>
                    </div>
                    
                    <div class="charts-row">
                        <div class="chart">
                            <h2>Por Categoria</h2>
                            <div id="chartReceitaCategoria"></div>
                        </div>
                        
                        <div class="chart">
                            <h2>MÃ©dia por Categoria</h2>
                            <div id="chartMediaCategoria"></div>
                        </div>
                        
                        <div class="chart">
                            <h2>Por Centro de Custo</h2>
                            <div id="chartReceitaCentroCusto"></div>
                        </div>
                    </div>
                    
                    <div class="chart">
                        <h2>A Receber por PerÃ­odo</h2>
                        <div id="chartReceitaPeriodo"></div>
                    </div>
                </section>
                
                <!-- Despesa -->
                <section id="despesa" class="page-section">
                    <div class="page-header">
                        <h2>A Pagar</h2>
                        <p>AnÃ¡lise detalhada a Pagar</p>
                    </div>
                    
                    <div class="controls">
                       <div class="date-toggle">
  <input type="radio" name="dateTypeDespesa" id="realizadoProjetadoDespesa" value="realizadoProjetado" checked hidden>
  <label for="realizadoProjetadoDespesa">ðŸ“… Realizado + Projetado</label>
  
  <input type="radio" name="dateTypeDespesa" id="dueDateDespesa" value="dueDate" hidden>
  <label for="dueDateDespesa">ðŸ“… Data de Vencimento</label>
  
  <input type="radio" name="dateTypeDespesa" id="compDateDespesa" value="financialEvent.competenceDate" hidden>
  <label for="compDateDespesa">ðŸ“… Data de CompetÃªncia</label>
</div>


                        
                        <div class="date-range-section">
                            <h3>ðŸ“† PerÃ­odo</h3>
                            <div class="date-range-inputs">
                                <div class="date-input-group">
                                    <label for="startMonthDespesa">Data Inicial:</label>
                                    <input type="month" id="startMonthDespesa" name="startMonthDespesa">
                                </div>
                                
                                <div class="date-range-divider">â†’</div>
                                
                                <div class="date-input-group">
                                    <label for="endMonthDespesa">Data Final:</label>
                                    <input type="month" id="endMonthDespesa" name="endMonthDespesa">
                                </div>
                                
                                <button class="clear-date-btn" onclick="clearDateRangeDespesa()">ðŸ—‘ï¸ Limpar</button>
                            </div>
                        </div>
                        
                        <div class="filters">
                            <div class="filter-group">
                                <label>Status:</label>
                                <select id="statusFilterDespesa" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Centro de Custo:</label>
                                <select id="costCenterFilterDespesa" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Categoria:</label>
                                <select id="categoryFilterDespesa" multiple>
                                    <option value="">Todas</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Fornecedor:</label>
                                <select id="negotiatorFilterDespesa" multiple>
                                    <option value="">Todos</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpis">
                        <div class="kpi-card negative">
                            <h3>ðŸ’¸ A Pagar Total</h3>
                            <div class="value" id="despesaTotalPage">R$ 0,00</div>
                        </div>
                        
                        <div class="kpi-card">
                            <h3>ðŸŽ¯ Ticket MÃ©dio</h3>
                            <div class="value" id="ticketMedioDespesa">R$ 0,00</div>
                        </div>
                        
                        <div class="kpi-card">
                            <h3>ðŸª MÃ©dia de Fornecedores</h3>
                            <div class="value" id="mediaFornecedoresDespesa">0</div>
                        </div>
                    </div>
                    
                    <div class="charts-row">
                        <div class="chart">
                            <h2>Por Categoria</h2>
                            <div id="chartDespesaCategoria"></div>
                        </div>
                        
                        <div class="chart">
                            <h2>MÃ©dia por Categoria</h2>
                            <div id="chartMediaCategoriaDespesa"></div>
                        </div>
                        
                        <div class="chart">
                            <h2>Por Centro de Custo</h2>
                            <div id="chartDespesaCentroCusto"></div>
                        </div>
                    </div>
                    
                    <div class="chart">
                        <h2>A Pagar por PerÃ­odo</h2>
                        <div id="chartDespesaPeriodo"></div>
                    </div>
                </section>
                
                <!-- Fluxo de Caixa -->
<section id="fluxo-caixa" class="page-section">
    <div class="page-header">
        <h2>Fluxo de Caixa</h2>
        <p>MovimentaÃ§Ã£o financeira detalhada</p>
    </div>
    
    <div class="controls">
        <div class="date-toggle">
  <input type="radio" name="dateTypeFluxo" id="realizadoProjetadoFluxo" value="realizadoProjetado" checked hidden>
  <label for="realizadoProjetadoFluxo">ðŸ“… Realizado + Projetado</label>
  
  <input type="radio" name="dateTypeFluxo" id="dueDateFluxo" value="dueDate" hidden>
  <label for="dueDateFluxo">ðŸ“… Data de Vencimento</label>
  
  <input type="radio" name="dateTypeFluxo" id="compDateFluxo" value="financialEvent.competenceDate" hidden>
  <label for="compDateFluxo">ðŸ“… Data de CompetÃªncia</label>
</div>


        
        <div class="date-range-section">
            <h3>ðŸ“† PerÃ­odo</h3>
            <div class="date-range-inputs">
                <div class="date-input-group">
                    <label for="startMonthFluxo">Data Inicial:</label>
                    <input type="month" id="startMonthFluxo" name="startMonthFluxo">
                </div>
                
                <div class="date-range-divider">â†’</div>
                
                <div class="date-input-group">
                    <label for="endMonthFluxo">Data Final:</label>
                    <input type="month" id="endMonthFluxo" name="endMonthFluxo">
                </div>
                
                <button class="clear-date-btn" onclick="clearDateRangeFluxo()">ðŸ—‘ï¸ Limpar</button>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label>Status:</label>
                <select id="statusFilterFluxo" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Centro de Custo:</label>
                <select id="costCenterFilterFluxo" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Categoria:</label>
                <select id="categoryFilterFluxo" multiple>
                    <option value="">Todas</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Cliente/Fornecedor:</label>
                <select id="negotiatorFilterFluxo" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="chart">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2>Fluxo de Caixa Detalhado por Categoria</h2>
        <button style="padding: 10px 20px; background: #4facfe; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;" onclick="exportFluxoDetalhado()">
            ðŸ’¾ Exportar CSV
        </button>
    </div>
    <div style="overflow-x: auto;">
            <table id="tableFluxoDetalhado" style="width: 100%; border-collapse: collapse;">
                <!-- Tabela serÃ¡ gerada dinamicamente -->
            </table>
        </div>
    </div>
    
    <div class="chart">
  <h2>Consolidado</h2>
  <div style="overflow-x:auto">
    <table id="tableResultadoLiquidoConsolidado" style="width: 100%; border-collapse: collapse;">
      <!-- Tabela serÃ¡ gerada dinamicamente -->
    </table>
  </div>
</div>
    
    <div class="chart">
        <h2>Consolidado de a Receber</h2>
        <div style="overflow-x: auto;">
            <table id="tableReceitaConsolidado" style="width: 100%; border-collapse: collapse;">
                
            </table>
        </div>
    </div>
    
    <div class="chart">
        <h2>Consolidado de a Pagar</h2>
        <div style="overflow-x: auto;">
            <table id="tableDespesaConsolidado" style="width: 100%; border-collapse: collapse;">
                
            </table>
        </div>
    </div> 
</section>

                
                <!-- Centro de Custo -->
<section id="centro-custo" class="page-section">
    <div class="page-header">
        <h2>Centro de Custo</h2>
        <p>AnÃ¡lise por centro de custo</p>
    </div>
    
    <div class="controls">
        <div class="date-toggle">
  <input type="radio" name="dateTypeCentroCusto" id="realizadoProjetadoCentroCusto" value="realizadoProjetado" checked hidden>
  <label for="realizadoProjetadoCentroCusto">ðŸ“… Realizado + Projetado</label>
  
  <input type="radio" name="dateTypeCentroCusto" id="dueDateCentroCusto" value="dueDate" hidden>
  <label for="dueDateCentroCusto">ðŸ“… Data de Vencimento</label>
  
  <input type="radio" name="dateTypeCentroCusto" id="compDateCentroCusto" value="financialEvent.competenceDate" hidden>
  <label for="compDateCentroCusto">ðŸ“… Data de CompetÃªncia</label>
</div>


        
        <div class="date-range-section">
            <h3>ðŸ“† PerÃ­odo</h3>
            <div class="date-range-inputs">
                <div class="date-input-group">
                    <label for="startMonthCentroCusto">Data Inicial:</label>
                    <input type="month" id="startMonthCentroCusto" name="startMonthCentroCusto">
                </div>
                
                <div class="date-range-divider">â†’</div>
                
                <div class="date-input-group">
                    <label for="endMonthCentroCusto">Data Final:</label>
                    <input type="month" id="endMonthCentroCusto" name="endMonthCentroCusto">
                </div>
                
                <button class="clear-date-btn" onclick="clearDateRangeCentroCusto()">ðŸ—‘ï¸ Limpar</button>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label>Status:</label>
                <select id="statusFilterCentroCusto" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Centro de Custo:</label>
                <select id="costCenterFilterCentroCusto" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Categoria:</label>
                <select id="categoryFilterCentroCusto" multiple>
                    <option value="">Todas</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Cliente/Fornecedor:</label>
                <select id="negotiatorFilterCentroCusto" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="kpis">
        <div class="kpi-card">
            <h3>ðŸ“Š Taxa da Margem</h3>
            <div class="value" id="taxaMargemCentroCusto">0%</div>
        </div>
        
        <div class="kpi-card" id="resultadoCardCentroCusto">
            <h3>ðŸ“ˆ Total do PerÃ­odo</h3>
            <div class="value" id="resultadoLiquidoCentroCusto">R$ 0,00</div>
        </div>
        
        <div class="kpi-card positive">
            <h3>ðŸ’° Total de a Receber</h3>
            <div class="value" id="totalReceitaCentroCusto">R$ 0,00</div>
        </div>
        
        <div class="kpi-card negative">
            <h3>ðŸ’¸ Total de a Pagar</h3>
            <div class="value" id="totalDespesaCentroCusto">R$ 0,00</div>
        </div>
    </div>
    
    <div class="chart">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2>Saldo Mensal por Centro de Custo</h2>
        <button style="padding: 10px 20px; background: #4facfe; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;" onclick="exportSaldoCentroCusto()">
            ðŸ’¾ Exportar CSV
        </button>
    </div>
    <div style="overflow-x: auto;">
            <table id="tableSaldoCentroCusto" style="width: 100%; border-collapse: collapse; table-layout: auto;">
                <!-- Tabela serÃ¡ gerada dinamicamente -->
            </table>
        </div>
    </div>
    
    <div class="chart">
        <h2>Saldo por Centro de Custo</h2>
        <div id="chartSaldoCentroCusto"></div>
    </div>
</section>

                
                <!-- Indicadores -->
<!-- Indicadores -->
<section id="indicadores" class="page-section">
    <div class="page-header">
        <h2>Indicadores</h2>
        <p>Indicadores de performance financeira</p>
    </div>
    
    <div class="controls">
        <div class="date-range-section">
            <h3>ðŸ“… PerÃ­odo</h3>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="date-input-group" style="max-width: 200px;">
                    <label for="yearSelectIndicadores">Ano:</label>
                    <select id="yearSelectIndicadores" style="padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; background: white; cursor: pointer;">
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                    </select>
                </div>
                <button style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 25px;" onclick="loadAllIndicadores()">ðŸ”„ Atualizar</button>
            </div>
        </div>
    </div>
    
    <div id="loadingIndicadores" class="loading" style="display:none;">
        <p>â³ Carregando indicadores...</p>
    </div>
    
    <div id="errorIndicadores" class="error" style="display:none;"></div>
    
    <!-- GrÃ¡ficos de Indicadores -->
    <div id="indicadoresGraphsContent" style="display:none;">
        <div class="charts-row">
            <div class="chart">
                <h2>Saldo por Trimestre</h2>
                <canvas id="chartTrimestre"></canvas>
            </div>
            
            <div class="chart">
                <h2>Saldo por MÃªs</h2>
                <canvas id="chartMes"></canvas>
            </div>
        </div>
        
        <div class="charts-row">
            <div class="chart">
                <h2>Runway (Meses)</h2>
                <canvas id="chartRunway"></canvas>
            </div>
            
            <div class="chart">
                <h2>Free Cash Flow</h2>
                <canvas id="chartFreeCashFlow"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Tabela de Indicadores DRE (original) -->
    <div class="chart" id="indicadoresTableContent" style="display:none;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2>Indicadores Financeiros Mensais (DRE)</h2>
        <button style="padding: 10px 20px; background: #4facfe; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;" onclick="exportIndicadores()">
            ðŸ’¾ Exportar CSV
        </button>
    </div>
    <div style="overflow-x: auto;">
            <table id="tableIndicadores" style="width: 100%; border-collapse: collapse; table-layout: auto;">
                <!-- Tabela serÃ¡ gerada dinamicamente -->
            </table>
        </div>
    </div>
</section>


                
                <!-- DRE -->
<!-- DRE -->
<section id="dre" class="page-section">
    <div class="page-header">
        <h2>DRE</h2>
        <p>DemonstraÃ§Ã£o do Resultado do ExercÃ­cio</p>
    </div>
    
    <div class="controls">
        <!-- Toggle de Tipo de Data -->
        <div class="date-toggle">
            <input type="radio" name="dateTypeDRE" id="realizadoProjetadoDRE" value="realizadoProjetado" checked hidden>
            <label for="realizadoProjetadoDRE">ðŸ“… Realizado + Projetado</label>
            
            <input type="radio" name="dateTypeDRE" id="dueDateDRE" value="dueDate" hidden>
            <label for="dueDateDRE">ðŸ“… Data de Vencimento</label>
            
            <input type="radio" name="dateTypeDRE" id="compDateDRE" value="financialEvent.competenceDate" hidden>
            <label for="compDateDRE">ðŸ“… Data de CompetÃªncia</label>
        </div>
        
        <!-- SeleÃ§Ã£o de PerÃ­odo -->
        <div class="date-range-section">
            <h3>ðŸ“† PerÃ­odo</h3>
            <div class="date-range-inputs">
                <div class="date-input-group">
                    <label for="startMonthDRE">Data Inicial:</label>
                    <input type="month" id="startMonthDRE" name="startMonthDRE">
                </div>
                
                <div class="date-range-divider">â†’</div>
                
                <div class="date-input-group">
                    <label for="endMonthDRE">Data Final:</label>
                    <input type="month" id="endMonthDRE" name="endMonthDRE">
                </div>
                
                <button class="clear-date-btn" onclick="clearDateRangeDRE()">ðŸ—‘ï¸ Limpar</button>
            </div>
        </div>
        
        <!-- Filtros MÃºltiplos -->
        <div class="filters">
            <div class="filter-group">
                <label>Status:</label>
                <select id="statusFilterDRE" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Centro de Custo:</label>
                <select id="costCenterFilterDRE" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Categoria:</label>
                <select id="categoryFilterDRE" multiple>
                    <option value="">Todas</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Cliente/Fornecedor:</label>
                <select id="negotiatorFilterDRE" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            
            <!-- NOVO FILTRO FCO/FCI/FCF -->
            <div class="filter-group">
                <label>Tipo de Fluxo:</label>
                <select id="fluxoFilterDRE" multiple>
                    <option value="">Todos</option>
                    <option value="FCO">FCO - Fluxo Op.</option>
                    <option value="FCI">FCI - Fluxo Inv.</option>
                    <option value="FCF">FCF - Fluxo Fin.</option>
                </select>
            </div>
        </div>
        
        <!-- InstruÃ§Ã£o de Uso -->
        <div class="filter-instruction">
            <span class="instruction-icon">ðŸ’¡</span>
            <span class="instruction-text">Use <kbd>Ctrl</kbd> + <kbd>Clique</kbd> (ou <kbd>Cmd</kbd> no Mac) para selecionar mÃºltiplas opÃ§Ãµes nos filtros</span>
        </div>
        
    
    <div id="loadingDRE" class="loading" style="display:none;">
        <p>â³ Carregando DRE...</p>
    </div>
    
    <div id="errorDRE" class="error" style="display:none;"></div>
    
    <div class="chart" style="background: rgba(15, 15, 35, 0.97);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2>DemonstraÃ§Ã£o do Resultado do ExercÃ­cio</h2>
            <button style="padding: 10px 20px; background: #4facfe; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;" onclick="exportDRE()">ðŸ“¥ Exportar CSV</button>
        </div>
        <div style="overflow-x: auto">
    <table id="tableDRE" style="width: 100%; border-collapse: collapse; table-layout: auto">
                <!-- Tabela serÃ¡ gerada dinamicamente -->
            </table>
        </div>
    </div>
</section>


                
                <!-- Simulador -->
<section id="simulador" class="page-section">
    <div class="page-header">
        <h2>Simulador</h2>
        <p>SimulaÃ§Ãµes e projeÃ§Ãµes financeiras</p>
    </div>
    
    <div class="controls">
        <div class="date-range-section">
            <h3>ðŸ“… PerÃ­odo</h3>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="date-input-group" style="max-width: 200px;">
                    <label for="yearSelectSimulador">Ano:</label>
                    <select id="yearSelectSimulador" style="padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; background: white; cursor: pointer;">
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                    </select>
                </div>
                <button style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 25px;" onclick="loadSimuladorData()">ðŸ”„ Carregar Dados</button>
                <button style="padding: 10px 20px; background: #f5576c; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 25px;" onclick="resetSimulacao()">â†º Resetar</button>
            </div>
        </div>
    </div>
    
    <div id="loadingSimulador" class="loading" style="display:none;">
        <p>â³ Carregando dados...</p>
    </div>
    
    <div id="errorSimulador" class="error" style="display:none;"></div>
    
    <div id="simuladorContent" style="display:none;">
        <!-- Sliders de Ajuste -->
        <div class="chart">
            <h2>Ajustes de SimulaÃ§Ã£o</h2>
            <div id="slidersContainer" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; padding: 20px;">
                <!-- Sliders serÃ£o gerados dinamicamente -->
            </div>
        </div>
        
        <!-- Tabela e GrÃ¡fico empilhados verticalmente -->
<div style="margin-top: 25px;">
    <div class="chart">
        <h2>Comparativo Real x Simulado</h2>
        <div style="overflow-x: auto;">
            <table id="tableSimulador" style="width: 100%; border-collapse: collapse; table-layout: auto;">
                <!-- Tabela serÃ¡ gerada dinamicamente -->
            </table>
        </div>
    </div>
    
    <div class="chart">
        <h2>Comparativo por Categoria</h2>
        <div id="chartSimulador"></div>
    </div>
</div>

    </div>
</section>




<!-- AnÃ¡lise de Rentabilidade -->
<section id="rentabilidade" class="page-section">
    <div class="page-header">
        <h2>AnÃ¡lise de Rentabilidade</h2>
        <p>AnÃ¡lise detalhada de rentabilidade por centro de custo</p>
    </div>
    
    <div class="controls">
        <div class="date-toggle">
            <input type="radio" name="dateTypeRentabilidade" id="realizadoProjetadoRentabilidade" value="realizadoProjetado" checked hidden>
            <label for="realizadoProjetadoRentabilidade">ðŸ“… Realizado + Projetado</label>
            
            <input type="radio" name="dateTypeRentabilidade" id="dueDateRentabilidade" value="dueDate" hidden>
            <label for="dueDateRentabilidade">ðŸ“… Data de Vencimento</label>
            
            <input type="radio" name="dateTypeRentabilidade" id="compDateRentabilidade" value="financialEvent.competenceDate" hidden>
            <label for="compDateRentabilidade">ðŸ“… Data de CompetÃªncia</label>
            
            <input type="radio" name="dateTypeRentabilidade" id="payDateRentabilidade" value="financialEvent.paymentDate" hidden>
            <label for="payDateRentabilidade">ðŸ“… Data de Pagamento</label>
        </div>
        
        <div class="date-range-section">
            <h3><i class="fas fa-calendar-alt"></i> PerÃ­odo de AnÃ¡lise</h3>
            <div class="date-range-inputs">
                <div class="date-input-group">
                    <label>Data Inicial</label>
                    <input type="month" id="startDateRentabilidade">
                </div>
                <div class="date-range-divider">â†’</div>
                <div class="date-input-group">
                    <label>Data Final</label>
                    <input type="month" id="endDateRentabilidade">
                </div>
                <button class="clear-date-btn" onclick="clearDateRangeRentabilidade()">ðŸ—‘ï¸ Limpar</button>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label>Status</label>
                <select id="filterStatusRentabilidade" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Centro de Custo</label>
                <select id="filterCentroCustoRentabilidade" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Categoria</label>
                <select id="filterCategoriaRentabilidade" multiple>
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Cliente/Fornecedor</label>
                <select id="filterNegotiatorRentabilidade" multiple>
                    <option value="">Todos</option>
                </select>
            </div>
        </div>
        
        <div class="filter-instruction">
            <span class="instruction-icon">â„¹ï¸</span>
            <div class="instruction-text">
                Use <kbd>Ctrl</kbd> (Windows) ou <kbd>Cmd</kbd> (Mac) + clique para selecionar mÃºltiplos itens nos filtros
            </div>
        </div>
    </div>
    
    <!-- KPIs Principais -->
    <div class="kpis">
        <div class="kpi-card">
            <h3>Contas a Receber</h3>
            <div class="value" id="kpiReceitaTotalRentabilidade">R$ 0,00</div>
        </div>
        <div class="kpi-card">
            <h3>Contas a Pagar</h3>
            <div class="value" id="kpiCustoTotalRentabilidade">R$ 0,00</div>
        </div>
        <div class="kpi-card">
            <h3>Margem Bruta</h3>
            <div class="value" id="kpiMargemBrutaRentabilidade">R$ 0,00</div>
        </div>
        <div class="kpi-card">
            <h3>% Margem Bruta</h3>
            <div class="value" id="kpiPercMargemBrutaRentabilidade">0%</div>
        </div>
        <div class="kpi-card">
            <h3>Centros de Custos Lucrativos</h3>
            <div class="value" id="kpiCentrosCustosLucrativosRentabilidade">0/0</div>
        </div>
    </div>
    
    <!-- AnÃ¡lise por Centro de Custo -->
    <div class="chart">
        <h2>AnÃ¡lise por Centro de Custo</h2>
        <div id="tabelaCentrosCustosRentabilidade"></div>
    </div>
    
    <div class="charts-row">
        <div class="chart">
            <h2>Top 10 Centros de Custo - A Receber</h2>
            <div id="graficoTop10CentrosCustosReceita"></div>
        </div>
        <div class="chart">
            <h2>Top 10 Centros de Custo - Margem (%)</h2>
            <div id="graficoTop10CentrosCustosMargem"></div>
        </div>
    </div>
    
    <!-- Break-Even Analysis -->
    
    <!-- AnÃ¡lise de ContribuiÃ§Ã£o Marginal -->
</section>





<!-- Simulador Fluxo de Caixa -->
<section id="simulador-fluxo" class="page-section">
  <div class="page-header">
    <h2>Simulador de Fluxo de Caixa</h2>
    <p>Simule diferentes cenÃ¡rios editando os valores do fluxo de caixa</p>
  </div>

  <div class="controls">
    <div class="date-toggle">
  <input type="radio" name="dateTypeSimFluxo" id="realizadoProjetadoSimFluxo" value="realizadoProjetado" checked hidden>
  <label for="realizadoProjetadoSimFluxo">ðŸ“… Realizado + Projetado</label>
  
  <input type="radio" name="dateTypeSimFluxo" id="dueDateSimFluxo" value="dueDate" hidden>
  <label for="dueDateSimFluxo">ðŸ“… Data de Vencimento</label>
  
  <input type="radio" name="dateTypeSimFluxo" id="compDateSimFluxo" value="financialEvent.competenceDate" hidden>
  <label for="compDateSimFluxo">ðŸ“… Data de CompetÃªncia</label>
</div>


    
    <div class="date-range-section">
      <h3>PerÃ­odo</h3>
      <div class="date-range-inputs">
        <div class="date-input-group">
          <label for="startMonthSimFluxo">Data Inicial</label>
          <input type="month" id="startMonthSimFluxo" name="startMonthSimFluxo">
        </div>
        <div class="date-range-divider"></div>
        <div class="date-input-group">
          <label for="endMonthSimFluxo">Data Final</label>
          <input type="month" id="endMonthSimFluxo" name="endMonthSimFluxo">
        </div>
        <button class="clear-date-btn" onclick="clearDateRangeSimFluxo()">Limpar</button>
      </div>
    </div>

    <div class="filters">
      <div class="filter-group">
        <label>Status</label>
        <select id="statusFilterSimFluxo" multiple>
          <option value="">Todos</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Centro de Custo</label>
        <select id="costCenterFilterSimFluxo" multiple>
          <option value="">Todos</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Categoria</label>
        <select id="categoryFilterSimFluxo" multiple>
          <option value="">Todas</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Cliente/Fornecedor</label>
        <select id="negotiatorFilterSimFluxo" multiple>
          <option value="">Todos</option>
        </select>
      </div>
    </div>
    
    <div style="display: flex; gap: 10px; margin-top: 20px;">
      <button style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;" onclick="resetSimuladorFluxo()">
        ðŸ”„ Resetar Valores
      </button>
      <button style="padding: 10px 20px; background: #4facfe; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;" onclick="exportSimuladorFluxo()">
        ðŸ’¾ Exportar SimulaÃ§Ã£o
      </button>
    </div>
  </div>

  <div class="chart">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <h2>Fluxo de Caixa Detalhado por Categoria (EditÃ¡vel)</h2>
      <div style="background: #fff3cd; padding: 10px 15px; border-radius: 8px; border: 1px solid #ffc107;">
        <span style="color: #856404; font-weight: 600;">ðŸ’¡ Clique nos valores para editar e simular diferentes cenÃ¡rios</span>
      </div>
    </div>
    <div style="overflow-x: auto;">
      <table id="tableSimuladorFluxoDetalhado" style="width: 100%; border-collapse: collapse;">
        <!-- Tabela serÃ¡ gerada dinamicamente -->
      </table>
    </div>
  </div>

<div class="chart">
  <h2>Consolidado do Total do PerÃ­odo (Simulado)</h2>
  <div style="overflow-x:auto;">
    <table id="tableConsolidadoResultadoLiquidoSimulado" style="width:100%; border-collapse: collapse;"></table>
  </div>
</div>

  <div class="chart">
    <h2>Consolidado de a Receber (Simulado)</h2>
    <div style="overflow-x: auto;">
      <table id="tableSimuladorReceitaConsolidado" style="width: 100%; border-collapse: collapse;">
        <!-- Tabela serÃ¡ gerada dinamicamente -->
      </table>
    </div>
  </div>

  <div class="chart">
    <h2>Consolidado de a Pagar (Simulado)</h2>
    <div style="overflow-x: auto;">
      <table id="tableSimuladorDespesaConsolidado" style="width: 100%; border-collapse: collapse;">
      </table>
    </div>
  </div>
</section>



            </div>
        </main>
    </div>

    <script>
    
    const GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1 = '<?php echo $GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1; ?>';
    const GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2 = '<?php echo $GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2; ?>';
    const GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar = '<?php echo $GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar; ?>';

        const GOOGLE_SHEETS_CSV_GID = '1876626478';
        const GOOGLE_SHEETS_FILE_IDS = [
            GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1,
            GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2,
            GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar
        ].filter(Boolean);

        function buildCsvUrl(fileId) {
            return `https://docs.google.com/spreadsheets/d/${fileId}/export?format=csv&gid=${GOOGLE_SHEETS_CSV_GID}`;
        }

        function parseCsvFromUrl(url) {
            return new Promise((resolve, reject) => {
                Papa.parse(url, {
                    download: true,
                    header: true,
                    complete: function(results) {
                        resolve(results.data || []);
                    },
                    error: function(error) {
                        reject(error);
                    }
                });
            });
        }

        async function loadCombinedCsvData() {
            if (GOOGLE_SHEETS_FILE_IDS.length === 0) {
                throw new Error('Nenhuma planilha foi configurada nas variÃ¡veis de ambiente.');
            }

            const requests = GOOGLE_SHEETS_FILE_IDS.map(fileId => parseCsvFromUrl(buildCsvUrl(fileId)));
            const results = await Promise.allSettled(requests);

            const combinedData = [];
            const errors = [];

            results.forEach((result, index) => {
                const fileId = GOOGLE_SHEETS_FILE_IDS[index];
                if (result.status === 'fulfilled') {
                    combinedData.push(...result.value);
                    return;
                }

                const message = result.reason && result.reason.message ? result.reason.message : String(result.reason);
                errors.push(`ID ${fileId}: ${message}`);
            });

            if (errors.length > 0) {
                console.warn('Falha ao carregar algumas planilhas:', errors);
            }

            if (combinedData.length === 0) {
                throw new Error(`NÃ£o foi possÃ­vel carregar dados das planilhas configuradas. ${errors.join(' | ')}`);
            }

            return combinedData;
        }
        
        // Mapeamento de categorias DRE para tipo de fluxo (baseado no Excel)
const dreFluxoMapping = {
    '3.01': 'FCO',
    '4.01': 'FCO',
    '4.02': 'FCO',
    '4.03': 'FCO',
    '5.01': 'FCO',
    '5.02': 'FCO',
    '5.03': 'FCO',
    '6.01': 'FCO',
    '6.02': 'FCO',
    '6.03': 'FCO',
    '6.04': 'FCF',
    '6.05': 'FCI',
    '6.06': 'FCF',
    '7.01': 'FCO',
    '7.02': 'FCO',
    '7.03': 'FCO',
    '7.04': 'FCF',
    '7.05': 'FCI',
    '7.06': 'FCF',
    '3.02': 'FCF',
    '3.03': 'FCI',
    '3.04': 'FCF',
    '8.03': 'FCF',
    '8.04': 'FCI',
    '8.05': 'FCF',
    '9.01': 'FCO',
    '9.02': 'FCO',
    '1.01': 'FCO',
    '1.02': 'FCO'
};

        
        // FunÃ§Ã£o para formatar valores no padrÃ£o 1,1Mi, 300k ou 40,03
function formatCompactValue(value) {
    const absValue = Math.abs(value);
    
    if (absValue >= 1000000) {
        // MilhÃµes
        const formatted = (value / 1000000).toFixed(1).replace('.', ',');
        return formatted + 'Mi';
    } else if (absValue >= 1000) {
        // Milhares
        const formatted = (value / 1000).toFixed(1);
        return formatted + 'k';
    } else {
        // Valores menores que 1000
        return value.toFixed(2).replace('.', ',');
    }
}


        
        let rawData = [];
        let currentDateType = 'realizadoProjetado';
        let currentDateTypeReceita = 'realizadoProjetado';
        let currentDateTypeDespesa = 'realizadoProjetado';
        let currentDateTypeDRE = 'realizadoProjetado';

        
// VariÃ¡veis para Simulador de Fluxo de Caixa
let currentDateTypeSimFluxo = 'realizadoProjetado';
let simuladorFluxoOriginalData = {}; // Armazena valores originais
let simuladorFluxoEditedData = {}; // Armazena valores editados


        
        // Mapeamento de Status (DE-PARA)
const statusLabels = {
    'ACQUITTED': 'Realizado',
    'LOST': 'Perdido',
    'OVERDUE': 'Vencido',
    'PENDING': 'A Vencer',
    'PARTIAL': 'Parcial',
    'RENEGOTIATED': 'Renegociado'
};

// FunÃ§Ã£o helper para obter o label do status
function getStatusLabel(status) {
    return statusLabels[status] || status;
}

// FunÃ§Ã£o helper para obter a data correta baseada no tipo de filtro
function getDateForRow(row, dateType) {
  if (dateType === 'realizadoProjetado') {
    // Se lastAcquittanceDate existe e nÃ£o estÃ¡ vazio, usa ela
    const lastAcquittance = row.lastAcquittanceDate;
    if (lastAcquittance && lastAcquittance.trim() !== '') {
      return lastAcquittance;
    } else {
      return row.dueDate;
    }
  } else {
    // Para outros tipos, retorna o campo normalmente
    return row[dateType];
  }
}

        // FunÃ§Ã£o para setar datas padrÃ£o do ano vigente
function setDefaultDates() {
    const currentYear = new Date().getFullYear();
    const startDate = `${currentYear}-01`; // Janeiro
    const endDate = `${currentYear}-12`;   // Dezembro
    
    // VisÃ£o Geral
    const startMonth = document.getElementById('startMonth');
    const endMonth = document.getElementById('endMonth');
    if (startMonth) startMonth.value = startDate;
    if (endMonth) endMonth.value = endDate;
    
    // Receita
    const startMonthReceita = document.getElementById('startMonthReceita');
    const endMonthReceita = document.getElementById('endMonthReceita');
    if (startMonthReceita) startMonthReceita.value = startDate;
    if (endMonthReceita) endMonthReceita.value = endDate;
    
    // Despesa
    const startMonthDespesa = document.getElementById('startMonthDespesa');
    const endMonthDespesa = document.getElementById('endMonthDespesa');
    if (startMonthDespesa) startMonthDespesa.value = startDate;
    if (endMonthDespesa) endMonthDespesa.value = endDate;
    
    // Fluxo de Caixa
    const startMonthFluxo = document.getElementById('startMonthFluxo');
    const endMonthFluxo = document.getElementById('endMonthFluxo');
    if (startMonthFluxo) startMonthFluxo.value = startDate;
    if (endMonthFluxo) endMonthFluxo.value = endDate;
    
    // Centro de Custo
    const startMonthCentroCusto = document.getElementById('startMonthCentroCusto');
    const endMonthCentroCusto = document.getElementById('endMonthCentroCusto');
    if (startMonthCentroCusto) startMonthCentroCusto.value = startDate;
    if (endMonthCentroCusto) endMonthCentroCusto.value = endDate;
    
    // Rentabilidade
const startDateRentabilidade = document.getElementById('startDateRentabilidade');
const endDateRentabilidade = document.getElementById('endDateRentabilidade');
if (startDateRentabilidade) startDateRentabilidade.value = startDate;
if (endDateRentabilidade) endDateRentabilidade.value = endDate;

// DRE
const startMonthDRE = document.getElementById('startMonthDRE');
    const endMonthDRE = document.getElementById('endMonthDRE');
    if (startMonthDRE) startMonthDRE.value = startDate;
    if (endMonthDRE) endMonthDRE.value = endDate;

}

        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        function showPage(pageId, event) {
    event.preventDefault();
    
    // Remover classe active de todas as seÃ§Ãµes
    document.querySelectorAll('.page-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Remover classe active de todos os links do menu
    document.querySelectorAll('.menu-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Adicionar classe active na seÃ§Ã£o e link correspondentes
    document.getElementById(pageId).classList.add('active');
    event.currentTarget.classList.add('active');
    
    // Fechar sidebar em mobile
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.remove('active');
    }
    
    // Atualizar dados especÃ­ficos de cada pÃ¡gina ao trocar
    if (pageId === 'visao-geral') {
        updateDashboard();
    } else if (pageId === 'receita') {
        updateReceitaPage();
    } else if (pageId === 'despesa') {
        updateDespesaPage();
    } else if (pageId === 'fluxo-caixa') {
        updateFluxoCaixaPage();
    } else if (pageId === 'centro-custo') {
        updateCentroCustoPage();
    } else if (pageId === 'dre') {
        initDREPage();
    } else if (pageId === 'indicadores') {
        initIndicadoresPage();
    } else if (pageId === 'simulador') {
        initSimuladorPage();
    } else if (pageId === 'simulador-fluxo') {
        initSimuladorFluxoPage();
    } else if (pageId === 'rentabilidade') {
        // Carregar dados se ainda nÃ£o foram carregados, senÃ£o apenas atualizar
        if (globalDataRentabilidade.length === 0) {
            loadRentabilidade();
        } else {
            updateRentabilidade();
        }
    }
}







        
        function parseBrazilianFloat(value) {
            if (!value) return 0;
            const stringValue = String(value);
            const normalized = stringValue.replace(/\./g, '').replace(',', '.');
            return parseFloat(normalized) || 0;
        }
        
        async function loadData() {
    try {
        const combinedData = await loadCombinedCsvData();
        rawData = combinedData.filter(row => row.paid_new || row.unpaid_new);
        console.log('Dados carregados:', rawData.length, 'registros');
        
        rawData.forEach(row => {
            row.paid_new = parseBrazilianFloat(row.paid_new);
            row.unpaid_new = parseBrazilianFloat(row.unpaid_new);
            row.total = row.paid_new + row.unpaid_new;
        });
        
        populateFilters();
        populateFiltersReceita();
        populateFiltersDespesa();
        populateFiltersFluxo();
        populateFiltersCentroCusto();
        populateFiltersDRE(); // NOVA LINHA ADICIONADA
        
        // Event listeners para filtros DRE
document.querySelectorAll('input[name="dateTypeDRE"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        currentDateTypeDRE = e.target.value;
        loadDREData();
    });
});
document.getElementById('startMonthDRE').addEventListener('change', loadDREData);
document.getElementById('endMonthDRE').addEventListener('change', loadDREData);
document.getElementById('statusFilterDRE').addEventListener('change', loadDREData);
document.getElementById('costCenterFilterDRE').addEventListener('change', loadDREData);
document.getElementById('categoryFilterDRE').addEventListener('change', loadDREData);
document.getElementById('negotiatorFilterDRE').addEventListener('change', loadDREData);
document.getElementById('fluxoFilterDRE').addEventListener('change', loadDREData);


['statusFilterDRE', 'costCenterFilterDRE', 'categoryFilterDRE', 'negotiatorFilterDRE', 'fluxoFilterDRE'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.addEventListener('change', () => loadDREData());
    }
});

// Event listeners para perÃ­odo DRE
['startMonthDRE', 'endMonthDRE'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.addEventListener('change', () => loadDREData());
    }
});

        
        // Setar datas padrÃ£o ANTES de atualizar dashboard
        setDefaultDates();
        
        document.getElementById('loading').style.display = 'none';
        document.getElementById('dashboard').style.display = 'block';
        
        
        // Primeira renderizaÃ§Ã£o
        updateDashboard();
        
        // Alternar para competÃªncia e voltar (para forÃ§ar redimensionamento)
        setTimeout(() => {
            document.getElementById('compDate').checked = true;
            currentDateType = 'financialEvent.competenceDate';
            updateDashboard();
            
            setTimeout(() => {
                document.getElementById('realizadoProjetado').checked = true;
                currentDateType = 'realizadoProjetado';
                updateDashboard();
                
                setTimeout(() => resizePlotlyCharts(), 100);
            }, 150);
        }, 300);
    } catch (error) {
        showError('Erro ao carregar dados: ' + error.message);
    }
}


        
        function showError(message) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('error').style.display = 'block';
            document.getElementById('error').textContent = message;
        }
        
        function clearDateRange() {
            document.getElementById('startMonth').value = '';
            document.getElementById('endMonth').value = '';
            updateDashboard();
        }
        
        function clearDateRangeReceita() {
            document.getElementById('startMonthReceita').value = '';
            document.getElementById('endMonthReceita').value = '';
            updateReceitaPage();
        }
        
        function clearDateRangeDespesa() {
            document.getElementById('startMonthDespesa').value = '';
            document.getElementById('endMonthDespesa').value = '';
            updateDespesaPage();
        }
        
        function clearDateRangeDRE() {
    document.getElementById('startMonthDRE').value = '';
    document.getElementById('endMonthDRE').value = '';
    loadDREData();
}


        
        function populateFilters() {
    const statusSet = new Set();
    const costCenterSet = new Set();
    const categorySet = new Set();
    const negotiatorSet = new Set();
    
    rawData.forEach(row => {
        if (row.status) statusSet.add(row.status);
        
        try {
            const costCenter = row['Centro_de_Custo_Unificado'];
            if (costCenter) costCenterSet.add(costCenter);
        } catch (e) {}
        
        const category = row['categoriesRatio.category'];
        if (category) categorySet.add(category);
        
        const negotiator = row['financialEvent.negotiator.name'];
        if (negotiator) negotiatorSet.add(negotiator);
    });
    
    populateSelect('statusFilter', statusSet, true);  // true = usar mapeamento de status
    populateSelect('costCenterFilter', costCenterSet);
    populateSelect('categoryFilter', categorySet);
    populateSelect('negotiatorFilter', negotiatorSet);
}
        
        function populateFiltersReceita() {
    const statusSet = new Set();
    const costCenterSet = new Set();
    const categorySet = new Set();
    const negotiatorSet = new Set();
    
    rawData.filter(row => row.tipo === 'Receita').forEach(row => {
        if (row.status) statusSet.add(row.status);
        
        try {
            const costCenter = row['Centro_de_Custo_Unificado'];
            if (costCenter) costCenterSet.add(costCenter);
        } catch (e) {}
        
        const category = row['categoriesRatio.category'];
        if (category) categorySet.add(category);
        
        const negotiator = row['financialEvent.negotiator.name'];
        if (negotiator) negotiatorSet.add(negotiator);
    });
    
    populateSelect('statusFilterReceita', statusSet, true);  // true = usar mapeamento de status
    populateSelect('costCenterFilterReceita', costCenterSet);
    populateSelect('categoryFilterReceita', categorySet);
    populateSelect('negotiatorFilterReceita', negotiatorSet);
}
        
        function populateFiltersDespesa() {
    const statusSet = new Set();
    const costCenterSet = new Set();
    const categorySet = new Set();
    const negotiatorSet = new Set();
    
    rawData.filter(row => row.tipo === 'Despesa').forEach(row => {
        if (row.status) statusSet.add(row.status);
        
        try {
            const costCenter = row['Centro_de_Custo_Unificado'];
            if (costCenter) costCenterSet.add(costCenter);
        } catch (e) {}
        
        const category = row['categoriesRatio.category'];
        if (category) categorySet.add(category);
        
        const negotiator = row['financialEvent.negotiator.name'];
        if (negotiator) negotiatorSet.add(negotiator);
    });
    
    populateSelect('statusFilterDespesa', statusSet, true);  // true = usar mapeamento de status
    populateSelect('costCenterFilterDespesa', costCenterSet);
    populateSelect('categoryFilterDespesa', categorySet);
    populateSelect('neighboratorFilterDespesa', negotiatorSet);
}
        
        function populateSelect(selectId, items, useStatusMapping = false) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    // Limpar opÃ§Ãµes existentes (exceto a primeira "Todos")
    while (select.options.length > 1) {
        select.remove(1);
    }
    
    // Ordenar items
    const sortedItems = Array.from(items).sort();
    
    // Adicionar novas opÃ§Ãµes
    sortedItems.forEach(item => {
        const option = document.createElement('option');
        option.value = item;
        // Se for status, usar o label traduzido
        option.textContent = useStatusMapping ? getStatusLabel(item) : item;
        select.appendChild(option);
    });
}

        
        function isDateInRange(dateStr, startMonth, endMonth) {
    if (!dateStr) return true;
    
    // Se nÃ£o houver filtro de data, retornar true
    if (!startMonth && !endMonth) return true;
    
    // Normalizar a data removendo timezone e convertendo para objeto Date
    let dateObj;
    
    // Se a data contÃ©m 'T' (formato ISO com hora)
    if (dateStr.includes('T')) {
        // Remover timezone e hora, manter apenas YYYY-MM-DD
        const datePart = dateStr.split('T')[0];
        dateObj = new Date(datePart + 'T00:00:00');
    } else {
        // Data jÃ¡ estÃ¡ no formato YYYY-MM-DD
        dateObj = new Date(dateStr + 'T00:00:00');
    }
    
    // Verificar se a data Ã© vÃ¡lida
    if (isNaN(dateObj.getTime())) return true;
    
    // Extrair YYYY-MM da data convertida
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const yearMonth = `${year}-${month}`;
    
    // Validar filtros
    let isValid = true;
    
    if (startMonth) {
        isValid = isValid && (yearMonth >= startMonth);
    }
    
    if (endMonth) {
        isValid = isValid && (yearMonth <= endMonth);
    }
    
    return isValid;
}


// FunÃ§Ã£o helper para extrair ano-mÃªs de forma segura
function getYearMonthFromDate(dateStr) {
    if (!dateStr) return null;
    
    let dateObj;
    
    // Se a data contÃ©m 'T' (formato ISO com hora)
    if (dateStr.includes('T')) {
        const datePart = dateStr.split('T')[0];
        dateObj = new Date(datePart + 'T00:00:00');
    } else {
        dateObj = new Date(dateStr + 'T00:00:00');
    }
    
    // Verificar se a data Ã© vÃ¡lida
    if (isNaN(dateObj.getTime())) return null;
    
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    
    return `${year}-${month}`;
}



        
        function getFilteredData() {
    const statusFilter = Array.from(document.getElementById('statusFilter').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const costCenterFilter = Array.from(document.getElementById('costCenterFilter').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const categoryFilter = Array.from(document.getElementById('categoryFilter').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const negotiatorFilter = Array.from(document.getElementById('negotiatorFilter').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const startMonth = document.getElementById('startMonth').value;
    const endMonth = document.getElementById('endMonth').value;
    
    return rawData.filter(row => {
        if (statusFilter.length > 0 && !statusFilter.includes(row.status)) return false;
        if (costCenterFilter.length > 0 && !costCenterFilter.includes(row['Centro_de_Custo_Unificado'])) return false;
        if (categoryFilter.length > 0 && !categoryFilter.includes(row['categoriesRatio.category'])) return false;
        if (negotiatorFilter.length > 0 && !negotiatorFilter.includes(row['financialEvent.negotiator.name'])) return false;
        
        const dateToCheck = getDateForRow(row, currentDateType);
        if (!isDateInRange(dateToCheck, startMonth, endMonth)) return false;
        
        return true;
    });
}
        
        function getFilteredDataReceita() {
    const statusFilter = Array.from(document.getElementById('statusFilterReceita').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const costCenterFilter = Array.from(document.getElementById('costCenterFilterReceita').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const categoryFilter = Array.from(document.getElementById('categoryFilterReceita').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const negotiatorFilter = Array.from(document.getElementById('negotiatorFilterReceita').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const startMonth = document.getElementById('startMonthReceita').value;
    const endMonth = document.getElementById('endMonthReceita').value;
    
    return rawData.filter(row => {
        if (row.tipo !== 'Receita') return false;
        if (statusFilter.length > 0 && !statusFilter.includes(row.status)) return false;
        if (costCenterFilter.length > 0 && !costCenterFilter.includes(row['Centro_de_Custo_Unificado'])) return false;
        if (categoryFilter.length > 0 && !categoryFilter.includes(row['categoriesRatio.category'])) return false;
        if (negotiatorFilter.length > 0 && !negotiatorFilter.includes(row['financialEvent.negotiator.name'])) return false;
        
        const dateToCheck = getDateForRow(row, currentDateTypeReceita);
        if (!isDateInRange(dateToCheck, startMonth, endMonth)) return false;
        
        return true;
    });
}

        
        function getFilteredDataDespesa() {
    const statusFilter = Array.from(document.getElementById('statusFilterDespesa').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const costCenterFilter = Array.from(document.getElementById('costCenterFilterDespesa').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const categoryFilter = Array.from(document.getElementById('categoryFilterDespesa').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const negotiatorFilter = Array.from(document.getElementById('negotiatorFilterDespesa').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const startMonth = document.getElementById('startMonthDespesa').value;
    const endMonth = document.getElementById('endMonthDespesa').value;
    
    return rawData.filter(row => {
        if (row.tipo !== 'Despesa') return false;
        if (statusFilter.length > 0 && !statusFilter.includes(row.status)) return false;
        if (costCenterFilter.length > 0 && !costCenterFilter.includes(row['Centro_de_Custo_Unificado'])) return false;
        if (categoryFilter.length > 0 && !categoryFilter.includes(row['categoriesRatio.category'])) return false;
        if (negotiatorFilter.length > 0 && !negotiatorFilter.includes(row['financialEvent.negotiator.name'])) return false;
        
        const dateToCheck = getDateForRow(row, currentDateTypeDespesa);
        if (!isDateInRange(dateToCheck, startMonth, endMonth)) return false;
        
        return true;
    });
}

        
        function groupByMonth(data, dateField) {
    const monthlyData = {};
    
    data.forEach(row => {
        const dateStr = getDateForRow(row, dateField);
        if (!dateStr) return;
        
        // Normalizar data para evitar problemas de timezone
        let dateObj;
        if (dateStr.includes('T')) {
            const datePart = dateStr.split('T')[0];
            dateObj = new Date(datePart + 'T00:00:00');
        } else {
            dateObj = new Date(dateStr + 'T00:00:00');
        }
        
        if (isNaN(dateObj.getTime())) return;
        
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const monthKey = `${year}-${month}`;
        
        if (!monthlyData[monthKey]) {
            monthlyData[monthKey] = { receita: 0, despesa: 0 };
        }
        
        if (row.tipo === 'Receita') {
            monthlyData[monthKey].receita += row.total;
        } else if (row.tipo === 'Despesa') {
            monthlyData[monthKey].despesa += row.total;
        }
    });
    
    return monthlyData;
}

        
        function updateDashboard() {
            const filteredData = getFilteredData();
            
            let receita = 0;
            let despesa = 0;
            
            filteredData.forEach(row => {
                if (row.tipo === 'Receita') {
                    receita += row.total;
                } else if (row.tipo === 'Despesa') {
                    despesa += row.total;
                }
            });
            
            const resultado = receita - despesa;
            const margem = receita > 0 ? ((receita - despesa) / receita) * 100 : 0;
            
            document.getElementById('receitaTotal').textContent = formatCurrency(receita);
            document.getElementById('despesaTotal').textContent = formatCurrency(despesa);
            document.getElementById('resultadoLiquido').textContent = formatCurrency(resultado);
            document.getElementById('taxaMargem').textContent = margem.toFixed(2) + '%';
            
            const resultadoCard = document.getElementById('resultadoCard');
            if (resultado >= 0) {
                resultadoCard.className = 'kpi-card positive';
            } else {
                resultadoCard.className = 'kpi-card negative';
            }
            
            createRevenueExpenseChart(filteredData);
            createNetValueChart(filteredData);
        }
        
        function updateReceitaPage() {
            const filteredData = getFilteredDataReceita();
            
            let receitaTotal = 0;
            const clientesUnicos = new Set();
            let countTransacoes = 0;
            
            filteredData.forEach(row => {
                receitaTotal += row.total;
                countTransacoes++;
                const negotiator = row['financialEvent.negotiator.name'];
                if (negotiator) clientesUnicos.add(negotiator);
            });
            
            const ticketMedio = countTransacoes > 0 ? receitaTotal / countTransacoes : 0;
            const mediaClientes = clientesUnicos.size;
            
            document.getElementById('receitaTotalPage').textContent = formatCurrency(receitaTotal);
            document.getElementById('ticketMedioReceita').textContent = formatCurrency(ticketMedio);
            document.getElementById('mediaClientesReceita').textContent = mediaClientes;
            
            createReceitaPorCategoria(filteredData);
            createMediaPorCategoriaReceita(filteredData);
            createReceitaPorCentroCusto(filteredData);
            createReceitaPorPeriodo(filteredData);
        }
        
        function updateDespesaPage() {
            const filteredData = getFilteredDataDespesa();
            
            let despesaTotal = 0;
            const fornecedoresUnicos = new Set();
            let countTransacoes = 0;
            
            filteredData.forEach(row => {
                despesaTotal += row.total;
                countTransacoes++;
                const negotiator = row['financialEvent.negotiator.name'];
                if (negotiator) fornecedoresUnicos.add(negotiator);
            });
            
            const ticketMedio = countTransacoes > 0 ? despesaTotal / countTransacoes : 0;
            const mediaFornecedores = fornecedoresUnicos.size;
            
            document.getElementById('despesaTotalPage').textContent = formatCurrency(despesaTotal);
            document.getElementById('ticketMedioDespesa').textContent = formatCurrency(ticketMedio);
            document.getElementById('mediaFornecedoresDespesa').textContent = mediaFornecedores;
            
            createDespesaPorCategoria(filteredData);
            createMediaPorCategoriaDespesa(filteredData);
            createDespesaPorCentroCusto(filteredData);
            createDespesaPorPeriodo(filteredData);
        }
        
        function createReceitaPorCategoria(data) {
            const categoriaData = {};
            
            data.forEach(row => {
                const categoria = row['categoriesRatio.category'] || 'Sem categoria';
                if (!categoriaData[categoria]) {
                    categoriaData[categoria] = 0;
                }
                categoriaData[categoria] += row.total;
            });
            
            const categorias = Object.keys(categoriaData).sort((a, b) => categoriaData[b] - categoriaData[a]);
            const valores = categorias.map(cat => categoriaData[cat]);
            
            const trace = {
                y: categorias,
                x: valores,
                type: 'bar',
                orientation: 'h',
                marker: { color: '#4facfe' }
            };
            
            const layout = {
                margin: { l: 150, r: 20, t: 20, b: 40 },
                xaxis: { title: 'Valor (R$)' },
                yaxis: { automargin: true },
                hoverlabel: {
    bgcolor: 'rgba(15, 15, 35, 0.95)',
    bordercolor: 'rgba(255, 215, 0, 0.5)',
    font: {
        family: 'Inter, sans-serif',
        size: 13,
        color: '#ffffff'
    }
}

            };
            
            Plotly.newPlot('chartReceitaCategoria', [trace], layout, { responsive: true });
        }
        
        function createMediaPorCategoriaReceita(data) {
            const categoriaData = {};
            const categoriaCount = {};
            
            data.forEach(row => {
                const categoria = row['categoriesRatio.category'] || 'Sem categoria';
                if (!categoriaData[categoria]) {
                    categoriaData[categoria] = 0;
                    categoriaCount[categoria] = 0;
                }
                categoriaData[categoria] += row.total;
                categoriaCount[categoria]++;
            });
            
            const categorias = Object.keys(categoriaData);
            const medias = categorias.map(cat => categoriaData[cat] / categoriaCount[cat]);
            
            const sortedIndices = medias.map((val, idx) => ({ val, idx }))
                .sort((a, b) => b.val - a.val)
                .map(item => item.idx);
            
            const categoriasSorted = sortedIndices.map(i => categorias[i]);
            const mediasSorted = sortedIndices.map(i => medias[i]);
            
            const trace = {
                y: categoriasSorted,
                x: mediasSorted,
                type: 'bar',
                orientation: 'h',
                marker: { color: '#00f2fe' }
            };
            
            const layout = {
                margin: { l: 150, r: 20, t: 20, b: 40 },
                xaxis: { title: 'MÃ©dia (R$)' },
                yaxis: { automargin: true },
                hoverlabel: {
    bgcolor: 'rgba(15, 15, 35, 0.95)',
    bordercolor: 'rgba(255, 215, 0, 0.5)',
    font: {
        family: 'Inter, sans-serif',
        size: 13,
        color: '#ffffff'
    }
}

            };
            
            Plotly.newPlot('chartMediaCategoria', [trace], layout, { responsive: true });
        }
        
        function createReceitaPorCentroCusto(data) {
            const centroCustoData = {};
            
            data.forEach(row => {
                const centroCusto = row['Centro_de_Custo_Unificado'] || 'Sem centro de custo';
                if (!centroCustoData[centroCusto]) {
                    centroCustoData[centroCusto] = 0;
                }
                centroCustoData[centroCusto] += row.total;
            });
            
            const centros = Object.keys(centroCustoData).sort((a, b) => centroCustoData[b] - centroCustoData[a]);
            const valores = centros.map(centro => centroCustoData[centro]);
            
            const trace = {
                y: centros,
                x: valores,
                type: 'bar',
                orientation: 'h',
                marker: { color: '#667eea' }
            };
            
            const layout = {
                margin: { l: 150, r: 20, t: 20, b: 40 },
                xaxis: { title: 'Valor (R$)' },
                yaxis: { automargin: true },
                hoverlabel: {
    bgcolor: 'rgba(15, 15, 35, 0.95)',
    bordercolor: 'rgba(255, 215, 0, 0.5)',
    font: {
        family: 'Inter, sans-serif',
        size: 13,
        color: '#ffffff'
    }
}

            };
            
            Plotly.newPlot('chartReceitaCentroCusto', [trace], layout, { responsive: true });
        }
        
        function createReceitaPorPeriodo(data) {
    const monthlyData = {};
    data.forEach(row => {
        const dateStr = getDateForRow(row, currentDateTypeReceita);
        if (!dateStr) return;
        
        const monthKey = getYearMonthFromDate(dateStr);  // âœ… CORREÃ‡ÃƒO
        if (!monthKey) return;  // âœ…
        
        if (!monthlyData[monthKey]) monthlyData[monthKey] = 0;
        monthlyData[monthKey] += row.total;
    });
    
    const months = Object.keys(monthlyData).sort();
    const valores = months.map(month => monthlyData[month]);
    
    const monthLabels = months.map(m => {
        const [year, month] = m.split('-');
        return `${month}/${year}`;
    });
    
    const trace = {
        x: monthLabels,
        y: valores,
        type: 'bar',
        marker: { color: '#4facfe' },
        text: valores.map(v => formatCompactValue(v)), // Adiciona os rÃ³tulos formatados
        textposition: 'auto',
        textfont: {
            family: 'Inter, sans-serif',
            size: 12,
            color: '#ffffff'  // Cor branca
        }
    };
    
    const layout = {
        xaxis: { title: 'MÃªs' },
        yaxis: { title: 'Valor (R$)' },
        hoverlabel: {
            bgcolor: 'rgba(15, 15, 35, 0.95)',
            bordercolor: 'rgba(255, 215, 0, 0.5)',
            font: {
                family: 'Inter, sans-serif',
                size: 13,
                color: '#ffffff'
            }
        }
    };
    
    Plotly.newPlot('chartReceitaPeriodo', [trace], layout, { responsive: true });
}

        
        function createDespesaPorCategoria(data) {
            const categoriaData = {};
            
            data.forEach(row => {
                const categoria = row['categoriesRatio.category'] || 'Sem categoria';
                if (!categoriaData[categoria]) {
                    categoriaData[categoria] = 0;
                }
                categoriaData[categoria] += row.total;
            });
            
            const categorias = Object.keys(categoriaData).sort((a, b) => categoriaData[b] - categoriaData[a]);
            const valores = categorias.map(cat => categoriaData[cat]);
            
            const trace = {
                y: categorias,
                x: valores,
                type: 'bar',
                orientation: 'h',
                marker: { color: '#f5576c' }
            };
            
            const layout = {
                margin: { l: 150, r: 20, t: 20, b: 40 },
                xaxis: { title: 'Valor (R$)' },
                yaxis: { automargin: true },
                hoverlabel: {
    bgcolor: 'rgba(15, 15, 35, 0.95)',
    bordercolor: 'rgba(255, 215, 0, 0.5)',
    font: {
        family: 'Inter, sans-serif',
        size: 13,
        color: '#ffffff'
    }
}

            };
            
            Plotly.newPlot('chartDespesaCategoria', [trace], layout, { responsive: true });
        }
        
        function createMediaPorCategoriaDespesa(data) {
            const categoriaData = {};
            const categoriaCount = {};
            
            data.forEach(row => {
                const categoria = row['categoriesRatio.category'] || 'Sem categoria';
                if (!categoriaData[categoria]) {
                    categoriaData[categoria] = 0;
                    categoriaCount[categoria] = 0;
                }
                categoriaData[categoria] += row.total;
                categoriaCount[categoria]++;
            });
            
            const categorias = Object.keys(categoriaData);
            const medias = categorias.map(cat => categoriaData[cat] / categoriaCount[cat]);
            
            const sortedIndices = medias.map((val, idx) => ({ val, idx }))
                .sort((a, b) => b.val - a.val)
                .map(item => item.idx);
            
            const categoriasSorted = sortedIndices.map(i => categorias[i]);
            const mediasSorted = sortedIndices.map(i => medias[i]);
            
            const trace = {
                y: categoriasSorted,
                x: mediasSorted,
                type: 'bar',
                orientation: 'h',
                marker: { color: '#f093fb' }
            };
            
            const layout = {
                margin: { l: 150, r: 20, t: 20, b: 40 },
                xaxis: { title: 'MÃ©dia (R$)' },
                yaxis: { automargin: true },
                hoverlabel: {
    bgcolor: 'rgba(15, 15, 35, 0.95)',
    bordercolor: 'rgba(255, 215, 0, 0.5)',
    font: {
        family: 'Inter, sans-serif',
        size: 13,
        color: '#ffffff'
    }
}

            };
            
            Plotly.newPlot('chartMediaCategoriaDespesa', [trace], layout, { responsive: true });
        }
        
        function createDespesaPorCentroCusto(data) {
            const centroCustoData = {};
            
            data.forEach(row => {
                const centroCusto = row['Centro_de_Custo_Unificado'] || 'Sem centro de custo';
                if (!centroCustoData[centroCusto]) {
                    centroCustoData[centroCusto] = 0;
                }
                centroCustoData[centroCusto] += row.total;
            });
            
            const centros = Object.keys(centroCustoData).sort((a, b) => centroCustoData[b] - centroCustoData[a]);
            const valores = centros.map(centro => centroCustoData[centro]);
            
            const trace = {
                y: centros,
                x: valores,
                type: 'bar',
                orientation: 'h',
                marker: { color: '#764ba2' }
            };
            
            const layout = {
                margin: { l: 150, r: 20, t: 20, b: 40 },
                xaxis: { title: 'Valor (R$)' },
                yaxis: { automargin: true },
                hoverlabel: {
    bgcolor: 'rgba(15, 15, 35, 0.95)',
    bordercolor: 'rgba(255, 215, 0, 0.5)',
    font: {
        family: 'Inter, sans-serif',
        size: 13,
        color: '#ffffff'
    }
}

            };
            
            Plotly.newPlot('chartDespesaCentroCusto', [trace], layout, { responsive: true });
        }
        
        function createDespesaPorPeriodo(data) {
    const monthlyData = {};
    
    data.forEach(row => {
        const dateStr = getDateForRow(row, currentDateTypeDespesa);
        if (!dateStr) return;
        
        const monthKey = getYearMonthFromDate(dateStr);
        if (!monthKey) return;
        
        if (!monthlyData[monthKey]) {
            monthlyData[monthKey] = 0;
        }
        monthlyData[monthKey] += row.total;
    });
    
    const months = Object.keys(monthlyData).sort();
    const valores = months.map(month => monthlyData[month]);
    
    const monthLabels = months.map(m => {
        const [year, month] = m.split('-');
        return `${month}/${year}`;
    });
    
    const trace = {
        x: monthLabels,
        y: valores,
        type: 'bar',
        marker: { color: '#f5576c' },
        text: valores.map(v => formatCompactValue(v)), // Adiciona os rÃ³tulos formatados
        textposition: 'auto',
        textfont: {
            family: 'Inter, sans-serif',
            size: 12,
            color: '#ffffff'  // Texto branco
        }
    };
    
    const layout = {
        xaxis: { title: 'MÃªs' },
        yaxis: { title: 'Valor (R$)' },
        hoverlabel: {
            bgcolor: 'rgba(15, 15, 35, 0.95)',
            bordercolor: 'rgba(255, 215, 0, 0.5)',
            font: {
                family: 'Inter, sans-serif',
                size: 13,
                color: '#ffffff'
            }
        }
    };
    
    Plotly.newPlot('chartDespesaPeriodo', [trace], layout, { responsive: true });
}

        
        function createRevenueExpenseChart(data) {
    const monthlyData = groupByMonth(data, currentDateType);
    const months = Object.keys(monthlyData).sort();
    
    const receitas = months.map(month => monthlyData[month].receita);
    const despesas = months.map(month => monthlyData[month].despesa);
    
    const monthLabels = months.map(m => {
        const [year, month] = m.split('-');
        return `${month}/${year}`;
    });
    
    const trace1 = {
        x: monthLabels,
        y: receitas,
        name: 'Receita',
        type: 'bar',
        marker: { color: '#4facfe' },
        text: receitas.map(v => formatCompactValue(v)),
        textposition: 'auto',
        textfont: {
            family: 'Inter, sans-serif',
            size: 12,
            color: '#ffffff',  // â† ALTERADO PARA BRANCO
            weight: 'bold'
        }
    };
    
    const trace2 = {
        x: monthLabels,
        y: despesas,
        name: 'Despesa',
        type: 'bar',
        marker: { color: '#f5576c' },
        text: despesas.map(v => formatCompactValue(v)),
        textposition: 'auto',
        textfont: {
            family: 'Inter, sans-serif',
            size: 12,
            color: '#ffffff',  // â† ALTERADO PARA BRANCO
            weight: 'bold'
        }
    };
    
    const layout = {
        barmode: 'group',
        xaxis: { title: 'MÃªs' },
        yaxis: { title: 'Valor (R$)' },
        hovermode: 'closest',
        showlegend: true,
        legend: { orientation: 'h', y: -0.2 },
        hoverlabel: {
            bgcolor: 'rgba(15, 15, 35, 0.95)',
            bordercolor: 'rgba(255, 215, 0, 0.5)',
            font: {
                family: 'Inter, sans-serif',
                size: 13,
                color: '#ffffff'
            }
        }
    };
    
    Plotly.newPlot('chartReceitasDespesas', [trace1, trace2], layout, { responsive: true });
}


        
        function createNetValueChart(data) {
    const monthlyData = groupByMonth(data, currentDateType);
    const months = Object.keys(monthlyData).sort();
    
    const netValues = months.map(month => 
        monthlyData[month].receita - monthlyData[month].despesa
    );
    
    const monthLabels = months.map(m => {
        const [year, month] = m.split('-');
        return `${month}/${year}`;
    });
    
    const trace = {
        x: monthLabels,
        y: netValues,
        name: 'Valor LÃ­quido',
        type: 'scatter',
        mode: 'lines+markers+text',
        line: { color: '#667eea', width: 3 },
        marker: { size: 8, color: '#764ba2' },
        text: netValues.map(v => formatCompactValue(v)),
        textposition: 'top center',
        textfont: {
            family: 'Inter, sans-serif',
            size: 12,
            color: '#ffffff',  // â† ALTERADO PARA BRANCO
            weight: 'bold'
        }
    };
    
    const layout = {
        xaxis: { title: 'MÃªs',
        showgrid: false },
        yaxis: { title: 'Total do PerÃ­odo (R$)' },
        hovermode: 'closest',
        showlegend: false,
        hoverlabel: {
            bgcolor: 'rgba(15, 15, 35, 0.95)',
            bordercolor: 'rgba(255, 215, 0, 0.5)',
            font: {
                family: 'Inter, sans-serif',
                size: 13,
                color: '#ffffff'
            }
        }
    };
    
    Plotly.newPlot('chartValorLiquido', [trace], layout, { responsive: true });
}



        
        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }
        
        // Event Listeners - VisÃ£o Geral
        document.querySelectorAll('input[name="dateType"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                currentDateType = e.target.value;
                updateDashboard();
            });
        });
        
        document.getElementById('statusFilter').addEventListener('change', updateDashboard);
        document.getElementById('costCenterFilter').addEventListener('change', updateDashboard);
        document.getElementById('categoryFilter').addEventListener('change', updateDashboard);
        document.getElementById('negotiatorFilter').addEventListener('change', updateDashboard);
        document.getElementById('startMonth').addEventListener('change', updateDashboard);
        document.getElementById('endMonth').addEventListener('change', updateDashboard);
        
        // Event Listeners - Receita
        document.querySelectorAll('input[name="dateTypeReceita"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                currentDateTypeReceita = e.target.value;
                updateReceitaPage();
            });
        });
        
        document.getElementById('statusFilterReceita').addEventListener('change', updateReceitaPage);
        document.getElementById('costCenterFilterReceita').addEventListener('change', updateReceitaPage);
        document.getElementById('categoryFilterReceita').addEventListener('change', updateReceitaPage);
        document.getElementById('negotiatorFilterReceita').addEventListener('change', updateReceitaPage);
        document.getElementById('startMonthReceita').addEventListener('change', updateReceitaPage);
        document.getElementById('endMonthReceita').addEventListener('change', updateReceitaPage);
        
        // Event Listeners - Despesa
        document.querySelectorAll('input[name="dateTypeDespesa"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                currentDateTypeDespesa = e.target.value;
                updateDespesaPage();
            });
        });
        
        document.getElementById('statusFilterDespesa').addEventListener('change', updateDespesaPage);
        document.getElementById('costCenterFilterDespesa').addEventListener('change', updateDespesaPage);
        document.getElementById('categoryFilterDespesa').addEventListener('change', updateDespesaPage);
        document.getElementById('negotiatorFilterDespesa').addEventListener('change', updateDespesaPage);
        document.getElementById('startMonthDespesa').addEventListener('change', updateDespesaPage);
        document.getElementById('endMonthDespesa').addEventListener('change', updateDespesaPage);
        
        // VariÃ¡veis para Fluxo de Caixa
let currentDateTypeFluxo = 'realizadoProjetado';

function clearDateRangeFluxo() {
    document.getElementById('startMonthFluxo').value = '';
    document.getElementById('endMonthFluxo').value = '';
    updateFluxoCaixaPage();
}

function populateFiltersFluxo() {
    const statusSet = new Set();
    const costCenterSet = new Set();
    const categorySet = new Set();
    const negotiatorSet = new Set();
    
    rawData.forEach(row => {
        if (row.status) statusSet.add(row.status);
        
        try {
            const costCenter = row['Centro_de_Custo_Unificado'];
            if (costCenter) costCenterSet.add(costCenter);
        } catch (e) {}
        
        const category = row['categoriesRatio.category'];
        if (category) categorySet.add(category);
        
        const negotiator = row['financialEvent.negotiator.name'];
        if (negotiator) negotiatorSet.add(negotiator);
    });
    
    populateSelect('statusFilterFluxo', statusSet, true);  // true = usar mapeamento de status
    populateSelect('costCenterFilterFluxo', costCenterSet);
    populateSelect('categoryFilterFluxo', categorySet);
    populateSelect('negotiatorFilterFluxo', negotiatorSet);
}

function getFilteredDataFluxo() {
    const statusFilter = Array.from(document.getElementById('statusFilterFluxo').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const costCenterFilter = Array.from(document.getElementById('costCenterFilterFluxo').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const categoryFilter = Array.from(document.getElementById('categoryFilterFluxo').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const negotiatorFilter = Array.from(document.getElementById('negotiatorFilterFluxo').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const startMonth = document.getElementById('startMonthFluxo').value;
    const endMonth = document.getElementById('endMonthFluxo').value;
    
    return rawData.filter(row => {
        if (statusFilter.length > 0 && !statusFilter.includes(row.status)) return false;
        if (costCenterFilter.length > 0 && !costCenterFilter.includes(row['Centro_de_Custo_Unificado'])) return false;
        if (categoryFilter.length > 0 && !categoryFilter.includes(row['categoriesRatio.category'])) return false;
        if (negotiatorFilter.length > 0 && !negotiatorFilter.includes(row['financialEvent.negotiator.name'])) return false;
        
        const dateToCheck = getDateForRow(row, currentDateTypeFluxo);
        if (!isDateInRange(dateToCheck, startMonth, endMonth)) return false;
        
        return true;
    });
}


function updateFluxoCaixaPage() {
    const filteredData = getFilteredDataFluxo();
    
    createFluxoDetalhado(filteredData);
    createReceitaConsolidado(filteredData);
    createDespesaConsolidado(filteredData);
    buildResultadoLiquidoConsolidado(filteredData);
}

function createFluxoDetalhado(data) {
    // Agrupar dados por mÃªs, tipo e categoria
    const monthlyData = {};
    const categorias = new Set();
    const months = new Set();
    
    data.forEach(row => {
        const dateStr = getDateForRow(row, currentDateTypeFluxo);

        if (!dateStr) return;
        
        const monthKey = getYearMonthFromDate(dateStr);
        if (!monthKey) return;

        months.add(monthKey);
        
        const categoria = row['categoriesRatio.category'] || 'Sem categoria';
        const tipo = row.tipo;
        
        categorias.add(categoria);
        
        const key = `${tipo}-${categoria}-${monthKey}`;
        if (!monthlyData[key]) {
            monthlyData[key] = { receita: 0, despesa: 0 };
        }
        
        if (tipo === 'Receita') {
            monthlyData[key].receita += row.total;
        } else if (tipo === 'Despesa') {
            monthlyData[key].despesa += row.total;
        }
    });
    
    const sortedMonths = Array.from(months).sort();
    const sortedCategorias = Array.from(categorias).sort();
    
    // Criar tabela HTML
    let html = '<thead><tr style="background: #667eea; color: white;">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #667eea; z-index: 2;"min-width: 200px;>Tipo</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 100px; background: #667eea; z-index: 2;min-width: 200px;">Categoria</th>';
    
    sortedMonths.forEach(month => {
        const [year, mon] = month.split('-');
        html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 120px;">${mon}/${year}</th>`;
    });
    
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 140px; background: #f5f5f5; font-weight: bold;">TOTAL</th>';
    html += '</tr></thead><tbody>';
    
    // SeÃ§Ã£o de Receitas
    html += '<tr style="background: #e8f5e9;"><td colspan="' + (sortedMonths.length + 3) + '" style="padding: 10px; border: 1px solid #ddd; font-weight: bold; color: #2e7d32;">ðŸ’° A RECEBER</td></tr>';
    
    sortedCategorias.forEach(categoria => {
        let hasReceitaData = false;
        let totalCategoria = 0;
        let rowHtml = `<tr><td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: white; z-index: 1;min-width: 200px;"></td>`;
        rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 100px; background: white; z-index: 1; min-width: 200px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;" title="${categoria}">${categoria}</td>`;
        
        sortedMonths.forEach(month => {
            const key = `Receita-${categoria}-${month}`;
            const valor = monthlyData[key] ? monthlyData[key].receita : 0;
            if (valor > 0) hasReceitaData = true;
            totalCategoria += valor;
            rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32;">${valor > 0 ? formatCurrency(valor) : '-'}</td>`;
        });
        
        rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #f5f5f5; font-weight: bold; color: #2e7d32;">${totalCategoria > 0 ? formatCurrency(totalCategoria) : '-'}</td>`;
        rowHtml += '</tr>';
        if (hasReceitaData) html += rowHtml;
    });
    
    // Total de Receitas
    html += '<tr style="background: #c8e6c9; font-weight: bold;">';
    html += '<td colspan="2" style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #c8e6c9; z-index: 1;">TOTAL A RECEBER</td>';
    
    let totalGeralReceitas = 0;
    sortedMonths.forEach(month => {
        let totalMes = 0;
        sortedCategorias.forEach(categoria => {
            const key = `Receita-${categoria}-${month}`;
            if (monthlyData[key]) totalMes += monthlyData[key].receita;
        });
        totalGeralReceitas += totalMes;
        html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32; font-weight: bold;">${formatCurrency(totalMes)}</td>`;
    });
    
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #a5d6a7; font-weight: bold; color: #1b5e20; font-size: 1.1em;">${formatCurrency(totalGeralReceitas)}</td>`;
    html += '</tr>';
    
    // SeÃ§Ã£o de Despesas
    html += '<tr style="background: #ffebee;"><td colspan="' + (sortedMonths.length + 3) + '" style="padding: 10px; border: 1px solid #ddd; font-weight: bold; color: #c62828;">ðŸ’¸ A PAGAR</td></tr>';
    
    sortedCategorias.forEach(categoria => {
        let hasDespesaData = false;
        let totalCategoria = 0;
        let rowHtml = `<tr><td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: white; z-index: 1;min-width: 200px;"></td>`;
        rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 100px; background: white; z-index: 1; min-width: 200px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;" title="${categoria}">${categoria}</td>`;
        
        sortedMonths.forEach(month => {
            const key = `Despesa-${categoria}-${month}`;
            const valor = monthlyData[key] ? monthlyData[key].despesa : 0;
            if (valor > 0) hasDespesaData = true;
            totalCategoria += valor;
            rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #c62828;">${valor > 0 ? formatCurrency(valor) : '-'}</td>`;
        });
        
        rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #f5f5f5; font-weight: bold; color: #c62828;">${totalCategoria > 0 ? formatCurrency(totalCategoria) : '-'}</td>`;
        rowHtml += '</tr>';
        if (hasDespesaData) html += rowHtml;
    });
    
    // Total de Despesas
    html += '<tr style="background: #ffcdd2; font-weight: bold;">';
    html += '<td colspan="2" style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #ffcdd2; z-index: 1;">TOTAL A PAGAR</td>';
    
    let totalGeralDespesas = 0;
    sortedMonths.forEach(month => {
        let totalMes = 0;
        sortedCategorias.forEach(categoria => {
            const key = `Despesa-${categoria}-${month}`;
            if (monthlyData[key]) totalMes += monthlyData[key].despesa;
        });
        totalGeralDespesas += totalMes;
        html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #c62828; font-weight: bold;">${formatCurrency(totalMes)}</td>`;
    });
    
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #ef9a9a; font-weight: bold; color: #b71c1c; font-size: 1.1em;">${formatCurrency(totalGeralDespesas)}</td>`;
    html += '</tr>';
    
    // Linha de Resultado LÃ­quido
    html += '<tr style="background: #e3f2fd; font-weight: bold;">';
    html += '<td colspan="2" style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #e3f2fd; z-index: 1;">ðŸ“Š TOTAL DO PERÃODO</td>';
    
    let resultadoGeralLiquido = 0;
    sortedMonths.forEach(month => {
        let receitaTotal = 0;
        let despesaTotal = 0;
        
        sortedCategorias.forEach(categoria => {
            const keyReceita = `Receita-${categoria}-${month}`;
            const keyDespesa = `Despesa-${categoria}-${month}`;
            
            if (monthlyData[keyReceita]) receitaTotal += monthlyData[keyReceita].receita;
            if (monthlyData[keyDespesa]) despesaTotal += monthlyData[keyDespesa].despesa;
        });
        
        const liquido = receitaTotal - despesaTotal;
        resultadoGeralLiquido += liquido;
        const color = liquido >= 0 ? '#1565c0' : '#c62828';
        html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; font-weight: bold; color: ${color};">${formatCurrency(liquido)}</td>`;
    });
    
    const colorTotal = resultadoGeralLiquido >= 0 ? '#0d47a1' : '#b71c1c';
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #bbdefb; font-weight: bold; color: ${colorTotal}; font-size: 1.1em;">${formatCurrency(resultadoGeralLiquido)}</td>`;
    html += '</tr></tbody>';
    
    document.getElementById('tableFluxoDetalhado').innerHTML = html;
}


function createReceitaConsolidado(data) {
    const monthlyData = {};
    const months = new Set();
    
    data.filter(row => row.tipo === 'Receita').forEach(row => {
        const dateStr = getDateForRow(row, currentDateTypeFluxo);
        if (!dateStr) return;
        
        const monthKey = getYearMonthFromDate(dateStr);
        if (!monthKey) return;

        months.add(monthKey);
        
        if (!monthlyData[monthKey]) {
            monthlyData[monthKey] = 0;
        }
        
        monthlyData[monthKey] += row.total;
    });
    
    const sortedMonths = Array.from(months).sort();
    
    // Criar tabela pivotada (meses nas colunas)
    let html = '<thead><tr style="background: #4caf50; color: white;">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; white-space: nowrap;">Tipo</th>';
    
    sortedMonths.forEach(month => {
        const [year, mon] = month.split('-');
        html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right; white-space: nowrap;">${mon}/${year}</th>`;
    });
    
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right; white-space: nowrap; background: #45a049; font-weight: bold;">TOTAL</th>';
    html += '</tr></thead><tbody>';
    
    // Linha de valores
    html += '<tr>';
    html += '<td style="padding: 12px; border: 1px solid #ddd; font-weight: bold; white-space: nowrap;">A Receber</td>';
    
    let totalGeral = 0;
    sortedMonths.forEach(month => {
        const valor = monthlyData[month] || 0;
        totalGeral += valor;
        html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32; font-weight: 600; white-space: nowrap;">${formatCurrency(valor)}</td>`;
    });
    
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #1b5e20; font-weight: bold; background: #e8f5e9; white-space: nowrap;">${formatCurrency(totalGeral)}</td>`;
    html += '</tr>';
    
    html += '</tbody>';
    
    const table = document.getElementById('tableReceitaConsolidado');
    table.innerHTML = html;
    table.style.width = 'auto';
    table.style.tableLayout = 'auto';
}

function createDespesaConsolidado(data) {
    const monthlyData = {};
    const months = new Set();
    
    data.filter(row => row.tipo === 'Despesa').forEach(row => {
        const dateStr = getDateForRow(row, currentDateTypeFluxo);
        if (!dateStr) return;
        
        const monthKey = getYearMonthFromDate(dateStr);
        if (!monthKey) return;

        months.add(monthKey);
        
        if (!monthlyData[monthKey]) {
            monthlyData[monthKey] = 0;
        }
        
        monthlyData[monthKey] += row.total;
    });
    
    const sortedMonths = Array.from(months).sort();
    
    // Criar tabela pivotada (meses nas colunas)
    let html = '<thead><tr style="background: #f44336; color: white;">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; white-space: nowrap;">Tipo</th>';
    
    sortedMonths.forEach(month => {
        const [year, mon] = month.split('-');
        html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right; white-space: nowrap;">${mon}/${year}</th>`;
    });
    
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right; white-space: nowrap; background: #e53935; font-weight: bold;">TOTAL</th>';
    html += '</tr></thead><tbody>';
    
    // Linha de valores
    html += '<tr>';
    html += '<td style="padding: 12px; border: 1px solid #ddd; font-weight: bold; white-space: nowrap;">A Pagar</td>';
    
    let totalGeral = 0;
    sortedMonths.forEach(month => {
        const valor = monthlyData[month] || 0;
        totalGeral += valor;
        html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #c62828; font-weight: 600; white-space: nowrap;">${formatCurrency(valor)}</td>`;
    });
    
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #b71c1c; font-weight: bold; background: #ffebee; white-space: nowrap;">${formatCurrency(totalGeral)}</td>`;
    html += '</tr>';
    
    html += '</tbody>';
    
    const table = document.getElementById('tableDespesaConsolidado');
    table.innerHTML = html;
    table.style.width = 'auto';
    table.style.tableLayout = 'auto';
}



        // Event Listeners - Fluxo de Caixa
document.querySelectorAll('input[name="dateTypeFluxo"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        currentDateTypeFluxo = e.target.value;
        updateFluxoCaixaPage();
    });
});

document.getElementById('statusFilterFluxo').addEventListener('change', updateFluxoCaixaPage);
document.getElementById('costCenterFilterFluxo').addEventListener('change', updateFluxoCaixaPage);
document.getElementById('categoryFilterFluxo').addEventListener('change', updateFluxoCaixaPage);
document.getElementById('negotiatorFilterFluxo').addEventListener('change', updateFluxoCaixaPage);
document.getElementById('startMonthFluxo').addEventListener('change', updateFluxoCaixaPage);
document.getElementById('endMonthFluxo').addEventListener('change', updateFluxoCaixaPage);

function buildResultadoLiquidoConsolidado(data) {
    // Agregar dados por mÃªs
    const receitaPorMes = {};
    const despesaPorMes = {};
    
    data.forEach(row => {
        const dateStr = getDateForRow(row, currentDateTypeFluxo);
        if (!dateStr) return;
        
        const monthKey = getYearMonthFromDate(dateStr);
        if (!monthKey) return;
        
        if (row.tipo === 'Receita') {
            if (!receitaPorMes[monthKey]) receitaPorMes[monthKey] = 0;
            receitaPorMes[monthKey] += row.total || 0;
        } else if (row.tipo === 'Despesa') {
            if (!despesaPorMes[monthKey]) despesaPorMes[monthKey] = 0;
            despesaPorMes[monthKey] += row.total || 0;
        }
    });
    
    // Obter todos os meses Ãºnicos
    const allMonths = new Set([...Object.keys(receitaPorMes), ...Object.keys(despesaPorMes)]);
    const sortedMonths = Array.from(allMonths).sort();
    
    // Montar HTML da tabela
    let html = '<thead><tr style="background: #4facfe; color: white">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left">MÃªs</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right">A Receber</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right">A Pagar</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right">Total do PerÃ­odo</th>';
    html += '</tr></thead><tbody>';
    
    let totalReceita = 0;
    let totalDespesa = 0;
    let totalResultado = 0;
    
    sortedMonths.forEach((month, index) => {
        const [year, mon] = month.split('-');
        const receita = receitaPorMes[month] || 0;
        const despesa = despesaPorMes[month] || 0;
        const resultado = receita - despesa;
        
        totalReceita += receita;
        totalDespesa += despesa;
        totalResultado += resultado;
        
        // Cores conforme padrÃ£o das outras tabelas
        const bgRow = index % 2 === 0 ? 'rgba(255, 255, 255, 0.03)' : 'rgba(255, 255, 255, 0.02)';
        const corReceita = '2e7d32'; // Verde para receita
        const corDespesa = 'c62828'; // Vermelho para despesa
        const corResultado = resultado >= 0 ? '2e7d32' : 'c62828'; // Azul se positivo, vermelho se negativo
        
        html += '<tr style="background: ' + bgRow + '">';
        html += `<td style="padding: 10px 12px; border: 1px solid rgba(255, 255, 255, 0.05); font-weight: 600">${mon}/${year}</td>`;
        html += `<td style="padding: 10px 12px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: right; color: #${corReceita}">${formatCurrency(receita)}</td>`;
        html += `<td style="padding: 10px 12px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: right; color: #${corDespesa}">${formatCurrency(despesa)}</td>`;
        html += `<td style="padding: 10px 12px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: right; color: #${corResultado}; font-weight: bold">${formatCurrency(resultado)}</td>`;
        html += '</tr>';
    });
    
    // Linha de totais com cores de sucesso/erro
    const corTotalResultado = totalResultado >= 0 ? '#1b5e20' : '#b71c1c';
    const bgTotal = totalResultado >= 0 ? 'rgba(232, 245, 233, 0.1)' : 'rgba(255, 235, 238, 0.1)'; // Verde claro ou vermelho claro
    
    html += '<tr style="background: ' + bgTotal + '; font-weight: bold">';
    html += '<td style="padding: 12px; border: 1px solid rgba(255, 255, 255, 0.05)">TOTAL</td>';
    html += `<td style="padding: 12px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: right; color: #1b5e20; font-size: 1.1em">${formatCurrency(totalReceita)}</td>`;
    html += `<td style="padding: 12px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: right; color: #b71c1c; font-size: 1.1em">${formatCurrency(totalDespesa)}</td>`;
    html += `<td style="padding: 12px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: right; color: ${corTotalResultado}; font-size: 1.1em; font-weight: bold">${formatCurrency(totalResultado)}</td>`;
    html += '</tr>';
    
    html += '</tbody>';
    
    document.getElementById('tableResultadoLiquidoConsolidado').innerHTML = html;
}

// VariÃ¡veis para Centro de Custo
let currentDateTypeCentroCusto = 'realizadoProjetado';

function clearDateRangeCentroCusto() {
    document.getElementById('startMonthCentroCusto').value = '';
    document.getElementById('endMonthCentroCusto').value = '';
    updateCentroCustoPage();
}

function clearDateRangeDRE() {
    document.getElementById('startMonthDRE').value = '';
    document.getElementById('endMonthDRE').value = '';
    loadDREData();
}


function populateFiltersCentroCusto() {
    const statusSet = new Set();
    const costCenterSet = new Set();
    const categorySet = new Set();
    const negotiatorSet = new Set();
    
    rawData.forEach(row => {
        if (row.status) statusSet.add(row.status);
        
        try {
            const costCenter = row['Centro_de_Custo_Unificado'];
            if (costCenter) costCenterSet.add(costCenter);
        } catch (e) {}
        
        const category = row['categoriesRatio.category'];
        if (category) categorySet.add(category);
        
        const negotiator = row['financialEvent.negotiator.name'];
        if (negotiator) negotiatorSet.add(negotiator);
    });
    
    populateSelect('statusFilterCentroCusto', statusSet, true);  // true = usar mapeamento de status
    populateSelect('costCenterFilterCentroCusto', costCenterSet);
    populateSelect('categoryFilterCentroCusto', categorySet);
    populateSelect('negotiatorFilterCentroCusto', negotiatorSet);
}


function populateFiltersDRE() {
    const statusSet = new Set();
    const costCenterSet = new Set();
    const categorySet = new Set();
    const negotiatorSet = new Set();
    
    rawData.forEach(row => {
        if (row.status) statusSet.add(row.status);
        
        try {
            const costCenter = row['Centro_de_Custo_Unificado'];
            if (costCenter) costCenterSet.add(costCenter);
        } catch (e) {}
        
        const category = row['categoriesRatio.category'];
        if (category) categorySet.add(category);
        
        const negotiator = row['financialEvent.negotiator.name'];
        if (negotiator) negotiatorSet.add(negotiator);
    });
    
    populateSelect('statusFilterDRE', statusSet, true);
    populateSelect('costCenterFilterDRE', costCenterSet);
    populateSelect('categoryFilterDRE', categorySet);
    populateSelect('negotiatorFilterDRE', negotiatorSet);
}


function getFilteredDataCentroCusto() {
    const statusFilter = Array.from(document.getElementById('statusFilterCentroCusto').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const costCenterFilter = Array.from(document.getElementById('costCenterFilterCentroCusto').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const categoryFilter = Array.from(document.getElementById('categoryFilterCentroCusto').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const negotiatorFilter = Array.from(document.getElementById('negotiatorFilterCentroCusto').selectedOptions).map(opt => opt.value).filter(v => v !== '');
    const startMonth = document.getElementById('startMonthCentroCusto').value;
    const endMonth = document.getElementById('endMonthCentroCusto').value;
    
    return rawData.filter(row => {
        if (statusFilter.length > 0 && !statusFilter.includes(row.status)) return false;
        if (costCenterFilter.length > 0 && !costCenterFilter.includes(row['Centro_de_Custo_Unificado'])) return false;
        if (categoryFilter.length > 0 && !categoryFilter.includes(row['categoriesRatio.category'])) return false;
        if (negotiatorFilter.length > 0 && !negotiatorFilter.includes(row['financialEvent.negotiator.name'])) return false;
        
        const dateToCheck = getDateForRow(row, currentDateTypeCentroCusto);
        if (!isDateInRange(dateToCheck, startMonth, endMonth)) return false;
        
        return true;
    });
}


function updateCentroCustoPage() {
    const filteredData = getFilteredDataCentroCusto();
    
    // Calcular KPIs
    let receita = 0;
    let despesa = 0;
    
    filteredData.forEach(row => {
        if (row.tipo === 'Receita') {
            receita += row.total;
        } else if (row.tipo === 'Despesa') {
            despesa += row.total;
        }
    });
    
    const resultado = receita - despesa;
    const margem = receita > 0 ? ((receita - despesa) / receita) * 100 : 0;
    
    document.getElementById('taxaMargemCentroCusto').textContent = margem.toFixed(2) + '%';
    document.getElementById('resultadoLiquidoCentroCusto').textContent = formatCurrency(resultado);
    document.getElementById('totalReceitaCentroCusto').textContent = formatCurrency(receita);
    document.getElementById('totalDespesaCentroCusto').textContent = formatCurrency(despesa);
    
    const resultadoCard = document.getElementById('resultadoCardCentroCusto');
    if (resultado >= 0) {
        resultadoCard.className = 'kpi-card positive';
    } else {
        resultadoCard.className = 'kpi-card negative';
    }
    
    createSaldoCentroCustoTable(filteredData);
    createSaldoCentroCustoChart(filteredData);
}

function createSaldoCentroCustoTable(data) {
    // Estrutura: { centroCusto: { month: { categoria: { receita, despesa } } } }
    const structure = {};
    const months = new Set();
    const centrosCusto = new Set();
    
    data.forEach(row => {
        const dateStr = getDateForRow(row, currentDateTypeCentroCusto);
        if (!dateStr) return;
        
        const monthKey = getYearMonthFromDate(dateStr);  
        if (!monthKey) return; 
        months.add(monthKey);
        
        const centroCusto = row['Centro_de_Custo_Unificado'] || 'Sem centro de custo';
        const categoria = row['categoriesRatio.category'] || 'Sem categoria';
        
        centrosCusto.add(centroCusto);
        
        if (!structure[centroCusto]) structure[centroCusto] = {};
        if (!structure[centroCusto][monthKey]) structure[centroCusto][monthKey] = {};
        if (!structure[centroCusto][monthKey][categoria]) {
            structure[centroCusto][monthKey][categoria] = { receita: 0, despesa: 0 };
        }
        
        if (row.tipo === 'Receita') {
            structure[centroCusto][monthKey][categoria].receita += row.total;
        } else if (row.tipo === 'Despesa') {
            structure[centroCusto][monthKey][categoria].despesa += row.total;
        }
    });
    
    const sortedMonths = Array.from(months).sort();
    const sortedCentros = Array.from(centrosCusto).sort();
    
    // Criar tabela HTML
    let html = '<thead><tr style="background: #667eea; color: white;">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #667eea; z-index: 2;">Centro de Custo / Categoria</th>';
    
    sortedMonths.forEach(month => {
        const [year, mon] = month.split('-');
        html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 120px;">${mon}/${year}</th>`;
    });
    
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 140px; background: #5a6fd8; font-weight: bold;">TOTAL</th>';
    html += '</tr></thead><tbody>';
    
    sortedCentros.forEach(centro => {
        // Linha do Centro de Custo (total)
        html += `<tr style="background: #e3f2fd; font-weight: bold;">`;
        html += `<td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #e3f2fd; z-index: 1;">ðŸ¢ ${centro}</td>`;
        
        let totalCentro = 0;
        sortedMonths.forEach(month => {
            let receitaMes = 0;
            let despesaMes = 0;
            
            if (structure[centro][month]) {
                Object.values(structure[centro][month]).forEach(values => {
                    receitaMes += values.receita;
                    despesaMes += values.despesa;
                });
            }
            
            const saldo = receitaMes - despesaMes;
            totalCentro += saldo;
            const color = saldo >= 0 ? '#1565c0' : '#c62828';
            html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${color}; font-weight: bold;">${saldo !== 0 ? formatCurrency(saldo) : '-'}</td>`;
        });
        
        const colorTotal = totalCentro >= 0 ? '#0d47a1' : '#b71c1c';
        html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #bbdefb; color: ${colorTotal}; font-weight: bold; font-size: 1.05em;">${formatCurrency(totalCentro)}</td>`;
        html += '</tr>';
        
        // Linhas das Categorias (sublinhas)
        const categorias = new Set();
        Object.values(structure[centro]).forEach(monthData => {
            Object.keys(monthData).forEach(cat => categorias.add(cat));
        });
        
        Array.from(categorias).sort().forEach(categoria => {
            html += `<tr style="background: #f5f5f5;">`;
            html += `<td style="padding: 8px 12px 8px 40px; border: 1px solid #ddd; position: sticky; left: 0; background: #f5f5f5; z-index: 1; color: #666; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${categoria}">â†³ ${categoria}</td>`;
            
            let totalCategoria = 0;
            sortedMonths.forEach(month => {
                let receita = 0;
                let despesa = 0;
                
                if (structure[centro][month] && structure[centro][month][categoria]) {
                    receita = structure[centro][month][categoria].receita;
                    despesa = structure[centro][month][categoria].despesa;
                }
                
                const saldo = receita - despesa;
                totalCategoria += saldo;
                const color = saldo >= 0 ? '#2e7d32' : '#d32f2f';
                html += `<td style="padding: 8px 12px; border: 1px solid #ddd; text-align: right; color: ${color};">${saldo !== 0 ? formatCurrency(saldo) : '-'}</td>`;
            });
            
            const colorTotal = totalCategoria >= 0 ? '#1b5e20' : '#b71c1c';
            html += `<td style="padding: 8px 12px; border: 1px solid #ddd; text-align: right; background: #fafafa; color: ${colorTotal}; font-weight: 600;">${formatCurrency(totalCategoria)}</td>`;
            html += '</tr>';
        });
    });
    
    html += '</tbody>';
    
    document.getElementById('tableSaldoCentroCusto').innerHTML = html;
}

function createSaldoCentroCustoChart(data) {
    const saldoPorCentro = {};
    
    data.forEach(row => {
        const centroCusto = row['Centro_de_Custo_Unificado'] || 'Sem centro de custo';
        
        if (!saldoPorCentro[centroCusto]) {
            saldoPorCentro[centroCusto] = { receita: 0, despesa: 0 };
        }
        
        if (row.tipo === 'Receita') {
            saldoPorCentro[centroCusto].receita += row.total;
        } else if (row.tipo === 'Despesa') {
            saldoPorCentro[centroCusto].despesa += row.total;
        }
    });
    
    // Calcular saldo e ordenar
    const centros = Object.keys(saldoPorCentro);
    const saldos = centros.map(centro => 
        saldoPorCentro[centro].receita - saldoPorCentro[centro].despesa
    );
    
    // Criar array de objetos para ordenar
    const sortedData = centros.map((centro, idx) => ({
        centro: centro,
        saldo: saldos[idx]
    })).sort((a, b) => b.saldo - a.saldo);
    
    const sortedCentros = sortedData.map(item => item.centro);
    const sortedSaldos = sortedData.map(item => item.saldo);
    
    // Cores dinÃ¢micas (verde para positivo, vermelho para negativo)
    const colors = sortedSaldos.map(saldo => saldo >= 0 ? '#4facfe' : '#f5576c');
    
    const trace = {
        y: sortedCentros,
        x: sortedSaldos,
        type: 'bar',
        orientation: 'h',
        marker: { color: colors },
        text: sortedSaldos.map(v => formatCompactValue(v)),  // RÃ³tulos formatados
        textposition: 'auto',
        textfont: {
            family: 'Inter, sans-serif',
            size: 12,
            color: '#ffffff'  // Texto branco
        }
    };
    
    const layout = {
        margin: { l: 200, r: 50, t: 20, b: 60 },
        xaxis: { 
            title: 'Saldo (R$)',
            zeroline: true,
            zerolinewidth: 2,
            zerolinecolor: '#999'
        },
        yaxis: { 
            automargin: true,
            tickfont: { size: 12 }
        },
        height: Math.max(400, sortedCentros.length * 50),
        hoverlabel: {
            bgcolor: 'rgba(15, 15, 35, 0.95)',
            bordercolor: 'rgba(255, 215, 0, 0.5)',
            font: {
                family: 'Inter, sans-serif',
                size: 13,
                color: '#ffffff'
            }
        }
    };
    
    Plotly.newPlot('chartSaldoCentroCusto', [trace], layout, { responsive: true });
}


// Event Listeners - Centro de Custo
document.querySelectorAll('input[name="dateTypeCentroCusto"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        currentDateTypeCentroCusto = e.target.value;
        updateCentroCustoPage();
    });
});

document.getElementById('statusFilterCentroCusto').addEventListener('change', updateCentroCustoPage);
document.getElementById('costCenterFilterCentroCusto').addEventListener('change', updateCentroCustoPage);
document.getElementById('categoryFilterCentroCusto').addEventListener('change', updateCentroCustoPage);
document.getElementById('negotiatorFilterCentroCusto').addEventListener('change', updateCentroCustoPage);
document.getElementById('startMonthCentroCusto').addEventListener('change', updateCentroCustoPage);
document.getElementById('endMonthCentroCusto').addEventListener('change', updateCentroCustoPage);




// Event Listener - Indicadores




// FunÃ§Ã£o para carregar dados da DRE customizada do Google Sheets
async function loadDREData() {
    const loadingDiv = document.getElementById('loadingDRE');
    const errorDiv   = document.getElementById('errorDRE');
    const tableDiv   = document.getElementById('tableDRE');

    if (!loadingDiv || !errorDiv || !tableDiv) {
        console.error('Elementos DRE nÃ£o encontrados');
        return;
    }

    loadingDiv.style.display = 'block';
    errorDiv.style.display = 'none';
    tableDiv.innerHTML = '';

    try {
        const startMonth = document.getElementById('startMonthDRE')?.value || '';
        const endMonth   = document.getElementById('endMonthDRE')?.value || '';

        // Filtros mÃºltiplos
        const statusFilter    = Array.from(document.getElementById('statusFilterDRE').selectedOptions).map(o => o.value).filter(v => v !== 'Todos');
        const costCenterFilter = Array.from(document.getElementById('costCenterFilterDRE').selectedOptions).map(o => o.value).filter(v => v !== 'Todos');
        const categoryFilter  = Array.from(document.getElementById('categoryFilterDRE').selectedOptions).map(o => o.value).filter(v => v !== 'Todas');
        const negotiatorFilter = Array.from(document.getElementById('negotiatorFilterDRE').selectedOptions).map(o => o.value).filter(v => v !== 'Todos');
        const fluxoFilter     = Array.from(document.getElementById('fluxoFilterDRE').selectedOptions).map(o => o.value).filter(v => v !== 'Todos');

        const dadosAno = rawData.filter(row => {
            const dateField = row.lastAcquittanceDate || row['financialEvent.competenceDate'] || row.dueDate;
            if (!dateField) return false;
            const monthKey = getYearMonthFromDate(dateField);
            if (!isDateInRange(monthKey, startMonth, endMonth)) return false;
            if (statusFilter.length > 0    && !statusFilter.includes(row.status)) return false;
            if (costCenterFilter.length > 0 && !costCenterFilter.includes(row['CentrodeCustoUnificado'])) return false;
            if (categoryFilter.length > 0  && !categoryFilter.includes(row['categoriesRatio.category'])) return false;
            if (negotiatorFilter.length > 0 && !negotiatorFilter.includes(row['financialEvent.negotiator.name'])) return false;
            if (fluxoFilter.length > 0     && !fluxoFilter.includes(row['financialEvent.type'])) return false;
            return true;
        });

        const meses = ['Janeiro','Fevereiro','MarÃ§o','Abril','Maio','Junho',
                       'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

        const dreMensal = {};
        meses.forEach(mes => {
            dreMensal[mes] = {
                '3.01':0,'3.02':0,'3.03':0,'3.04':0,
                '4.01':0,'4.02':0,'4.03':0,
                '5.01':0,'5.02':0,'5.03':0,
                '6.01':0,'6.02':0,'6.03':0,'6.04':0,'6.05':0,'6.06':0,'6.07':0,
                '7.01':0,'7.02':0,'7.03':0,'7.04':0,'7.05':0,'7.06':0,'7.07':0,'7.08':0,
                '8.03':0,'8.04':0,'8.05':0,
                '9.01':0,'9.02':0,
                '1.01':0,'1.02':0
            };
        });

        let processados = 0, erros = 0;
        dadosAno.forEach(row => {
            try {
                const categoria = row['categoriesRatio.category'];
                if (!categoria) return;
                const valor = parseFloat(row.total) || 0;
                if (valor === 0) return;
                let dataCompetencia = row.lastAcquittanceDate || row['financialEvent.competenceDate'] || row.dueDate;
                if (!dataCompetencia) return;
                const dateObj = new Date(dataCompetencia.split('T')[0] + 'T00:00:00');
                if (isNaN(dateObj.getTime())) return;
                const mesIndex = dateObj.getMonth();
                const mes = meses[mesIndex];
                if (!dreMensal[mes]) return;
                const codigoMatch = categoria.match(/^(\d+\.\d+)/);
                if (codigoMatch) {
                    const codigo = codigoMatch[1];
                    if (dreMensal[mes][codigo] !== undefined) {
                        dreMensal[mes][codigo] += Math.abs(valor);
                    }
                }
                processados++;
            } catch(err) { erros++; }
        });

        console.log(`DRE: ${processados} processados, ${erros} ignorados`);

        const dreCalculado = {};
        meses.forEach(mes => {
            dreCalculado[mes] = { ...dreMensal[mes] };
            const d = dreCalculado[mes];

            d.A = d['3.01'];
            d.B = d['4.01'] + d['4.02'] + d['4.03'];
            d.C = d.A - d.B;
            d.D = d['5.01'] + d['5.02'] + d['5.03'];
            d.E = d.C - d.D;
            d.F = d.A !== 0 ? (d.E / d.A) * 100 : 0;

            const receitasFixas  = (d['6.04'] || 0) * -1;
            const despesasFixas  = (d['6.01']||0)+(d['6.02']||0)+(d['6.03']||0)+(d['6.05']||0)+(d['6.06']||0)+(d['6.07']||0);
            d.H = despesasFixas + receitasFixas;

            const receitasVariaveis  = (d['7.04'] || 0) * -1;
            const despesasVariaveis  = (d['7.01']||0)+(d['7.02']||0)+(d['7.03']||0)+(d['7.05']||0)+(d['7.06']||0)+(d['7.07']||0)+(d['7.08']||0);
            d.I = despesasVariaveis + receitasVariaveis;

            d.G = d.H + d.I;
            d.K = (d['3.02']||0)+(d['3.03']||0)+(d['3.04']||0);
            d.L = (d['8.03']||0)+(d['8.04']||0)+(d['8.05']||0);
            d.J = d.K - d.L;
            d.M = d.E - d.G + d.J;
            d.N = d.A !== 0 ? (d.M / d.A) * 100 : 0;
            d.O = (d['9.01']||0) - Math.abs(d['9.02']||0);
            d.P = d.M + d.O;
            d.Q = (d['1.01']||0)+(d['1.02']||0);
            d.R = d.P - d.Q;

            if (d.A !== 0 || d.D !== 0) {
                console.log(mes, '| H:', d.H.toFixed(2), '| I:', d.I.toFixed(2), '| G:', d.G.toFixed(2), '| M:', d.M.toFixed(2), '| R:', d.R.toFixed(2));
            }
        });

        createCustomDRETable(meses, dreCalculado);
        loadingDiv.style.display = 'none';

    } catch(error) {
        console.error('Erro ao carregar DRE:', error);
        loadingDiv.style.display = 'none';
        errorDiv.style.display = 'block';
        errorDiv.textContent = 'Erro ao carregar DRE: ' + error.message;
    }
}



function createCustomDRETable(meses, dreCalculado) {
    // Estrutura DRE completa baseada no Excel
    const estruturaDRE = [
        // A - RECEITA OPERACIONAL BRUTA
        { label: 'A - Receita Operacional Bruta', key: 'A', indent: 0, bold: true, bg: '#e8f5e9', isCalculation: true },
        { label: '   3.01 Receita da Venda de Produtos e/ou ServiÃ§os', key: '3.01', indent: 1, bold: false, bg: 'transparent' },
        
        // B - DEDUÃ‡Ã•ES DA RECEITA BRUTA
        { label: 'B - DeduÃ§Ãµes da Receita Bruta', key: 'B', indent: 0, bold: true, bg: '#ffebee', isCalculation: true },
        { label: '   4.01 Abatimentos sobre Vendas', key: '4.01', indent: 1, bold: false, bg: 'transparent' },
        { label: '   4.02 DevoluÃ§Ãµes de Vendas', key: '4.02', indent: 1, bold: false, bg: 'transparent' },
        { label: '   4.03 Impostos sobre Vendas (ICMS, PIS, COFINS)', key: '4.03', indent: 1, bold: false, bg: 'transparent' },
        
        // C - RECEITA OPERACIONAL LÃQUIDA
        { label: 'C - Receita Operacional LÃ­quida (A - B)', key: 'C', indent: 0, bold: true, bg: '#e3f2fd', isCalculation: true },
        
        // D - CUSTO DAS VENDAS
        { label: 'D - Custo das Vendas', key: 'D', indent: 0, bold: true, bg: '#ffebee', isCalculation: true },
        { label: '   5.01 Custo dos Produtos Vendidos (CPV)', key: '5.01', indent: 1, bold: false, bg: 'transparent' },
        { label: '   5.02 Custo das Mercadorias Vendidas (CMV)', key: '5.02', indent: 1, bold: false, bg: 'transparent' },
        { label: '   5.03 Custo dos ServiÃ§os Prestados (CSP)', key: '5.03', indent: 1, bold: false, bg: 'transparent' },
        
        // E - LUCRO BRUTO
        { label: 'E - Lucro Bruto (C - D)', key: 'E', indent: 0, bold: true, bg: '#e3f2fd', isCalculation: true },
        
        // F - MARGEM BRUTA
        { label: 'F - Margem Bruta % (E / A)', key: 'F', indent: 0, bold: true, bg: '#f5f5f5', isCalculation: true, isPercent: true },
        
        // G - DESPESAS OPERACIONAIS
        { label: 'G - Despesas Operacionais (H + I)', key: 'G', indent: 0, bold: true, bg: '#ffebee', isCalculation: true },
        
        // H - DESPESAS FIXAS
        { label: '   H - Fixa', key: 'H', indent: 1, bold: true, bg: '#fff3e0', isCalculation: true },
        { label: '       6.01 Despesas Comerciais', key: '6.01', indent: 2, bold: false, bg: 'transparent' },
        { label: '       6.02 Despesas com Pessoal', key: '6.02', indent: 2, bold: false, bg: 'transparent' },
        { label: '       6.03 Despesas Administrativas', key: '6.03', indent: 2, bold: false, bg: 'transparent' },
        { label: '       6.04 Outras Receitas Operacionais', key: '6.04', indent: 2, bold: false, bg: 'transparent' },
        { label: '       6.05 Outras Despesas Operacionais / Diretoria', key: '6.05', indent: 2, bold: false, bg: 'transparent' },
        { label: '       6.06 Ativos', key: '6.06', indent: 2, bold: false, bg: 'transparent' },
        { label: '       6.07 EmprÃ©stimos', key: '6.07', indent: 2, bold: false, bg: 'transparent' },
        
        // I - DESPESAS VARIÃVEIS
        { label: '   I - VariÃ¡vel', key: 'I', indent: 1, bold: true, bg: '#fff3e0', isCalculation: true },
        { label: '       7.01 Despesas Comerciais', key: '7.01', indent: 2, bold: false, bg: 'transparent' },
        { label: '       7.02 Despesas com Pessoal', key: '7.02', indent: 2, bold: false, bg: 'transparent' },
        { label: '       7.03 Despesas Administrativas', key: '7.03', indent: 2, bold: false, bg: 'transparent' },
        { label: '       7.04 Outras Receitas Operacionais', key: '7.04', indent: 2, bold: false, bg: 'transparent' },
        { label: '       7.05 Outras Despesas Operacionais', key: '7.05', indent: 2, bold: false, bg: 'transparent' },
        { label: '       7.06 Diretoria', key: '7.06', indent: 2, bold: false, bg: 'transparent' },
        { label: '       7.07 Ativos', key: '7.07', indent: 2, bold: false, bg: 'transparent' },
        { label: '       7.08 EmprÃ©stimos', key: '7.08', indent: 2, bold: false, bg: 'transparent' },
        
        // J - OUTRAS RECEITAS E DESPESAS NÃƒO OPERACIONAIS
        { label: 'J - Outras Rec./Desp. NÃ£o Operacionais (K - L)', key: 'J', indent: 0, bold: true, bg: '#f5f5f5', isCalculation: true },
        
        // K - OUTRAS RECEITAS NÃƒO OPERACIONAIS
        { label: '   K - Outras Receitas NÃ£o Operacionais', key: 'K', indent: 1, bold: true, bg: '#f3e5f5', isCalculation: true },
        { label: '       3.02 Diretoria', key: '3.02', indent: 2, bold: false, bg: 'transparent' },
        { label: '       3.03 Ativos', key: '3.03', indent: 2, bold: false, bg: 'transparent' },
        { label: '       3.04 EmprÃ©stimos', key: '3.04', indent: 2, bold: false, bg: 'transparent' },
        
        // L - OUTRAS DESPESAS NÃƒO OPERACIONAIS
        { label: '   L - Outras Despesas NÃ£o Operacionais', key: 'L', indent: 1, bold: true, bg: '#fce4ec', isCalculation: true },
        { label: '       8.03 Diretoria', key: '8.03', indent: 2, bold: false, bg: 'transparent' },
        { label: '       8.04 Ativos', key: '8.04', indent: 2, bold: false, bg: 'transparent' },
        { label: '       8.05 EmprÃ©stimos', key: '8.05', indent: 2, bold: false, bg: 'transparent' },
        
        // M - EBITDA
        { label: 'M - (EBITDA) Lucro/PrejuÃ­zo Antes Resultado Financeiro e Impostos (E - G - J)', key: 'M', indent: 0, bold: true, bg: '#e3f2fd', isCalculation: true },
        
        // N - MARGEM EBITDA
        { label: 'N - Margem EBITDA ou Margem Operacional % (M / A)', key: 'N', indent: 0, bold: true, bg: '#f5f5f5', isCalculation: true, isPercent: true },
        
        // O - RECEITAS E DESPESAS FINANCEIRAS
        { label: 'O - Receitas e Despesas Financeiras', key: 'O', indent: 0, bold: true, bg: '#f5f5f5', isCalculation: true },
        { label: '   9.01 Receitas Financeiras', key: '9.01', indent: 1, bold: false, bg: 'transparent' },
        { label: '   9.02 Despesas Financeiras', key: '9.02', indent: 1, bold: false, bg: 'transparent' },
        
        // P - RESULTADO ANTES DOS IMPOSTOS
        { label: 'P - Resultado Antes dos Impostos sobre o Lucro (M + O)', key: 'P', indent: 0, bold: true, bg: '#e3f2fd', isCalculation: true },
        
        // Q - PROVISÃƒO PARA IMPOSTOS
        { label: 'Q - ProvisÃ£o para Impostos sobre o Lucro', key: 'Q', indent: 0, bold: true, bg: '#ffebee', isCalculation: true },
        { label: '   1.01 Imposto de Renda (IRPJ)', key: '1.01', indent: 1, bold: false, bg: 'transparent' },
        { label: '   1.02 ContribuiÃ§Ã£o Social sobre o Lucro LÃ­quido (CSLL)', key: '1.02', indent: 1, bold: false, bg: 'transparent' },
        
        // R - LUCRO LÃQUIDO
        { label: 'R - Lucro (ou PrejuÃ­zo) LÃ­quido do PerÃ­odo (P - Q)', key: 'R', indent: 0, bold: true, bg: '#c8e6c9', isCalculation: true }
    ];
    
    // Renderizar tabela
    let html = '<table style="width: 100%; border-collapse: collapse; font-size: 0.85em;">';
    
    // CabeÃ§alho
    html += '<thead><tr style="background: #667eea; color: white;">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #667eea; z-index: 2; min-width: 350px; text-align: left;">Categoria</th>';
    
    meses.forEach(mes => {
        html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 120px; white-space: nowrap;">${mes}</th>`;
    });
    
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 140px; background: #5a6fd8; font-weight: bold; white-space: nowrap;">TOTAL</th>';
    html += '</tr></thead><tbody>';
    
    // Renderizar linhas
    estruturaDRE.forEach(linha => {
        const paddingLeft = (linha.indent * 20) + 12;
        const fontWeight = linha.bold ? 'bold' : 'normal';
        const bgColor = linha.bg || 'transparent';
        
        // Calcular total anual
        let total = 0;
        let count = 0;
        
        meses.forEach(mes => {
            const valor = dreCalculado[mes][linha.key] || 0;
            if (linha.isPercent) {
                total += valor;
                count++;
            } else {
                total += valor;
            }
        });
        
        const displayTotal = linha.isPercent 
            ? (count > 0 ? (total / count).toFixed(2) + '%' : '0.00%')
            : formatCurrency(total);
        
        html += `<tr style="background: ${bgColor}">`;
html += `<td style="padding: 10px; padding-left: ${paddingLeft}px; border: 1px solid rgba(255,255,255,0.05); font-weight: ${fontWeight}; position: sticky; left: 0; background: rgba(15,15,35,0.97); z-index: 1; min-width: 350px; color: #e2e8f0;">${linha.label}</td>`;

        // Valores mensais
        meses.forEach(mes => {
            const valor = dreCalculado[mes][linha.key] || 0;
            const displayValor = linha.isPercent ? valor.toFixed(2) + '%' : formatCurrency(valor);
            
            // Cor baseada no tipo
            let color = '#cbd5e1';
            if (linha.isPercent) {
                color = valor >= 0 ? '#4ade80' : '#f87171';
            } else if (linha.key.match(/^3\.|^9\.01/) || ['A','C','E','K','M','P','R'].includes(linha.key)) {
                color = valor >= 0 ? '#4ade80' : '#f87171';
            } else if (['B','D','G','H','I','L','Q'].includes(linha.key) || linha.key.match(/^[4-8]\.|^9\.02|^1\./)) {
                color = valor > 0 ? '#f87171' : '#cbd5e1';
            } else if (['J','O'].includes(linha.key)) {
                color = valor >= 0 ? '#4ade80' : '#f87171';
            }
            
            html += `<td style="padding: 10px 12px; border: 1px solid rgba(255,255,255,0.05); text-align: right; color: ${color}; font-weight: ${linha.bold ? '600' : 'normal'};">${displayValor}</td>`;
        });
        
        // Coluna TOTAL
        let colorTotal = '#cbd5e1';
        const totalValue = linha.isPercent && count > 0 ? total / count : total;
        
        if (linha.isPercent) {
            colorTotal = totalValue >= 0 ? '#4ade80' : '#f87171';
        } else if (linha.key.match(/^3\.|^9\.01/) || ['A','C','E','K','M','P','R'].includes(linha.key)) {
            colorTotal = totalValue >= 0 ? '#4ade80' : '#f87171';
        } else if (['B','D','G','H','I','L','Q'].includes(linha.key) || linha.key.match(/^[4-8]\.|^9\.02|^1\./)) {
            colorTotal = totalValue > 0 ? '#f87171' : '#cbd5e1';
        } else if (['J','O'].includes(linha.key)) {
            colorTotal = totalValue >= 0 ? '#4ade80' : '#f87171';
        }
        
        html += `<td style="padding: 10px 12px; border: 1px solid rgba(255,255,255,0.05); text-align: right; background: rgba(187,222,251,0.1); color: ${colorTotal}; font-weight: bold;">${displayTotal}</td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    
    const container = document.getElementById('tableDRE');
    if (container) {
        container.innerHTML = html;
        // ForÃ§a fundo escuro na div pai (overflow-x: auto)
        const wrapper = container.closest('div[style*="overflow-x"]');
        if (wrapper) wrapper.style.background = 'rgba(15, 15, 35, 0.4)';
        console.log('âœ… Tabela DRE renderizada com sucesso!');
    } else {
        console.error('âŒ Elemento tableDRE nÃ£o encontrado');
    }
}





function exportDRE() {
    const table = document.getElementById('tableDRE');
    if (!table || !table.innerHTML) {
        alert('NÃ£o hÃ¡ dados para exportar. Carregue os dados primeiro.');
        return;
    }
    
    // Extrair dados da tabela
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];
        cols.forEach(col => {
            // Remover formataÃ§Ã£o e pegar apenas o texto
            let text = col.textContent.trim();
            // Escapar aspas duplas
            text = text.replace(/"/g, '""');
            rowData.push(`"${text}"`);
        });
        csv.push(rowData.join(','));
    });
    
    // Criar arquivo e download
    const csvContent = csv.join('\n');
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    const year = document.getElementById('yearSelectDRE').value;
    
    link.setAttribute('href', url);
    link.setAttribute('download', `DRE_${year}_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}



function createDRETable(headers, data) {
    const meses = ['Janeiro', 'Fevereiro', 'MarÃ§o', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    
    // Criar cabeÃ§alho
    let html = '<thead><tr style="background: #667eea; color: white;">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #667eea; z-index: 2; min-width: 250px; text-align: left;">Categoria</th>';
    
    for (let i = 1; i < headers.length; i++) {
        const header = headers[i].trim();
        let displayName = header;
        
        // Mapear nomes dos meses
        if (meses.includes(header)) {
            displayName = header;
        } else if (header === 'Total') {
            displayName = 'TOTAL';
        }
        
        html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 120px; white-space: nowrap;">${displayName}</th>`;
    }
    
    html += '</tr></thead><tbody>';
    
    // Categorias principais (negrito e com fundo)
    const categoriasprincipais = [
        'Receitas Operacionais',
        'Receita Bruta de Vendas',
        'DeduÃ§Ãµes da Receita Bruta',
        'Receita LÃ­quida de Vendas',
        'Custos Operacionais',
        'Lucro Bruto',
        'Despesas Operacionais',
        'Lucro / PrejuÃ­zo Operacional',
        'Receitas e Despesas Financeiras',
        'Outras Receitas e Despesas NÃ£o Operacionais',
        'Lucro / PrejuÃ­zo LÃ­quido',
        'Despesas com Investimentos e EmprÃ©stimos',
        'Lucro / PrejuÃ­zo Final'
    ];
    
    // Processar cada linha
    data.forEach(row => {
        if (row.length === 0 || !row[0]) return;
        
        const categoria = row[0].trim();
        const isPrincipal = categoriasprincipais.includes(categoria);
        const isLucro = categoria.includes('Lucro') || categoria.includes('PrejuÃ­zo');
        
        let bgColor = '#ffffff';
        let fontWeight = 'normal';
        let fontSize = '14px';
        let textIndent = '20px';
        
        if (isPrincipal) {
            fontWeight = 'bold';
            textIndent = '0px';
            fontSize = '15px';
            
            if (isLucro) {
                bgColor = '#e3f2fd';
            } else if (categoria.includes('Receita')) {
                bgColor = '#e8f5e9';
            } else if (categoria.includes('Despesa') || categoria.includes('Custo') || categoria.includes('DeduÃ§Ã£o')) {
                bgColor = '#ffebee';
            } else {
                bgColor = '#f5f5f5';
            }
        }
        
        html += `<tr style="background: ${bgColor};">`;
        html += `<td style="padding: 10px 12px; border: 1px solid #ddd; position: sticky; left: 0; background: ${bgColor}; z-index: 1; font-weight: ${fontWeight}; font-size: ${fontSize}; text-indent: ${textIndent};text-align: left;">${categoria}</td>`;
        
        // Processar valores
        for (let i = 1; i < row.length; i++) {
            let valor = row[i].trim();
            
            if (valor === '') {
                html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right;">-</td>`;
                continue;
            }
            
            // Converter valor brasileiro para nÃºmero
            const valorNumerico = parseBrazilianFloat(valor);
            
            let color = '#333';
            if (valorNumerico < 0) {
                color = '#c62828';
            } else if (valorNumerico > 0 && isLucro) {
                color = '#2e7d32';
            }
            
            html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${color}; font-weight: ${isPrincipal ? 'bold' : 'normal'};">${formatCurrency(valorNumerico)}</td>`;
        }
        
        html += '</tr>';
    });
    
    html += '</tbody>';
    
    document.getElementById('tableDRE').innerHTML = html;
}

// Carregar DRE ao entrar na pÃ¡gina
function initDREPage() {
    if (!rawData || rawData.length === 0) {
        setTimeout(initDREPage, 500);
        return;
    }
    loadDREData();
}



// VariÃ¡veis para Simulador
let simuladorRealData = {};
let simuladorAjustes = {};

async function loadSimuladorData() {
    const year = document.getElementById('yearSelectSimulador').value;
    const loadingDiv = document.getElementById('loadingSimulador');
    const errorDiv = document.getElementById('errorSimulador');
    const contentDiv = document.getElementById('simuladorContent');
    
    loadingDiv.style.display = 'block';
    errorDiv.style.display = 'none';
    contentDiv.style.display = 'none';
    
    try {
        const response = await fetch(`/api-proxy_Casa_Cuidar_1.php?year=${year}`);
        
        if (!response.ok) {
            throw new Error(`Erro ao buscar dados: ${response.status}`);
        }
        
        const csvText = await response.text();
        const lines = csvText.trim().split('\n');
        const headers = lines[0].split(';');
        
        // Categorias principais que queremos
        const categoriasDesejadas = [
            'Receitas Operacionais',
            'DeduÃ§Ãµes da Receita Bruta',
            'Receita LÃ­quida de Vendas',
            'Custos Operacionais',
            'Lucro Bruto',
            'Despesas Operacionais',
            'Lucro / PrejuÃ­zo Operacional',
            'Receitas e Despesas Financeiras',
            'Outras Receitas e Despesas NÃ£o Operacionais',
            'Lucro / PrejuÃ­zo LÃ­quido',
            'Despesas com Investimentos e EmprÃ©stimos',
            'Lucro / PrejuÃ­zo Final'
        ];
        
        simuladorRealData = {};
        simuladorAjustes = {};
        
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(';');
            const categoria = values[0].trim();
            
            if (categoriasDesejadas.includes(categoria)) {
                const totalIndex = values.length - 1;
                const valorTotal = parseBrazilianFloat(values[totalIndex]);
                
                simuladorRealData[categoria] = valorTotal;
                simuladorAjustes[categoria] = 0; // Inicializar com 0%
            }
        }
        
        createSimuladorSliders();
        updateSimuladorTable();
        updateSimuladorChart();
        
        loadingDiv.style.display = 'none';
        contentDiv.style.display = 'block';
        
    } catch (error) {
        console.error('Erro ao carregar dados:', error);
        loadingDiv.style.display = 'none';
        errorDiv.style.display = 'block';
        errorDiv.textContent = `Erro ao carregar dados: ${error.message}`;
    }
}

function createSimuladorSliders() {
    const container = document.getElementById('slidersContainer');
    container.innerHTML = '';
    
    Object.keys(simuladorRealData).forEach(categoria => {
        const sliderDiv = document.createElement('div');
        sliderDiv.className = 'slider-control';
        
        const sliderId = `slider_${categoria.replace(/[^a-zA-Z0-9]/g, '_')}`;
        const valueId = `value_${categoria.replace(/[^a-zA-Z0-9]/g, '_')}`;
        
        sliderDiv.innerHTML = `
            <h4>${categoria}</h4>
            <div class="slider-wrapper">
                <input type="range" 
                       id="${sliderId}" 
                       class="slider-input" 
                       min="-50" 
                       max="50" 
                       value="0" 
                       step="1"
                       oninput="updateSimulacao('${categoria}', this.value)">
                <span id="${valueId}" class="slider-value">0%</span>
            </div>
        `;
        
        container.appendChild(sliderDiv);
    });
}

function updateSimulacao(categoria, percentual) {
    const valueId = `value_${categoria.replace(/[^a-zA-Z0-9]/g, '_')}`;
    document.getElementById(valueId).textContent = `${percentual > 0 ? '+' : ''}${percentual}%`;
    
    simuladorAjustes[categoria] = parseFloat(percentual);
    
    updateSimuladorTable();
    updateSimuladorChart();
}

function resetSimulacao() {
    Object.keys(simuladorAjustes).forEach(categoria => {
        simuladorAjustes[categoria] = 0;
        const sliderId = `slider_${categoria.replace(/[^a-zA-Z0-9]/g, '_')}`;
        const valueId = `value_${categoria.replace(/[^a-zA-Z0-9]/g, '_')}`;
        
        const slider = document.getElementById(sliderId);
        const valueSpan = document.getElementById(valueId);
        
        if (slider) slider.value = 0;
        if (valueSpan) valueSpan.textContent = '0%';
    });
    
    updateSimuladorTable();
    updateSimuladorChart();
}

function updateSimuladorTable() {
    let html = '<thead><tr style="background: #667eea; color: white;">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Categoria</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Real</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Simulado</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">VariaÃ§Ã£o</th>';
    html += '</tr></thead><tbody>';
    
    Object.keys(simuladorRealData).forEach(categoria => {
        const valorReal = simuladorRealData[categoria];
        const ajuste = simuladorAjustes[categoria] || 0;
        const valorSimulado = valorReal * (1 + ajuste / 100);
        const variacao = valorSimulado - valorReal;
        
        const isLucro = categoria.includes('Lucro') || categoria.includes('PrejuÃ­zo');
        const bgColor = isLucro ? '#e3f2fd' : '#f5f5f5';
        
        const colorReal = valorReal < 0 ? '#c62828' : (isLucro && valorReal > 0 ? '#2e7d32' : '#333');
        const colorSimulado = valorSimulado < 0 ? '#c62828' : (isLucro && valorSimulado > 0 ? '#2e7d32' : '#333');
        const colorVariacao = variacao > 0 ? '#2e7d32' : (variacao < 0 ? '#c62828' : '#666');
        
        html += `<tr style="background: ${bgColor};">`;
        html += `<td style="padding: 10px 12px; border: 1px solid #ddd; font-weight: 600;">${categoria}</td>`;
        html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${colorReal}; font-weight: 600;">${formatCurrency(valorReal)}</td>`;
        html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${colorSimulado}; font-weight: 600;">${formatCurrency(valorSimulado)}</td>`;
        html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${colorVariacao}; font-weight: bold;">${variacao > 0 ? '+' : ''}${formatCurrency(variacao)}</td>`;
        html += '</tr>';
    });
    
    html += '</tbody>';
    
    document.getElementById('tableSimulador').innerHTML = html;
}

function updateSimuladorChart() {
    const categorias = Object.keys(simuladorRealData);
    const valoresReais = categorias.map(cat => simuladorRealData[cat]);
    const valoresSimulados = categorias.map(cat => {
        const ajuste = simuladorAjustes[cat] || 0;
        return simuladorRealData[cat] * (1 + ajuste / 100);
    });
    
    const trace1 = {
        y: categorias,
        x: valoresReais,
        name: 'Real',
        type: 'bar',
        orientation: 'h',
        marker: { color: '#4facfe' },
        text: valoresReais.map(v => formatCompactValue(v)),  // RÃ³tulos formatados
        textposition: 'auto',
        textfont: {
            family: 'Inter, sans-serif',
            size: 12,
            color: '#ffffff'  // Texto branco
        }
    };
    
    const trace2 = {
        y: categorias,
        x: valoresSimulados,
        name: 'Simulado',
        type: 'bar',
        orientation: 'h',
        marker: { color: '#667eea' },
        text: valoresSimulados.map(v => formatCompactValue(v)),  // RÃ³tulos formatados
        textposition: 'auto',
        textfont: {
            family: 'Inter, sans-serif',
            size: 12,
            color: '#ffffff'  // Texto branco
        }
    };
    
    const layout = {
        barmode: 'group',
        margin: { l: 250, r: 50, t: 20, b: 60 },
        xaxis: { 
            title: 'Valor (R$)',
            zeroline: true,
            zerolinewidth: 2,
            zerolinecolor: '#999'
        },
        yaxis: { 
            automargin: true,
            tickfont: { size: 11 }
        },
        legend: {
            orientation: 'h',
            y: -0.15
        },
        height: Math.max(500, categorias.length * 50),
        hoverlabel: {
            bgcolor: 'rgba(15, 15, 35, 0.95)',
            bordercolor: 'rgba(255, 215, 0, 0.5)',
            font: {
                family: 'Inter, sans-serif',
                size: 13,
                color: '#ffffff'
            }
        }
    };
    
    Plotly.newPlot('chartSimulador', [trace1, trace2], layout, { responsive: true });
}


function initSimuladorPage() {
    loadSimuladorData();
}

// FunÃ§Ã£o para carregar dados dos Indicadores
// FunÃ§Ã£o para carregar dados dos Indicadores DRE
function criarTabelaIndicadoresDRE(meses, dreCalculado) {
    // Filtra apenas meses com dados
    const mesesComDados = meses.filter(mes => dreCalculado[mes].A !== 0 || dreCalculado[mes].R !== 0);

    const indicadores = [
        { label: 'Receita Bruta (R$)',                   key: 'A',  isPercent: false },
        { label: 'Lucro Bruto (R$)',                      key: 'E',  isPercent: false },
        { label: 'Margem Bruta (%)',                      key: 'F',  isPercent: true  },
        { label: 'EBITDA (R$)',                           key: 'M',  isPercent: false },
        { label: 'Margem EBITDA (%)',                     key: 'N',  isPercent: true  },
        { label: 'Despesas Operacionais (R$)',            key: 'G',  isPercent: false },
        { label: 'Despesas Operacionais (% Receita)',     key: '_DG', isPercent: true  },
        { label: 'Lucro LÃ­quido (R$)',                    key: 'R',  isPercent: false },
        { label: 'Margem LÃ­quida (%)',                    key: '_ML', isPercent: true  },
        { label: 'Free Cash Flow (R$)',                   key: '_FCF',isPercent: false },
    ];

    let html = '<thead><tr style="background:#667eea;color:white;">';
    html += '<th style="padding:12px;border:1px solid rgba(255,255,255,0.1);position:sticky;left:0;background:#667eea;z-index:2;min-width:280px;">Indicador</th>';

    mesesComDados.forEach(mes => {
        html += `<th style="padding:12px;border:1px solid rgba(255,255,255,0.1);text-align:right;min-width:130px;white-space:nowrap;">${mes.substring(0,3)}</th>`;
    });

    html += '<th style="padding:12px;border:1px solid rgba(255,255,255,0.1);text-align:right;min-width:140px;background:#5a6fd8;font-weight:bold;">MÃ©dia/Total</th>';
    html += '</tr></thead><tbody>';

    indicadores.forEach((ind, idx) => {
        const bg = idx % 2 === 0 ? 'rgba(255,255,255,0.03)' : 'rgba(255,255,255,0.06)';

        html += `<tr style="background:${bg};">`;
        html += `<td style="padding:10px 12px;border:1px solid rgba(255,255,255,0.05);font-weight:600;position:sticky;left:0;background:${bg};z-index:1;">${ind.label}</td>`;

        let soma = 0;
        let count = 0;

        mesesComDados.forEach(mes => {
            const d = dreCalculado[mes];
            let valor = 0;

            if (ind.key === '_DG') {
                valor = d.A !== 0 ? (d.G / d.A) * 100 : 0;
            } else if (ind.key === '_ML') {
                valor = d.A !== 0 ? (d.R / d.A) * 100 : 0;
            } else if (ind.key === '_FCF') {
                valor = (d.M || 0) - Math.abs(d['6.06'] || 0);
            } else {
                valor = d[ind.key] || 0;
            }

            soma += valor;
            count++;

            const color = valor < 0 ? '#f87171' : valor > 0 ? '#4ade80' : '#94a3b8';
            const display = ind.isPercent ? valor.toFixed(2) + '%' : formatCurrency(valor);

            html += `<td style="padding:10px 12px;border:1px solid rgba(255,255,255,0.05);text-align:right;color:${color};font-weight:500;">${display}</td>`;
        });

        // Coluna MÃ©dia/Total
        const resumo = ind.isPercent ? (count > 0 ? soma / count : 0) : soma;
        const colorResumo = resumo < 0 ? '#f87171' : resumo > 0 ? '#4ade80' : '#94a3b8';
        const displayResumo = ind.isPercent ? resumo.toFixed(2) + '%' : formatCurrency(resumo);

        html += `<td style="padding:10px 12px;border:1px solid rgba(255,255,255,0.05);text-align:right;background:rgba(96,165,250,0.1);color:${colorResumo};font-weight:bold;">${displayResumo}</td>`;
        html += '</tr>';
    });

    html += '</tbody>';
    document.getElementById('tableIndicadores').innerHTML = html;
}


function createIndicadoresTable(meses, dados) {
    const numMeses = meses.length;
    
    // Calcular indicadores mÃªs a mÃªs
    const indicadores = {
        'Margem LÃ­quida (%)': [],
        'Ponto de EquilÃ­brio (R$)': [],
        'Margem EBIT (%)': [],
        'Despesa Fixa / Receita Operacional (%)': [],
        'Despesa VariÃ¡vel / Receita Operacional (%)': [],
        'Margem de ContribuiÃ§Ã£o / Margem Bruta (%)': [],
        'Margem de ContribuiÃ§Ã£o $ (R$)': []
    };
    
    for (let i = 0; i < numMeses; i++) {
        const receitasOp = dados['Receitas Operacionais'][i];
        const deducoes = Math.abs(dados['DeduÃ§Ãµes da Receita Bruta'][i]);
        const custos = Math.abs(dados['Custos Operacionais'][i]);
        const despesasOp = Math.abs(dados['Despesas Operacionais'][i]);
        const financeiras = Math.abs(dados['Receitas e Despesas Financeiras'][i]);
        const investimentos = Math.abs(dados['Despesas com Investimentos e EmprÃ©stimos'][i]);
        const lucroFinal = dados['Lucro / PrejuÃ­zo Final'][i];
        
        // Margem LÃ­quida = Lucro Final / Receitas Operacionais
        const margemLiquida = receitasOp !== 0 ? (lucroFinal / receitasOp) * 100 : 0;
        indicadores['Margem LÃ­quida (%)'].push(margemLiquida);
        
        // Ponto de EquilÃ­brio = soma de todas as despesas
        const pontoEquilibrio = deducoes + custos + despesasOp + financeiras + investimentos;
        indicadores['Ponto de EquilÃ­brio (R$)'].push(pontoEquilibrio);
        
        // Margem EBIT = (Receitas Op - DeduÃ§Ãµes - Custos - Despesas Op) / Receitas Op
        const margemEBIT = receitasOp !== 0 ? ((receitasOp - deducoes - custos - despesasOp) / receitasOp) * 100 : 0;
        indicadores['Margem EBIT (%)'].push(margemEBIT);
        
        // Despesa Fixa / Receita Operacional
        const despesaFixa = receitasOp !== 0 ? ((despesasOp + financeiras + investimentos) / receitasOp) * 100 : 0;
        indicadores['Despesa Fixa / Receita Operacional (%)'].push(despesaFixa);
        
        // Despesa VariÃ¡vel / Receita Operacional
        const despesaVariavel = receitasOp !== 0 ? ((deducoes + custos) / receitasOp) * 100 : 0;
        indicadores['Despesa VariÃ¡vel / Receita Operacional (%)'].push(despesaVariavel);
        
        // Margem de ContribuiÃ§Ã£o / Margem Bruta
        const margemContribuicao = receitasOp !== 0 ? (1 - ((deducoes + custos) / receitasOp)) * 100 : 0;
        indicadores['Margem de ContribuiÃ§Ã£o / Margem Bruta (%)'].push(margemContribuicao);
        
        // Margem de ContribuiÃ§Ã£o $
        const margemContribuicaoDolar = receitasOp !== 0 ? (1 - ((deducoes + custos) / receitasOp)) * receitasOp : 0;
        indicadores['Margem de ContribuiÃ§Ã£o $ (R$)'].push(margemContribuicaoDolar);
    }
    
    // Criar tabela HTML
    let html = '<thead><tr style="background: #667eea; color: white;">';
    html += '<th style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #667eea; z-index: 2; min-width: 280px;">Indicador</th>';
    
    meses.forEach(mes => {
        html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 120px; white-space: nowrap;">${mes}</th>`;
    });
    
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 120px; background: #5a6fd8; font-weight: bold;">MÃ©dia</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: right; min-width: 120px; background: #5a6fd8; font-weight: bold;">Total</th>';
    html += '</tr></thead><tbody>';
    
    // Adicionar linhas de indicadores
    Object.keys(indicadores).forEach((nomeIndicador, idx) => {
        const valores = indicadores[nomeIndicador];
        const isPercentual = nomeIndicador.includes('(%)');
        const bgColor = idx % 2 === 0 ? '#f5f5f5' : '#ffffff';
        
        html += `<tr style="background: ${bgColor};">`;
        html += `<td style="padding: 10px 12px; border: 1px solid #ddd; font-weight: 600; position: sticky; left: 0; background: ${bgColor}; z-index: 1;">${nomeIndicador}</td>`;
        
        let soma = 0;
        let count = 0;
        
        valores.forEach(valor => {
            if (!isNaN(valor) && isFinite(valor)) {
                soma += valor;
                count++;
            }
            
            const color = valor < 0 ? '#c62828' : (valor > 0 ? '#2e7d32' : '#666');
            const displayValue = isPercentual ? 
                `${valor.toFixed(2)}%` : 
                formatCurrency(valor);
            
            html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${color}; font-weight: 500;">${displayValue}</td>`;
        });
        
        // Calcular mÃ©dia
        const media = count > 0 ? soma / count : 0;
        const colorMedia = media < 0 ? '#c62828' : (media > 0 ? '#2e7d32' : '#666');
        const displayMedia = isPercentual ? 
            `${media.toFixed(2)}%` : 
            formatCurrency(media);
        
        html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; background: #e3f2fd; color: ${colorMedia}; font-weight: bold;">${displayMedia}</td>`;
        
        // Total (soma ou mÃ©dia dependendo do indicador)
        const total = isPercentual ? media : soma;
        const colorTotal = total < 0 ? '#c62828' : (total > 0 ? '#2e7d32' : '#666');
        const displayTotal = isPercentual ? 
            `${total.toFixed(2)}%` : 
            formatCurrency(total);
        
        html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; background: #bbdefb; color: ${colorTotal}; font-weight: bold;">${displayTotal}</td>`;
        html += '</tr>';
    });
    
    html += '</tbody>';
    
    document.getElementById('tableIndicadores').innerHTML = html;
}

// VariÃ¡vel para armazenar instÃ¢ncias dos grÃ¡ficos
let chartInstances = {
    trimestre: null,
    mes: null,
    runway: null,
    freeCashFlow: null
};

// FunÃ§Ã£o principal para carregar todos os indicadores
async function loadAllIndicadores() {
    const yearSelect = document.getElementById('yearSelectIndicadores');
    if (!yearSelect) return;
    const year = yearSelect.value;

    const loadingDiv = document.getElementById('loadingIndicadores');
    const errorDiv = document.getElementById('errorIndicadores');
    const graphsDiv = document.getElementById('indicadoresGraphsContent');
    const tableDiv = document.getElementById('indicadoresTableContent');

    loadingDiv.style.display = 'block';
    errorDiv.style.display = 'none';
    graphsDiv.style.display = 'none';
    tableDiv.style.display = 'none';

    try {
        // âœ… MESMA LÃ“GICA DO loadDREData
        const dadosAno = rawData.filter(row => {
            const dateField = row.lastAcquittanceDate || row.dueDate || row['financialEvent.competenceDate'];
            if (!dateField) return false;
            return dateField.startsWith(year);
        });

        const meses = ['Janeiro','Fevereiro','MarÃ§o','Abril','Maio','Junho',
                       'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

        const dreMensal = {};
        meses.forEach(mes => {
            dreMensal[mes] = {
                '3.01':0,'3.02':0,'3.03':0,'3.04':0,
                '4.01':0,'4.02':0,'4.03':0,
                '5.01':0,'5.02':0,'5.03':0,
                '6.01':0,'6.02':0,'6.03':0,'6.04':0,'6.05':0,'6.06':0,'6.07':0,
                '7.01':0,'7.02':0,'7.03':0,'7.04':0,'7.05':0,'7.06':0,'7.07':0,'7.08':0,
                '8.03':0,'8.04':0,'8.05':0,
                '9.01':0,'9.02':0,
                '1.01':0,'1.02':0
            };
        });

        dadosAno.forEach(row => {
            try {
                const categoria = row['categoriesRatio.category'];
                if (!categoria) return;
                const valor = parseFloat(row.total) || 0;
                if (valor === 0) return;
                let dataCompetencia = row.lastAcquittanceDate || row['financialEvent.competenceDate'] || row.dueDate;
                if (!dataCompetencia) return;
                const dateObj = new Date(dataCompetencia.split('T')[0] + 'T00:00:00');
                if (isNaN(dateObj.getTime())) return;
                const mesIndex = dateObj.getMonth();
                const mes = meses[mesIndex];
                if (!dreMensal[mes]) return;
                const codigoMatch = categoria.match(/^(\d+\.\d+)/);
                if (codigoMatch) {
                    const codigo = codigoMatch[1];
                    if (dreMensal[mes][codigo] !== undefined) {
                        dreMensal[mes][codigo] += Math.abs(valor);
                    }
                }
            } catch(err) {}
        });

        const dreCalculado = {};
        meses.forEach(mes => {
            dreCalculado[mes] = { ...dreMensal[mes] };
            const d = dreCalculado[mes];

            d.A = d['3.01'];
            d.B = d['4.01'] + d['4.02'] + d['4.03'];
            d.C = d.A - d.B;
            d.D = d['5.01'] + d['5.02'] + d['5.03'];
            d.E = d.C - d.D;
            d.F = d.A !== 0 ? (d.E / d.A) * 100 : 0;

            const receitasFixas   = (d['6.04'] || 0) * -1;
            const despesasFixas   = (d['6.01']||0)+(d['6.02']||0)+(d['6.03']||0)+(d['6.05']||0)+(d['6.06']||0)+(d['6.07']||0);
            d.H = despesasFixas + receitasFixas;

            const receitasVariaveis = (d['7.04'] || 0) * -1;
            const despesasVariaveis = (d['7.01']||0)+(d['7.02']||0)+(d['7.03']||0)+(d['7.05']||0)+(d['7.06']||0)+(d['7.07']||0)+(d['7.08']||0);
            d.I = despesasVariaveis + receitasVariaveis;

            d.G = d.H + d.I;
            d.K = (d['3.02']||0) + (d['3.03']||0) + (d['3.04']||0);
            d.L = (d['8.03']||0) + (d['8.04']||0) + (d['8.05']||0);
            d.J = d.K - d.L;
            d.M = d.E - d.G + d.J;
            d.N = d.A !== 0 ? (d.M / d.A) * 100 : 0;
            d.O = (d['9.01']||0) - Math.abs(d['9.02']||0);
            d.P = d.M + d.O;
            d.Q = (d['1.01']||0) + (d['1.02']||0);
            d.R = d.P - d.Q;
        });

        // GrÃ¡ficos
        criarGraficoSaldoMes(meses, dreCalculado, year);
        criarGraficoSaldoTrimestre(meses, dreCalculado, year);
        criarGraficoRunwayIndicadores(meses, dreCalculado);
        criarGraficoFreeCashFlowIndicadores(meses, dreCalculado);

        // Tabela
        criarTabelaIndicadoresDRE(meses, dreCalculado);

        graphsDiv.style.display = 'block';
        tableDiv.style.display = 'block';
        loadingDiv.style.display = 'none';

    } catch(error) {
        console.error('Erro ao carregar indicadores:', error);
        loadingDiv.style.display = 'none';
        errorDiv.style.display = 'block';
        errorDiv.textContent = 'Erro ao carregar indicadores: ' + error.message;
    }
}


function criarGraficoSaldoMes(meses, dreCalculado, year) {
    const labels = [];
    const valores = [];

    meses.forEach((mes, idx) => {
        const saldo = dreCalculado[mes].R || 0;
        if (saldo !== 0) {
            const numMes = String(idx + 1).padStart(2, '0');
            labels.push(`${numMes}/${year}`);
            valores.push(saldo);
        }
    });

    const cores = valores.map(v => v >= 0 ? 'rgba(74, 222, 128, 0.8)' : 'rgba(248, 113, 113, 0.8)');
    const borderCores = valores.map(v => v >= 0 ? '#4ade80' : '#f87171');

    const ctx = document.getElementById('chartMes');
    if (!ctx) return;
    if (ctx._chartInstance) ctx._chartInstance.destroy();

    ctx._chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Saldo Mensal (R$)',
                data: valores,
                backgroundColor: cores,
                borderColor: borderCores,
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#e2e8f0' } },
                datalabels: {
                    color: '#ffffff',
                    font: { weight: 'bold', size: 11 },
                    formatter: v => formatCompactValue(v, 'R$')
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#e2e8f0', callback: v => formatCompactValue(v, 'R$') }
                },
                x: { grid: { display: false }, ticks: { color: '#e2e8f0' } }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function criarGraficoSaldoTrimestre(meses, dreCalculado, year) {
    const trimestres = { 'Q1': 0, 'Q2': 0, 'Q3': 0, 'Q4': 0 };
    meses.forEach((mes, idx) => {
        const q = 'Q' + (Math.floor(idx / 3) + 1);
        trimestres[q] += dreCalculado[mes].R || 0;
    });

    const labels = Object.keys(trimestres).map(q => `${q} ${year}`);
    const valores = Object.values(trimestres);
    const cores = valores.map(v => v >= 0 ? 'rgba(74, 222, 128, 0.8)' : 'rgba(248, 113, 113, 0.8)');
    const borderCores = valores.map(v => v >= 0 ? '#4ade80' : '#f87171');

    const ctx = document.getElementById('chartTrimestre');
    if (!ctx) return;
    if (ctx._chartInstance) ctx._chartInstance.destroy();

    ctx._chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Saldo Trimestral (R$)',
                data: valores,
                backgroundColor: cores,
                borderColor: borderCores,
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#e2e8f0' } },
                datalabels: {
                    color: '#ffffff',
                    font: { weight: 'bold', size: 11 },
                    formatter: v => formatCompactValue(v, 'R$')
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#e2e8f0', callback: v => formatCompactValue(v, 'R$') }
                },
                x: { grid: { display: false }, ticks: { color: '#e2e8f0' } }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function criarGraficoRunwayIndicadores(meses, dreCalculado) {
    const labels = [];
    const valores = [];

    let saldoAcumulado = 0;
    meses.forEach(mes => {
        saldoAcumulado += dreCalculado[mes].R || 0;
        const despesaMensal = dreCalculado[mes].G || 0;
        const runway = despesaMensal !== 0 ? Math.abs(saldoAcumulado / despesaMensal) : 0;
        if (dreCalculado[mes].A !== 0 || dreCalculado[mes].R !== 0) {
            labels.push(mes.substring(0, 3));
            valores.push(parseFloat(runway.toFixed(1)));
        }
    });

    const pontoCores = valores.map(v => v <= 3 ? '#f87171' : v <= 6 ? '#fbbf24' : '#4ade80');

    const ctx = document.getElementById('chartRunway');
    if (!ctx) return;
    if (ctx._chartInstance) ctx._chartInstance.destroy();

    ctx._chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Runway (meses)',
                data: valores,
                borderColor: '#60a5fa',
                backgroundColor: 'rgba(96,165,250,0.15)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: pontoCores,
                pointBorderColor: pontoCores,
                pointRadius: 6,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#e2e8f0' } },
                datalabels: {
                    color: '#ffffff',
                    font: { weight: 'bold', size: 11 },
                    formatter: v => v + 'x'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#e2e8f0', callback: v => v + ' meses' }
                },
                x: { grid: { display: false }, ticks: { color: '#e2e8f0' } }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function criarGraficoFreeCashFlowIndicadores(meses, dreCalculado) {
    const labels = [];
    const valores = [];

    meses.forEach((mes, idx) => {
        const ebitda = dreCalculado[mes].M || 0;
        const capex  = Math.abs(dreCalculado[mes]['6.06'] || 0);
        const fcf    = ebitda - capex;
        if (dreCalculado[mes].A !== 0 || ebitda !== 0) {
            const numMes = String(idx + 1).padStart(2, '0');
            labels.push(`${numMes}`);
            valores.push(fcf);
        }
    });

    const cores = valores.map(v => v >= 0 ? 'rgba(74, 222, 128, 0.8)' : 'rgba(248, 113, 113, 0.8)');
    const borderCores = valores.map(v => v >= 0 ? '#4ade80' : '#f87171');

    const ctx = document.getElementById('chartFreeCashFlow');
    if (!ctx) return;
    if (ctx._chartInstance) ctx._chartInstance.destroy();

    ctx._chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Free Cash Flow (R$)',
                data: valores,
                backgroundColor: cores,
                borderColor: borderCores,
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#e2e8f0' } },
                datalabels: {
                    color: '#ffffff',
                    font: { weight: 'bold', size: 11 },
                    formatter: v => formatCompactValue(v, 'R$')
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#e2e8f0', callback: v => formatCompactValue(v, 'R$') }
                },
                x: { grid: { display: false }, ticks: { color: '#e2e8f0' } }
            }
        },
        plugins: [ChartDataLabels]
    });
}


// FunÃ§Ã£o para inicializar pÃ¡gina de indicadores
function initIndicadoresPage() {
    loadAllIndicadores();
}

// ========== SIMULADOR DE FLUXO DE CAIXA ==========

function populateFiltersSimFluxo() {
  const statusSet = new Set();
  const costCenterSet = new Set();
  const categorySet = new Set();
  const negotiatorSet = new Set();
  
  rawData.forEach(row => {
    if (row.status) statusSet.add(row.status);
    
    // ALTERADO: usar notaÃ§Ã£o de string ao invÃ©s de objeto
    const costCenter = row['Centro_de_Custo_Unificado'];
    if (costCenter) costCenterSet.add(costCenter);
    
    // ALTERADO: usar notaÃ§Ã£o de string ao invÃ©s de objeto
    const category = row['categoriesRatio.category'];
    if (category) categorySet.add(category);
    
    // ALTERADO: usar notaÃ§Ã£o de string ao invÃ©s de objeto
    const negotiator = row['financialEvent.negotiator.name'];
    if (negotiator) negotiatorSet.add(negotiator);
  });
  
  populateSelect('statusFilterSimFluxo', statusSet, true);
  populateSelect('costCenterFilterSimFluxo', costCenterSet);
  populateSelect('categoryFilterSimFluxo', categorySet);
  populateSelect('negotiatorFilterSimFluxo', negotiatorSet);
}


function getFilteredDataSimFluxo() {
  const statusFilter = Array.from(document.getElementById('statusFilterSimFluxo').selectedOptions).map(opt => opt.value).filter(v => v !== '');
  const costCenterFilter = Array.from(document.getElementById('costCenterFilterSimFluxo').selectedOptions).map(opt => opt.value).filter(v => v !== '');
  const categoryFilter = Array.from(document.getElementById('categoryFilterSimFluxo').selectedOptions).map(opt => opt.value).filter(v => v !== '');
  const negotiatorFilter = Array.from(document.getElementById('negotiatorFilterSimFluxo').selectedOptions).map(opt => opt.value).filter(v => v !== '');
  const startMonth = document.getElementById('startMonthSimFluxo').value;
  const endMonth = document.getElementById('endMonthSimFluxo').value;
  
  return rawData.filter(row => {
    if (statusFilter.length > 0 && !statusFilter.includes(row.status)) return false;
    
    // ALTERADO: usar notaÃ§Ã£o de string ao invÃ©s de objeto
    if (costCenterFilter.length > 0 && !costCenterFilter.includes(row['Centro_de_Custo_Unificado'])) return false;
    
    // ALTERADO: usar notaÃ§Ã£o de string ao invÃ©s de objeto
    if (categoryFilter.length > 0 && !categoryFilter.includes(row['categoriesRatio.category'])) return false;
    
    // ALTERADO: usar notaÃ§Ã£o de string ao invÃ©s de objeto
    if (negotiatorFilter.length > 0 && !negotiatorFilter.includes(row['financialEvent.negotiator.name'])) return false;
    
    const dateToCheck = getDateForRow(row, currentDateTypeSimFluxo);
    if (!isDateInRange(dateToCheck, startMonth, endMonth)) return false;
    
    return true;
  });
}


function clearDateRangeSimFluxo() {
  document.getElementById('startMonthSimFluxo').value = '';
  document.getElementById('endMonthSimFluxo').value = '';
  updateSimuladorFluxoPage();
}

function updateSimuladorFluxoPage() {
  const filteredData = getFilteredDataSimFluxo();
  buildSimuladorFluxoTable(filteredData);
  buildSimuladorConsolidadoTables();
  buildSimuladorConsolidadoResultadoLiquido();
}



function buildSimuladorFluxoTable(data) {
  const monthlyData = {};
  const categorias = new Set();
  const months = new Set();

  const isFirstLoad = Object.keys(simuladorFluxoOriginalData).length === 0;

  data.forEach(row => {
    const dateStr = getDateForRow(row, currentDateTypeSimFluxo);
    if (!dateStr) return;

    const monthKey = getYearMonthFromDate(dateStr);
    if (!monthKey) return;

    const categoria = row['categoriesRatio.category'] || 'Sem categoria';
    const tipo = row.tipo;

    months.add(monthKey);
    categorias.add(categoria);

    const key = `${tipo}-${categoria}-${monthKey}`;

    if (!monthlyData[key]) {
      monthlyData[key] = { receita: 0, despesa: 0 };
    }

    if (tipo === 'Receita') {
      monthlyData[key].receita += (row.total || 0);
    } else if (tipo === 'Despesa') {
      monthlyData[key].despesa += (row.total || 0);
    }

    if (isFirstLoad) {
      if (!simuladorFluxoOriginalData[key]) {
        simuladorFluxoOriginalData[key] = { receita: 0, despesa: 0 };
      }

      if (tipo === 'Receita') {
        simuladorFluxoOriginalData[key].receita += (row.total || 0);
      } else if (tipo === 'Despesa') {
        simuladorFluxoOriginalData[key].despesa += (row.total || 0);
      }
    }
  });

  const sortedMonths = Array.from(months).sort();
  const sortedCategorias = Array.from(categorias).sort();

  let html = '<thead><tr style="background: #667eea; color: white;">';
  html += '<th style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: #667eea; z-index: 2;">Tipo</th>';
  html += '<th style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 100px; background: #667eea; z-index: 2;">Categoria</th>';

  sortedMonths.forEach(month => {
    const [year, mon] = month.split('-');
    const monthLabel = `${mon}/${year}`;
    html += `<th colspan="3" style="padding: 12px; border: 1px solid #ddd; text-align: center; white-space: nowrap; min-width: 240px;">${monthLabel}</th>`;
  });

  html += '<th colspan="3" style="padding: 12px; border: 1px solid #ddd; text-align: center; background: #5a6fd8; font-weight: bold;">TOTAL</th>';
  html += '</tr>';

  html += '<tr style="background: #7c8fe9; color: white; font-size: 13px;">';
  html += '<th colspan="2" style="padding: 8px; border: 1px solid #ddd;"></th>';
  sortedMonths.forEach(() => {
    html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Real</th>';
    html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Sim.</th>';
    html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">%</th>';
  });
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center; background: #6b7ee0;">Real</th>';
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center; background: #6b7ee0;">Sim.</th>';
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center; background: #6b7ee0;">%</th>';
  html += '</tr></thead><tbody>';

  let receitaTotalReal = 0;
  let receitaTotalSimulado = 0;
  let despesaTotalReal = 0;
  let despesaTotalSimulado = 0;

  // SEÃ‡ÃƒO RECEITAS
  html += '<tr style="background: #e8f5e9;">';
  html += `<td colspan="${sortedMonths.length * 3 + 3}" style="padding: 10px; border: 1px solid #ddd; font-weight: bold; color: #2e7d32;">ðŸ“ˆ CONTAS A RECEBER</td>`;
  html += '</tr>';

  sortedCategorias.forEach(categoria => {
    let hasReceitaData = false;
    let totalCategoriaReal = 0;
    let totalCategoriaSimulado = 0;

    let rowHtml = `<tr>`;
    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: white; z-index: 1;min-width: 200px;"></td>`;
    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 100px; background: white; z-index: 1; min-width: 200px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;" title="${categoria}">${categoria}</td>`;

    sortedMonths.forEach(month => {
      const key = `Receita-${categoria}-${month}`;
      const valorReal = simuladorFluxoOriginalData[key] ? simuladorFluxoOriginalData[key].receita : 0;
      let valorSimulado = valorReal;
      if (simuladorFluxoEditedData[key] && simuladorFluxoEditedData[key].receita !== undefined) {
        valorSimulado = simuladorFluxoEditedData[key].receita;
      }
      const percentual = valorReal !== 0 ? ((valorSimulado - valorReal) / valorReal) * 100 : 0;

      if (valorReal !== 0) {
        hasReceitaData = true;
        totalCategoriaReal += valorReal;
        totalCategoriaSimulado += valorSimulado;
      }

      const bgEditableSimulado = simuladorFluxoEditedData[key] ? '#fffde7' : '';

      rowHtml += `<td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: #2e7d32; font-weight: 500;">${valorReal !== 0 ? formatCurrency(valorReal) : '-'}</td>`;
      rowHtml += `<td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: #2e7d32; cursor: pointer; background: ${bgEditableSimulado}; transition: background 0.2s; font-weight: 600;" 
        onmouseover="this.style.background='#fff9c4'" 
        onmouseout="this.style.background='${bgEditableSimulado}'" 
        onclick="editCellSimFluxoReceita('${key}', ${valorSimulado}, this)">${valorSimulado !== 0 ? formatCurrency(valorSimulado) : '-'}</td>`;

      const corPercentual = percentual < 0 ? '#ff6b6b' : percentual > 0 ? '#51cf66' : '#999';
      rowHtml += `<td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: ${corPercentual}; font-weight: 600;">${Math.abs(percentual).toFixed(1)}%</td>`;
    });

    const totalPercentualCategoria = totalCategoriaReal !== 0 ? ((totalCategoriaSimulado - totalCategoriaReal) / totalCategoriaReal) * 100 : 0;
    const corTotalCategoria = totalPercentualCategoria < 0 ? '#ff6b6b' : totalPercentualCategoria > 0 ? '#51cf66' : '#999';

    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #f5f5f5; font-weight: bold; color: #2e7d32;">${totalCategoriaReal !== 0 ? formatCurrency(totalCategoriaReal) : '-'}</td>`;
    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #f5f5f5; font-weight: bold; color: #2e7d32;">${totalCategoriaSimulado !== 0 ? formatCurrency(totalCategoriaSimulado) : '-'}</td>`;
    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #f5f5f5; font-weight: bold; color: ${corTotalCategoria};">${Math.abs(totalPercentualCategoria).toFixed(1)}%</td>`;
    rowHtml += `</tr>`;

    if (hasReceitaData) {
      html += rowHtml;
      receitaTotalReal += totalCategoriaReal;
      receitaTotalSimulado += totalCategoriaSimulado;
    }
  });

  // SEÃ‡ÃƒO DESPESAS
  html += '<tr style="background: #ffebee;">';
  html += `<td colspan="${sortedMonths.length * 3 + 3}" style="padding: 10px; border: 1px solid #ddd; font-weight: bold; color: #c62828;">ðŸ“‰ A PAGAR</td>`;
  html += '</tr>';

  sortedCategorias.forEach(categoria => {
    let hasDespesaData = false;
    let totalCategoriaReal = 0;
    let totalCategoriaSimulado = 0;

    let rowHtml = `<tr>`;
    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 0; background: white; z-index: 1;min-width: 200px;"></td>`;
    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; position: sticky; left: 100px; background: white; z-index: 1; min-width: 200px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;" title="${categoria}">${categoria}</td>`;

    sortedMonths.forEach(month => {
      const key = `Despesa-${categoria}-${month}`;
      const valorReal = simuladorFluxoOriginalData[key] ? simuladorFluxoOriginalData[key].despesa : 0;
      let valorSimulado = valorReal;
      if (simuladorFluxoEditedData[key] && simuladorFluxoEditedData[key].despesa !== undefined) {
        valorSimulado = simuladorFluxoEditedData[key].despesa;
      }
      const percentual = valorReal !== 0 ? ((valorSimulado - valorReal) / valorReal) * 100 : 0;

      if (valorReal !== 0) {
        hasDespesaData = true;
        totalCategoriaReal += valorReal;
        totalCategoriaSimulado += valorSimulado;
      }

      const bgEditableSimulado = simuladorFluxoEditedData[key] ? '#fffde7' : '';

      rowHtml += `<td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: #c62828; font-weight: 500;">${valorReal !== 0 ? formatCurrency(valorReal) : '-'}</td>`;
      rowHtml += `<td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: #c62828; cursor: pointer; background: ${bgEditableSimulado}; transition: background 0.2s; font-weight: 600;" 
        onmouseover="this.style.background='#fff9c4'" 
        onmouseout="this.style.background='${bgEditableSimulado}'" 
        onclick="editCellSimFluxoDespesa('${key}', ${valorSimulado}, this)">${valorSimulado !== 0 ? formatCurrency(valorSimulado) : '-'}</td>`;

      const corPercentual = percentual < 0 ? '#ff6b6b' : percentual > 0 ? '#51cf66' : '#999';
      rowHtml += `<td style="padding: 8px; border: 1px solid #ddd; text-align: right; color: ${corPercentual}; font-weight: 600;">${Math.abs(percentual).toFixed(1)}%</td>`;
    });

    const totalPercentualCategoria = totalCategoriaReal !== 0 ? ((totalCategoriaSimulado - totalCategoriaReal) / totalCategoriaReal) * 100 : 0;
    const corTotalCategoria = totalPercentualCategoria < 0 ? '#ff6b6b' : totalPercentualCategoria > 0 ? '#51cf66' : '#999';

    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #f5f5f5; font-weight: bold; color: #c62828;">${totalCategoriaReal !== 0 ? formatCurrency(totalCategoriaReal) : '-'}</td>`;
    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #f5f5f5; font-weight: bold; color: #c62828;">${totalCategoriaSimulado !== 0 ? formatCurrency(totalCategoriaSimulado) : '-'}</td>`;
    rowHtml += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; background: #f5f5f5; font-weight: bold; color: ${corTotalCategoria};">${Math.abs(totalPercentualCategoria).toFixed(1)}%</td>`;
    rowHtml += `</tr>`;

    if (hasDespesaData) {
      html += rowHtml;
      despesaTotalReal += totalCategoriaReal;
      despesaTotalSimulado += totalCategoriaSimulado;
    }
  });

  // Totais mensais e percentual por mÃªs
  const totalReceitaPorMes = {};
  const totalDespesaPorMes = {};
  const resultadoLiquidoPorMes = {};

  sortedMonths.forEach(month => {
    totalReceitaPorMes[month] = 0;
    totalDespesaPorMes[month] = 0;
    sortedCategorias.forEach(categoria => {
      const keyReceita = `Receita-${categoria}-${month}`;
      const valReceita = simuladorFluxoEditedData[keyReceita]?.receita ?? simuladorFluxoOriginalData[keyReceita]?.receita ?? 0;
      totalReceitaPorMes[month] += valReceita;

      const keyDespesa = `Despesa-${categoria}-${month}`;
      const valDespesa = simuladorFluxoEditedData[keyDespesa]?.despesa ?? simuladorFluxoOriginalData[keyDespesa]?.despesa ?? 0;
      totalDespesaPorMes[month] += valDespesa;
    });
    resultadoLiquidoPorMes[month] = totalReceitaPorMes[month] - totalDespesaPorMes[month];
  });

  // Linha total receita mensal
  let rowTotalReceita = `<tr style="background: #c8e6c9; font-weight: bold;">
  <td colspan="2" style="padding: 12px; border: 1px solid #ddd; text-align: right;">TOTAL DE A RECEBER</td>`;

  sortedMonths.forEach(month => {
    const valReal = sortedCategorias.reduce((acc, cat) => {
      const k = `Receita-${cat}-${month}`;
      return acc + (simuladorFluxoOriginalData[k]?.receita ?? 0);
    }, 0);
    const valSim = totalReceitaPorMes[month];
    const perc = valReal !== 0 ? ((valSim - valReal) / valReal) * 100 : 0;
    const corPerc = perc < 0 ? '#ff6b6b' : perc > 0 ? '#51cf66' : '#999';

    rowTotalReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32;">${formatCurrency(valReal)}</td>`;
    rowTotalReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32;">${formatCurrency(valSim)}</td>`;
    rowTotalReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corPerc};">${perc.toFixed(1)}%</td>`;
  });

  // Total geral
  const percTotalReceita = receitaTotalReal !== 0 ? ((receitaTotalSimulado - receitaTotalReal) / receitaTotalReal) * 100 : 0;
  const corTotalReceita = percTotalReceita < 0 ? '#ff6b6b' : percTotalReceita > 0 ? '#51cf66' : '#999';

  rowTotalReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32;">${formatCurrency(receitaTotalReal)}</td>`;
  rowTotalReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32;">${formatCurrency(receitaTotalSimulado)}</td>`;
  rowTotalReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corTotalReceita};">${percTotalReceita.toFixed(1)}%</td></tr>`;

  // Linha total despesa mensal
  let rowTotalDespesa = `<tr style="background: #ffcdd2; font-weight: bold;">
  <td colspan="2" style="padding: 12px; border: 1px solid #ddd; text-align: right;">TOTAL A PAGAR</td>`;

  sortedMonths.forEach(month => {
    const valReal = sortedCategorias.reduce((acc, cat) => {
      const k = `Despesa-${cat}-${month}`;
      return acc + (simuladorFluxoOriginalData[k]?.despesa ?? 0);
    }, 0);
    const valSim = totalDespesaPorMes[month];
    const perc = valReal !== 0 ? ((valSim - valReal) / valReal) * 100 : 0;
    const corPerc = perc < 0 ? '#ff6b6b' : perc > 0 ? '#51cf66' : '#999';

    rowTotalDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #c62828;">${formatCurrency(valReal)}</td>`;
    rowTotalDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #c62828;">${formatCurrency(valSim)}</td>`;
    rowTotalDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corPerc};">${perc.toFixed(1)}%</td>`;
  });

  const percTotalDespesa = despesaTotalReal !== 0 ? ((despesaTotalSimulado - despesaTotalReal) / despesaTotalReal) * 100 : 0;
  const corTotalDespesa = percTotalDespesa < 0 ? '#ff6b6b' : percTotalDespesa > 0 ? '#51cf66' : '#999';

  rowTotalDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #c62828;">${formatCurrency(despesaTotalReal)}</td>`;
  rowTotalDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #c62828;">${formatCurrency(despesaTotalSimulado)}</td>`;
  rowTotalDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corTotalDespesa};">${percTotalDespesa.toFixed(1)}%</td></tr>`;

  // Linha Resultado lÃ­quido mensal
  let rowResultadoLiquido = `<tr style="background: #d1ffd1; font-weight: bold;">
  <td colspan="2" style="padding: 12px; border: 1px solid #ddd; text-align: right;">TOTAL DO PERÃODO</td>`;

  sortedMonths.forEach(month => {
    const valReal = sortedCategorias.reduce((acc, cat) => {
      const kReceita = `Receita-${cat}-${month}`;
      const kDespesa = `Despesa-${cat}-${month}`;
      const rReal = (simuladorFluxoOriginalData[kReceita]?.receita ?? 0) - (simuladorFluxoOriginalData[kDespesa]?.despesa ?? 0);
      return acc + rReal;
    }, 0);

    const valSim = resultadoLiquidoPorMes[month];
    const perc = valReal !== 0 ? ((valSim - valReal) / Math.abs(valReal)) * 100 : 0;
    const corPerc = perc < 0 ? '#ff6b6b' : perc > 0 ? '#51cf66' : '#999';
    const corVal = valSim >= 0 ? '#2e7d32' : '#c62828';
    
    const corResultadoReal = valReal >= 0 ? '#2e7d32' : '#c62828'; // cor azul ou verde se positivo real
    const corResultadoSim = valSim >= 0 ? '#2e7d32' : '#c62828'; // cor azul ou vermelha se positivo/negativo simulado

    rowResultadoLiquido += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corResultadoReal};">${formatCurrency(valReal)}</td>`;
    rowResultadoLiquido += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corResultadoSim};">${formatCurrency(valSim)}</td>`;
    rowResultadoLiquido += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corPerc};">${perc.toFixed(1)}%</td>`;
  });

  const resultadoLiquidoRealTotal = receitaTotalReal - despesaTotalReal;
  const resultadoLiquidoSimTotal = receitaTotalSimulado - despesaTotalSimulado;
  const percResultadoTotal = resultadoLiquidoRealTotal !== 0 ? ((resultadoLiquidoSimTotal - resultadoLiquidoRealTotal) / Math.abs(resultadoLiquidoRealTotal)) * 100 : 0;
  const corPercTotal = percResultadoTotal < 0 ? '#ff6b6b' : percResultadoTotal > 0 ? '#51cf66' : '#999';
  const corResultadoTotal = resultadoLiquidoSimTotal >= 0 ? '#2e7d32' : '#c62828';
  
  const corResultadoReal = resultadoLiquidoRealTotal >= 0 ? '#2e7d32' : '#c62828'; // cor azul ou verde se positivo real
  const corResultadoSim = resultadoLiquidoSimTotal >= 0 ? '#2e7d32' : '#c62828'; // cor azul ou vermelha se positivo/negativo simulado

  rowResultadoLiquido += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corResultadoReal};">${formatCurrency(resultadoLiquidoRealTotal)}</td>`;
  rowResultadoLiquido += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corResultadoSim};">${formatCurrency(resultadoLiquidoSimTotal)}</td>`;
  rowResultadoLiquido += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${corPercTotal};">${percResultadoTotal.toFixed(1)}%</td></tr>`;

  html += rowTotalReceita + rowTotalDespesa + rowResultadoLiquido;

  html += '</tbody>';
  document.getElementById('tableSimuladorFluxoDetalhado').innerHTML = html;
}




// FunÃ§Ã£o para editar cÃ©lula de RECEITA
function editCellSimFluxoReceita(key, currentValue, cell) {
  const novoValor = prompt(
    `Editar A RECEBER\n\nValor atual: ${formatCurrency(currentValue)}\n\nDigite o novo valor:`,
    currentValue.toFixed(2)
  );
  
  if (novoValor === null) return; // Cancelou
  
  const novoValorNum = parseFloat(novoValor.replace(',', '.'));
  
  if (isNaN(novoValorNum)) {
    alert('Valor invÃ¡lido!');
    return;
  }
  
  // âœ… CORREÃ‡ÃƒO: garantir que o objeto existe antes de aplicar spread
  if (!simuladorFluxoEditedData[key]) {
    simuladorFluxoEditedData[key] = {};
  }
  
  // Atualizar apenas receita, preservando despesa se existir
  simuladorFluxoEditedData[key].receita = novoValorNum;
  
  // Atualizar cÃ©lula visualmente
  cell.style.color = '#2e7d32';
  cell.style.fontWeight = 'bold';
  cell.style.background = '#fffde7';
  cell.innerHTML = novoValorNum !== 0 ? formatCurrency(novoValorNum) : '-';
  
  // Atualizar onclick com novo valor
  cell.setAttribute('onclick', `editCellSimFluxoReceita('${key}', ${novoValorNum}, this)`);
  
  // Recalcular tabelas
  buildSimuladorConsolidadoTables();
  
  // Rebuild da tabela principal para atualizar totais
  const filteredData = getFilteredDataSimFluxo();
  buildSimuladorFluxoTable(filteredData);
}



// FunÃ§Ã£o para editar cÃ©lula de DESPESA
// FunÃ§Ã£o para editar cÃ©lula de DESPESA
// FunÃ§Ã£o para editar cÃ©lula de DESPESA
function editCellSimFluxoDespesa(key, currentValue, cell) {
  const novoValor = prompt(
    `Editar A Pagar\n\nValor atual: ${formatCurrency(currentValue)}\n\nDigite o novo valor:`,
    currentValue.toFixed(2)
  );
  
  if (novoValor === null) return; // Cancelou
  
  const novoValorNum = parseFloat(novoValor.replace(',', '.'));
  
  if (isNaN(novoValorNum)) {
    alert('Valor invÃ¡lido!');
    return;
  }
  
  // âœ… CORREÃ‡ÃƒO: garantir que o objeto existe antes de aplicar spread
  if (!simuladorFluxoEditedData[key]) {
    simuladorFluxoEditedData[key] = {};
  }
  
  // Atualizar apenas despesa, preservando receita se existir
  simuladorFluxoEditedData[key].despesa = novoValorNum;
  
  // Atualizar cÃ©lula visualmente
  cell.style.color = '#c62828';
  cell.style.fontWeight = 'bold';
  cell.style.background = '#fffde7';
  cell.innerHTML = novoValorNum !== 0 ? formatCurrency(novoValorNum) : '-';
  
  // Atualizar onclick com novo valor
  cell.setAttribute('onclick', `editCellSimFluxoDespesa('${key}', ${novoValorNum}, this)`);
  
  // Recalcular tabelas
  buildSimuladorConsolidadoTables();
  
  // Rebuild da tabela principal para atualizar totais
  const filteredData = getFilteredDataSimFluxo();
  buildSimuladorFluxoTable(filteredData);
}




function editCellSimFluxo(uniqueKey, currentReceita, currentDespesa, cell) {
  const saldoAtual = currentReceita - currentDespesa;
  
  const novoValor = prompt(
    `Editar valor do saldo\n\nValor atual: ${formatCurrency(saldoAtual)}\n\nA Receber: ${formatCurrency(currentReceita)}\nA Pagar: ${formatCurrency(currentDespesa)}\n\nDigite o novo SALDO (pode ser negativo):`,
    saldoAtual.toFixed(2)
  );
  
  if (novoValor === null) return; // Cancelou
  
  const novoSaldo = parseFloat(novoValor.replace(',', '.'));
  
  if (isNaN(novoSaldo)) {
    alert('Valor invÃ¡lido!');
    return;
  }
  
  // Ajustar receita ou despesa mantendo a proporÃ§Ã£o
  // Se novo saldo Ã© positivo, ajustar receita; se negativo, ajustar despesa
  let novaReceita = currentReceita;
  let novaDespesa = currentDespesa;
  
  const diferenca = novoSaldo - saldoAtual;
  
  if (diferenca >= 0) {
    // Aumentando receita
    novaReceita += diferenca;
  } else {
    // Aumentando despesa
    novaDespesa += Math.abs(diferenca);
  }
  
  // Armazenar valor editado
  simuladorFluxoEditedData[uniqueKey] = {
    receita: novaReceita,
    despesa: novaDespesa
  };
  
  // Atualizar cÃ©lula
  const color = novoSaldo >= 0 ? '#2e7d32' : '#d32f2f';
  cell.style.color = color;
  cell.style.fontWeight = 'bold';
  cell.style.background = '#fffde7';
  cell.innerHTML = novoSaldo !== 0 ? formatCurrency(novoSaldo) : '-';
  
  // Atualizar onclick com novos valores
  cell.setAttribute('onclick', `editCellSimFluxo('${uniqueKey}', ${novaReceita}, ${novaDespesa}, this)`);
  
  // Recalcular tabelas consolidadas
  buildSimuladorConsolidadoTables();
  
  // Rebuild da tabela principal para atualizar totais
  const filteredData = getFilteredDataSimFluxo();
  buildSimuladorFluxoTable(filteredData);
}

function buildSimuladorConsolidadoTables() {
    const filteredData = getFilteredDataSimFluxo();
    
    // Estrutura: { "mÃªs": { categoria: { receita, despesa } } }
    const dadosPorMes = {};
    
    filteredData.forEach(row => {
        const dateStr = getDateForRow(row, currentDateTypeSimFluxo);
        if (!dateStr) return;
        
        const monthKey = getYearMonthFromDate(dateStr);
        if (!monthKey) return;
        
        const categoria = row["categoriesRatio.category"] || "Sem categoria";
        const tipo = row.tipo;
        const key = `${tipo}-${categoria}-${monthKey}`;
        
        // Inicializar estrutura
        if (!dadosPorMes[monthKey]) {
            dadosPorMes[monthKey] = {};
        }
        if (!dadosPorMes[monthKey][categoria]) {
            dadosPorMes[monthKey][categoria] = { receita: 0, despesa: 0 };
        }
        
        // Somar valores (cada row.total sÃ³ deve ser contado UMA vez)
        if (tipo === "Receita") {
            dadosPorMes[monthKey][categoria].receita += (row.total || 0);
        } else if (tipo === "Despesa") {
            dadosPorMes[monthKey][categoria].despesa += (row.total || 0);
        }
    });
    
    // Agora agregar por mÃªs para as tabelas consolidadas
    const receitaRealPorMes = {};
    const receitaSimPorMes = {};
    const despesaRealPorMes = {};
    const despesaSimPorMes = {};
    
    Object.keys(dadosPorMes).forEach(monthKey => {
        receitaRealPorMes[monthKey] = 0;
        receitaSimPorMes[monthKey] = 0;
        despesaRealPorMes[monthKey] = 0;
        despesaSimPorMes[monthKey] = 0;
        
        Object.keys(dadosPorMes[monthKey]).forEach(categoria => {
            const keyReceita = `Receita-${categoria}-${monthKey}`;
            const keyDespesa = `Despesa-${categoria}-${monthKey}`;
            
            const receitaReal = dadosPorMes[monthKey][categoria].receita;
            const despesaReal = dadosPorMes[monthKey][categoria].despesa;
            
            // Real
            receitaRealPorMes[monthKey] += receitaReal;
            despesaRealPorMes[monthKey] += despesaReal;
            
            // Simulado (usa editado se existir, senÃ£o usa real)
            const receitaSim = simuladorFluxoEditedData[keyReceita]?.receita ?? receitaReal;
            const despesaSim = simuladorFluxoEditedData[keyDespesa]?.despesa ?? despesaReal;
            
            receitaSimPorMes[monthKey] += receitaSim;
            despesaSimPorMes[monthKey] += despesaSim;
        });
    });
    
    const sortedMonths = Object.keys(receitaRealPorMes).sort();
    
    // ===== TABELA CONSOLIDADO DE RECEITAS =====
    let htmlReceita = `<thead><tr style="background: #4facfe; color: white;">`;
    htmlReceita += `<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">MÃªs</th>`;
    htmlReceita += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Real</th>`;
    htmlReceita += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Sim.</th>`;
    htmlReceita += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">%</th>`;
    htmlReceita += `</tr></thead><tbody>`;
    
    let totalReceitaReal = 0;
    let totalReceitaSim = 0;
    
    if (sortedMonths.length > 0) {
        sortedMonths.forEach(month => {
            const [year, mon] = month.split('-');
            const receitaReal = receitaRealPorMes[month] || 0;
            const receitaSim = receitaSimPorMes[month] || 0;
            const variacao = receitaReal !== 0 ? ((receitaSim - receitaReal) / receitaReal) * 100 : 0;
            
            totalReceitaReal += receitaReal;
            totalReceitaSim += receitaSim;
            
            const colorVar = variacao >= 0 ? '#2e7d32' : '#d32f2f';
            const signVar = variacao >= 0 ? '+' : '';
            
            htmlReceita += `<tr>`;
            htmlReceita += `<td style="padding: 10px 12px; border: 1px solid #ddd; font-weight: 600;">${mon}/${year}</td>`;
            htmlReceita += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32;">${formatCurrency(receitaReal)}</td>`;
            htmlReceita += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: #2e7d32; font-weight: bold;">${formatCurrency(receitaSim)}</td>`;
            htmlReceita += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${colorVar}; font-weight: bold;">${signVar}${variacao.toFixed(2)}%</td>`;
            htmlReceita += `</tr>`;
        });
    } else {
        htmlReceita += `<tr><td colspan="4" style="padding: 20px; text-align: center; color: #999;">Nenhum dado disponÃ­vel</td></tr>`;
    }
    
    const variacaoReceitaTotal = totalReceitaReal !== 0 ? ((totalReceitaSim - totalReceitaReal) / totalReceitaReal) * 100 : 0;
    const colorTotalVarReceita = variacaoReceitaTotal >= 0 ? '#2e7d32' : '#d32f2f';
    const signTotalVarReceita = variacaoReceitaTotal >= 0 ? '+' : '';
    
    htmlReceita += `<tr style="background: #c8e6c9; font-weight: bold;">`;
    htmlReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: left;">TOTAL A RECEBER</td>`;
    htmlReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #1b5e20; font-size: 1.1em;">${formatCurrency(totalReceitaReal)}</td>`;
    htmlReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #1b5e20; font-size: 1.1em;">${formatCurrency(totalReceitaSim)}</td>`;
    htmlReceita += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${colorTotalVarReceita}; font-size: 1.1em;">${signTotalVarReceita}${variacaoReceitaTotal.toFixed(2)}%</td>`;
    htmlReceita += `</tr></tbody>`;
    
    document.getElementById('tableSimuladorReceitaConsolidado').innerHTML = htmlReceita;
    
    // ===== TABELA CONSOLIDADO DE DESPESAS =====
    let htmlDespesa = `<thead><tr style="background: #f5576c; color: white;">`;
    htmlDespesa += `<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">MÃªs</th>`;
    htmlDespesa += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Real</th>`;
    htmlDespesa += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Sim.</th>`;
    htmlDespesa += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">%</th>`;
    htmlDespesa += `</tr></thead><tbody>`;
    
    let totalDespesaReal = 0;
    let totalDespesaSim = 0;
    
    if (sortedMonths.length > 0) {
        sortedMonths.forEach(month => {
            const [year, mon] = month.split('-');
            const despesaReal = despesaRealPorMes[month] || 0;
            const despesaSim = despesaSimPorMes[month] || 0;
            const variacao = despesaReal !== 0 ? ((despesaSim - despesaReal) / despesaReal) * 100 : 0;
            
            totalDespesaReal += despesaReal;
            totalDespesaSim += despesaSim;
            
            const colorVar = variacao >= 0 ? '#d32f2f' : '#2e7d32';
            const signVar = variacao >= 0 ? '+' : '';
            
            htmlDespesa += `<tr>`;
            htmlDespesa += `<td style="padding: 10px 12px; border: 1px solid #ddd; font-weight: 600;">${mon}/${year}</td>`;
            htmlDespesa += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: #c62828;">${formatCurrency(despesaReal)}</td>`;
            htmlDespesa += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: #c62828; font-weight: bold;">${formatCurrency(despesaSim)}</td>`;
            htmlDespesa += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${colorVar}; font-weight: bold;">${signVar}${variacao.toFixed(2)}%</td>`;
            htmlDespesa += `</tr>`;
        });
    } else {
        htmlDespesa += `<tr><td colspan="4" style="padding: 20px; text-align: center; color: #999;">Nenhum dado disponÃ­vel</td></tr>`;
    }
    
    const variacaoDespesaTotal = totalDespesaReal !== 0 ? ((totalDespesaSim - totalDespesaReal) / totalDespesaReal) * 100 : 0;
    const colorTotalVarDespesa = variacaoDespesaTotal >= 0 ? '#d32f2f' : '#2e7d32';
    const signTotalVarDespesa = variacaoDespesaTotal >= 0 ? '+' : '';
    
    htmlDespesa += `<tr style="background: #ffcdd2; font-weight: bold;">`;
    htmlDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: left;">TOTAL A PAGAR</td>`;
    htmlDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #b71c1c; font-size: 1.1em;">${formatCurrency(totalDespesaReal)}</td>`;
    htmlDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: #b71c1c; font-size: 1.1em;">${formatCurrency(totalDespesaSim)}</td>`;
    htmlDespesa += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${colorTotalVarDespesa}; font-size: 1.1em;">${signTotalVarDespesa}${variacaoDespesaTotal.toFixed(2)}%</td>`;
    htmlDespesa += `</tr></tbody>`;
    
    document.getElementById('tableSimuladorDespesaConsolidado').innerHTML = htmlDespesa;
    
    // ===== TABELA RESULTADO LÃQUIDO (usando dados jÃ¡ calculados) =====
    buildSimuladorConsolidadoResultadoLiquido(receitaRealPorMes, receitaSimPorMes, despesaRealPorMes, despesaSimPorMes);
}

// Nova assinatura da funÃ§Ã£o com parÃ¢metros


// Nova assinatura da funÃ§Ã£o com parÃ¢metros
function buildSimuladorConsolidadoResultadoLiquido(receitaRealPorMes, receitaSimPorMes, despesaRealPorMes, despesaSimPorMes) {
    const sortedMonths = Object.keys(receitaRealPorMes).sort();
    
    let html = `<thead><tr style="background: #7b4397; color: white;">`;
    html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">MÃªs</th>`;
    html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Real</th>`;
    html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Sim.</th>`;
    html += `<th style="padding: 12px; border: 1px solid #ddd; text-align: right;">%</th>`;
    html += `</tr></thead><tbody>`;
    
    let totalResultadoReal = 0;
    let totalResultadoSim = 0;
    
    if (sortedMonths.length > 0) {
        sortedMonths.forEach(mesAno => {
            const [ano, mes] = mesAno.split('-');
            
            // CORREÃ‡ÃƒO: Usar os parÃ¢metros recebidos diretamente
            const receitaReal = receitaRealPorMes[mesAno] || 0;
            const receitaSim = receitaSimPorMes[mesAno] || 0;
            const despesaReal = despesaRealPorMes[mesAno] || 0;
            const despesaSim = despesaSimPorMes[mesAno] || 0;
            
            // Calcular resultado lÃ­quido
            const real = receitaReal - despesaReal;
            const sim = receitaSim - despesaSim;
            
            const variacao = (real !== 0) ? ((sim - real) / real * 100) : 0;
            
            totalResultadoReal += real;
            totalResultadoSim += sim;
            
            const corReal = real >= 0 ? '#2e7d32' : '#c62828';
            const corSim = sim >= 0 ? '#2e7d32' : '#c62828';
            const corVar = variacao >= 0 ? '#2e7d32' : '#d32f2f';
            const sinal = variacao >= 0 ? '+' : '';
            
            html += `<tr>`;
            html += `<td style="padding: 10px 12px; border: 1px solid #ddd; font-weight: 600;">${mes}/${ano}</td>`;
            html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${corReal};">${formatCurrency(real)}</td>`;
            html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${corSim}; font-weight: bold;">${formatCurrency(sim)}</td>`;
            html += `<td style="padding: 10px 12px; border: 1px solid #ddd; text-align: right; color: ${corVar}; font-weight: bold;">${sinal}${variacao.toFixed(2)}%</td>`;
            html += `</tr>`;
        });
    } else {
        html += `<tr><td colspan="4" style="padding:20px; text-align:center; color:#999;">Nenhum dado disponÃ­vel</td></tr>`;
    }
    
    // Total
    const variacaoTotal = (totalResultadoReal !== 0) ? ((totalResultadoSim - totalResultadoReal) / totalResultadoReal * 100) : 0;
    const colorTotalVar = variacaoTotal >= 0 ? '#2e7d32' : '#d32f2f';
    const signTotalVar = variacaoTotal >= 0 ? '+' : '';
    
    html += `<tr style="background: #e1bee7; font-weight: bold;">`;
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: left;">TOTAL DO PERÃODO</td>`;
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${totalResultadoReal >= 0 ? '#1b5e20' : '#b71c1c'}; font-size: 1.1em;">${formatCurrency(totalResultadoReal)}</td>`;
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${totalResultadoSim >= 0 ? '#1b5e20' : '#b71c1c'}; font-size: 1.1em;">${formatCurrency(totalResultadoSim)}</td>`;
    html += `<td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: ${colorTotalVar}; font-size: 1.1em;">${signTotalVar}${variacaoTotal.toFixed(2)}%</td>`;
    html += `</tr>`;
    
    html += `</tbody>`;
    
    document.getElementById('tableConsolidadoResultadoLiquidoSimulado').innerHTML = html;
}







function resetSimuladorFluxo() {
  if (confirm('Deseja realmente resetar todos os valores editados?')) {
    simuladorFluxoEditedData = {};
    simuladorFluxoOriginalData = {};
    updateSimuladorFluxoPage();
  }
}

function exportSimuladorFluxo() {
  const table = document.getElementById('tableSimuladorFluxoDetalhado');
  let csvContent = '';
  
  // ===== CONSTRUIR CABEÃ‡ALHO MANUALMENTE EM 2 LINHAS =====
  const headerRow1 = table.querySelector('thead tr:first-child');
  const monthHeaders = [];
  
  headerRow1.querySelectorAll('th').forEach((th, idx) => {
    const text = th.textContent.trim();
    const colspan = parseInt(th.getAttribute('colspan')) || 1;
    
    if (idx === 0) {
      monthHeaders.push({ text: 'Tipo', colspan: 1, isMonth: false });
    } else if (idx === 1) {
      monthHeaders.push({ text: 'Categoria', colspan: 1, isMonth: false });
    } else {
      monthHeaders.push({ text: text, colspan: colspan, isMonth: true });
    }
  });
  
  // LINHA 1 do cabeÃ§alho
  let line1 = [];
  monthHeaders.forEach(h => {
    line1.push(h.text);
    for (let i = 1; i < h.colspan; i++) {
      line1.push('');
    }
  });
  csvContent += line1.join(';') + '\n';
  
  // LINHA 2 do cabeÃ§alho
  let line2 = [];
  monthHeaders.forEach(h => {
    if (h.isMonth) {
      line2.push('Real');
      line2.push('Sim.');
      line2.push('%');
    } else {
      line2.push('');
    }
  });
  csvContent += line2.join(';') + '\n';
  
  // ===== DADOS DA TABELA =====
  table.querySelectorAll('tbody tr').forEach(tr => {
    const row = [];
    
    // Verificar se Ã© linha de TOTAL (tem colspan)
    const firstCell = tr.querySelector('td');
    const hasColspan = firstCell && parseInt(firstCell.getAttribute('colspan')) > 1;
    
    if (hasColspan) {
      // Ã‰ linha de TOTAL - adicionar cÃ©lula vazia para compensar o colspan
      const totalText = firstCell.textContent.trim().replace(/R\$\s*/g, '').replace(/\./g, '');
      row.push(''); // CÃ©lula vazia para "Tipo"
      row.push(totalText); // Texto do total em "Categoria"
      
      // Pegar os valores restantes (Real, Sim., % de cada mÃªs)
      tr.querySelectorAll('td:not(:first-child)').forEach(td => {
        let value = td.textContent.trim();
        // Remove R$ e pontos (separador de milhar), MANTÃ‰M vÃ­rgula decimal
        value = value.replace(/R\$\s*/g, '').replace(/\./g, '');
        row.push(value);
      });
    } else {
      // Linha normal de categoria
      tr.querySelectorAll('td').forEach(td => {
        let value = td.textContent.trim();
        // Remove R$ e pontos (separador de milhar), MANTÃ‰M vÃ­rgula decimal
        value = value.replace(/R\$\s*/g, '').replace(/\./g, '');
        row.push(value);
      });
    }
    
    csvContent += row.join(';') + '\n';
  });
  
  // Adicionar BOM UTF-8
  const BOM = '\uFEFF';
  const csvWithBOM = BOM + csvContent;
  
  // Download
  const blob = new Blob([csvWithBOM], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `simulador_fluxo_caixa_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
}


// ========== FUNÃ‡Ã•ES DE EXPORTAÃ‡ÃƒO CSV ==========

// 1. Exportar Fluxo de Caixa Detalhado por Categoria
// ========== FUNÃ‡Ã•ES DE EXPORTAÃ‡ÃƒO CSV (CORRIGIDAS) ==========

// 1. Exportar Fluxo de Caixa Detalhado por Categoria
// 1. Exportar Fluxo de Caixa Detalhado por Categoria
function exportFluxoDetalhado() {
    const table = document.getElementById('tableFluxoDetalhado');
    if (!table) {
        alert('Tabela nÃ£o encontrada!');
        return;
    }
    
    let csvContent = "";
    const BOM = "\uFEFF";
    
    // CABEÃ‡ALHO - Primeira linha (Tipo, Categoria, e Meses)
    const headerRow1 = table.querySelector('thead tr:first-child');
    if (headerRow1) {
        let line1 = [];
        headerRow1.querySelectorAll('th').forEach(th => {
            const text = th.textContent.trim();
            const colspan = parseInt(th.getAttribute('colspan')) || 1;
            line1.push('"' + text.replace(/"/g, '""') + '"');
            for (let i = 1; i < colspan; i++) {
                line1.push('""');
            }
        });
        csvContent += line1.join(";") + "\n";
    }
    
    // CABEÃ‡ALHO - Segunda linha (Real, Simulado, %)
    const headerRow2 = table.querySelector('thead tr:nth-child(2)');
    if (headerRow2) {
        let line2 = [];
        headerRow2.querySelectorAll('th').forEach(th => {
            let text = th.textContent.trim();
            text = text.replace(/"/g, '""');
            line2.push('"' + text + '"');
        });
        csvContent += line2.join(";") + "\n";
    }
    
    // DADOS DA TABELA
    table.querySelectorAll('tbody tr').forEach(tr => {
        let row = [];
        
        // Verificar se Ã© uma linha de total (TOTAL RECEITAS, TOTAL DESPESAS, RESULTADO LÃQUIDO)
        const firstCell = tr.querySelector('td');
        if (firstCell && firstCell.hasAttribute('colspan')) {
            const colspan = parseInt(firstCell.getAttribute('colspan'));
            const text = firstCell.textContent.trim();
            
            // Se colspan = 2, significa que ocupa as colunas "Tipo" e "Categoria"
            if (colspan === 2) {
                // Adicionar cÃ©lula vazia para "Tipo"
                row.push('""');
                // Adicionar o texto na cÃ©lula "Categoria"
                let cleanText = text.replace(/\u00A0/g, " ");
                cleanText = cleanText.replace(/R\$/g, "").replace(/\./g, "").replace(/%/g, "");
                cleanText = cleanText.replace(/"/g, '""');
                row.push('"' + cleanText + '"');
                
                // Processar as outras cÃ©lulas normalmente
                let skipFirst = true;
                tr.querySelectorAll('td').forEach(td => {
                    if (skipFirst) {
                        skipFirst = false;
                        return; // Pula a primeira cÃ©lula (jÃ¡ processada)
                    }
                    let value = td.textContent.trim();
                    value = value.replace(/\u00A0/g, " ");
                    value = value.replace(/R\$/g, "").replace(/\./g, "").replace(/%/g, "");
                    value = value.replace(/"/g, '""');
                    row.push('"' + value + '"');
                });
            } else {
                // Para outros colspans, processar normalmente
                tr.querySelectorAll('td').forEach(td => {
                    let value = td.textContent.trim();
                    value = value.replace(/\u00A0/g, " ");
                    value = value.replace(/R\$/g, "").replace(/\./g, "").replace(/%/g, "");
                    value = value.replace(/"/g, '""');
                    row.push('"' + value + '"');
                });
            }
        } else {
            // Linha normal (sem colspan)
            tr.querySelectorAll('td').forEach(td => {
                let value = td.textContent.trim();
                value = value.replace(/\u00A0/g, " ");
                value = value.replace(/R\$/g, "").replace(/\./g, "").replace(/%/g, "");
                value = value.replace(/"/g, '""');
                row.push('"' + value + '"');
            });
        }
        
        if (row.length > 0) {
            csvContent += row.join(";") + "\n";
        }
    });
    
    // Download
    const csvWithBOM = BOM + csvContent;
    const blob = new Blob([csvWithBOM], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "fluxo_caixa_detalhado_" + new Date().toISOString().split("T")[0] + ".csv";
    link.click();
}


// 2. Exportar Saldo Mensal por Centro de Custo
function exportSaldoCentroCusto() {
    const table = document.getElementById('tableSaldoCentroCusto');
    if (!table) {
        alert('Tabela nÃ£o encontrada!');
        return;
    }
    
    let csvContent = "";
    const BOM = "\uFEFF";
    
    // CABEÃ‡ALHO
    const headers = table.querySelectorAll("thead tr th");
    let headerRow = [];
    headers.forEach(th => {
        let text = th.textContent.trim();
        text = text.replace(/"/g, '""');
        headerRow.push('"' + text + '"');
    });
    csvContent += headerRow.join(";") + "\n";  // â† MUDANÃ‡A AQUI
    
    // DADOS
    table.querySelectorAll("tbody tr").forEach(tr => {
        let row = [];
        tr.querySelectorAll("td").forEach(td => {
            let value = td.textContent.trim();
            value = value.replace(/\u00A0/g, " ");
            value = value.replace(/R\$/g, "").replace(/\./g, "");
            value = value.replace(/"/g, '""');
            row.push('"' + value + '"');
        });
        if (row.length > 0) {
            csvContent += row.join(";") + "\n";  // â† MUDANÃ‡A AQUI
        }
    });
    
    // Download
    const csvWithBOM = BOM + csvContent;
    const blob = new Blob([csvWithBOM], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "saldo_centro_custo_" + new Date().toISOString().split("T")[0] + ".csv";
    link.click();
}

// 3. Exportar Indicadores Financeiros Mensais (DRE)
function exportIndicadores() {
    const table = document.getElementById('tableIndicadores');
    if (!table) {
        alert('Tabela nÃ£o encontrada!');
        return;
    }
    
    let csvContent = "";
    const BOM = "\uFEFF";
    
    // CABEÃ‡ALHO
    const headers = table.querySelectorAll("thead tr th");
    let headerRow = [];
    headers.forEach(th => {
        let text = th.textContent.trim();
        text = text.replace(/"/g, '""');
        headerRow.push('"' + text + '"');
    });
    csvContent += headerRow.join(";") + "\n";  // â† MUDANÃ‡A AQUI
    
    // DADOS
    table.querySelectorAll("tbody tr").forEach(tr => {
        let row = [];
        tr.querySelectorAll("td").forEach(td => {
            let value = td.textContent.trim();
            value = value.replace(/\u00A0/g, " ");
            value = value.replace(/R\$/g, "").replace(/\./g, "").replace(/%/g, "");
            value = value.replace(/"/g, '""');
            row.push('"' + value + '"');
        });
        if (row.length > 0) {
            csvContent += row.join(";") + "\n";  // â† MUDANÃ‡A AQUI
        }
    });
    
    // Download
    const csvWithBOM = BOM + csvContent;
    const blob = new Blob([csvWithBOM], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "indicadores_financeiros_" + new Date().toISOString().split("T")[0] + ".csv";
    link.click();
}

// 4. Exportar DemonstraÃ§Ã£o do Resultado do ExercÃ­cio (DRE)
function exportDRE() {
    const table = document.getElementById('tableDRE');
    if (!table) {
        alert('Tabela nÃ£o encontrada!');
        return;
    }
    
    let csvContent = "";
    const BOM = "\uFEFF";
    
    // CABEÃ‡ALHO
    const headers = table.querySelectorAll("thead tr th");
    let headerRow = [];
    headers.forEach(th => {
        let text = th.textContent.trim();
        text = text.replace(/"/g, '""');
        headerRow.push('"' + text + '"');
    });
    csvContent += headerRow.join(";") + "\n";  // â† MUDANÃ‡A AQUI
    
    // DADOS
    table.querySelectorAll("tbody tr").forEach(tr => {
        let row = [];
        tr.querySelectorAll("td").forEach(td => {
            let value = td.textContent.trim();
            value = value.replace(/\u00A0/g, " ");
            value = value.replace(/R\$/g, "").replace(/\./g, "").replace(/%/g, "");
            value = value.replace(/"/g, '""');
            row.push('"' + value + '"');
        });
        if (row.length > 0) {
            csvContent += row.join(";") + "\n";  // â† MUDANÃ‡A AQUI
        }
    });
    
    // Download
    const csvWithBOM = BOM + csvContent;
    const blob = new Blob([csvWithBOM], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "dre_" + new Date().toISOString().split("T")[0] + ".csv";
    link.click();
}



function initSimuladorFluxoPage() {
  // Setar datas padrÃ£o do ano vigente
  const currentYear = new Date().getFullYear();
  document.getElementById('startMonthSimFluxo').value = `${currentYear}-01`;
  document.getElementById('endMonthSimFluxo').value = `${currentYear}-12`;
  
  populateFiltersSimFluxo();
  updateSimuladorFluxoPage();
}

// Event Listeners - Simulador Fluxo de Caixa
document.getElementById('statusFilterSimFluxo').addEventListener('change', updateSimuladorFluxoPage);
document.getElementById('costCenterFilterSimFluxo').addEventListener('change', updateSimuladorFluxoPage);
document.getElementById('categoryFilterSimFluxo').addEventListener('change', updateSimuladorFluxoPage);
document.getElementById('negotiatorFilterSimFluxo').addEventListener('change', updateSimuladorFluxoPage);
document.getElementById('startMonthSimFluxo').addEventListener('change', updateSimuladorFluxoPage);
document.getElementById('endMonthSimFluxo').addEventListener('change', updateSimuladorFluxoPage);

document.querySelectorAll('input[name="dateTypeSimFluxo"]').forEach(radio => {
  radio.addEventListener('change', function() {
    currentDateTypeSimFluxo = this.value;
    updateSimuladorFluxoPage();
  });
});


// ==================== ANÃLISE DE RENTABILIDADE ====================

let globalDataRentabilidade = [];

function clearDateRangeRentabilidade() {
    document.getElementById('startDateRentabilidade').value = '';
    document.getElementById('endDateRentabilidade').value = '';
    if (globalDataRentabilidade.length > 0) {
        updateRentabilidade();
    }
}

async function loadRentabilidade() {
    try {
        // Usar o mesmo conjunto consolidado do dashboard principal
        const combinedData = await loadCombinedCsvData();
        
        // Filtrar dados vÃ¡lidos
        globalDataRentabilidade = combinedData.filter(row => row.paid_new || row.unpaid_new);
        
        // Processar valores
        globalDataRentabilidade.forEach(row => {
            row.paidnew = parseBrazilianFloat(row.paid_new);
            row.unpaidnew = parseBrazilianFloat(row.unpaid_new);
            row.total = row.paidnew + row.unpaidnew;
        });
        
        console.log('Dados de Rentabilidade carregados:', globalDataRentabilidade.length, 'registros');
        
        populateFiltersRentabilidade(globalDataRentabilidade);
        updateRentabilidade();
    } catch (error) {
        console.error('Erro ao carregar dados de Rentabilidade:', error);
        alert('Erro ao carregar dados: ' + error.message);
    }
}

function populateFiltersRentabilidade(data) {
    console.log('Populando filtros de Rentabilidade com', data.length, 'registros');
    console.log('Exemplo de registro:', data[0]); // DEBUG
    
    const statusSet = new Set();
    const costCenterSet = new Set();
    const categorySet = new Set();
    const negotiatorSet = new Set();
    
    data.forEach(row => {
        // Status
        if (row.status) statusSet.add(row.status);
        
        // Centro de Custo - usar exatamente como nas outras seÃ§Ãµes
        const costCenter = row['Centro_de_Custo_Unificado'];
        if (costCenter) {
            costCenterSet.add(costCenter);
        }
        
        // Categoria
        const category = row['categoriesRatio.category'];
        if (category) {
            categorySet.add(category);
        }
        
        // Negotiator
        const negotiator = row['financialEvent.negotiator.name'];
        if (negotiator) {
            negotiatorSet.add(negotiator);
        }
    });
    
    console.log('Centros de Custo encontrados:', Array.from(costCenterSet)); // DEBUG
    console.log('Status encontrados:', Array.from(statusSet)); // DEBUG
    
    // Usar a funÃ§Ã£o populateSelect que jÃ¡ existe no sistema
    populateSelect('filterStatusRentabilidade', statusSet, true); // true = usar mapeamento de status
    populateSelect('filterCentroCustoRentabilidade', costCenterSet);
    populateSelect('filterCategoriaRentabilidade', categorySet);
    populateSelect('filterNegotiatorRentabilidade', negotiatorSet);
}





function getFilteredDataRentabilidade() {
    const dateType = document.querySelector('input[name="dateTypeRentabilidade"]:checked')?.value || 'realizadoProjetado';
    const startDate = document.getElementById('startDateRentabilidade').value;
    const endDate = document.getElementById('endDateRentabilidade').value;
    
    const selectedStatus = Array.from(document.getElementById('filterStatusRentabilidade').selectedOptions)
        .map(o => o.value).filter(v => v);
    const selectedCentros = Array.from(document.getElementById('filterCentroCustoRentabilidade').selectedOptions)
        .map(o => o.value).filter(v => v);
    const selectedCategorias = Array.from(document.getElementById('filterCategoriaRentabilidade').selectedOptions)
        .map(o => o.value).filter(v => v);
    const selectedNegotiators = Array.from(document.getElementById('filterNegotiatorRentabilidade').selectedOptions)
        .map(o => o.value).filter(v => v);
    
    return globalDataRentabilidade.filter(row => {
        // Filtros de seleÃ§Ã£o
        if (selectedStatus.length > 0 && !selectedStatus.includes(row.status)) return false;
        if (selectedCentros.length > 0 && !selectedCentros.includes(row['Centro_de_Custo_Unificado'])) return false;
        if (selectedCategorias.length > 0 && !selectedCategorias.includes(row['categoriesRatio.category'])) return false;
        if (selectedNegotiators.length > 0 && !selectedNegotiators.includes(row['financialEvent.negotiator.name'])) return false;
        
        // Filtro de data
        const dateToCheck = getDateForRow(row, dateType);
        if (!isDateInRange(dateToCheck, startDate, endDate)) return false;
        
        return true;
    });
}

function getFilteredDataDRE() {
    const dateType = document.querySelector('input[name="dateTypeDRE"]:checked')?.value || 'realizadoProjetado';
    const startMonth = document.getElementById('startMonthDRE').value;
    const endMonth = document.getElementById('endMonthDRE').value;
    
    const selectedStatus = Array.from(document.getElementById('statusFilterDRE').selectedOptions)
        .map(o => o.value).filter(v => v);
    const selectedCostCenters = Array.from(document.getElementById('costCenterFilterDRE').selectedOptions)
        .map(o => o.value).filter(v => v);
    const selectedCategories = Array.from(document.getElementById('categoryFilterDRE').selectedOptions)
        .map(o => o.value).filter(v => v);
    const selectedNegotiators = Array.from(document.getElementById('negotiatorFilterDRE').selectedOptions)
        .map(o => o.value).filter(v => v);
    const selectedFluxos = Array.from(document.getElementById('fluxoFilterDRE').selectedOptions)
        .map(o => o.value).filter(v => v);
    
    return rawData.filter(row => {
        // Filtros bÃ¡sicos
        if (selectedStatus.length > 0 && !selectedStatus.includes(row.status)) return false;
        if (selectedCostCenters.length > 0 && !selectedCostCenters.includes(row['Centro_de_Custo_Unificado'])) return false;
        if (selectedCategories.length > 0 && !selectedCategories.includes(row['categoriesRatio.category'])) return false;
        if (selectedNegotiators.length > 0 && !selectedNegotiators.includes(row['financialEvent.negotiator.name'])) return false;
        
        // Filtro de data
        const dateToCheck = getDateForRow(row, dateType);
        if (!isDateInRange(dateToCheck, startMonth, endMonth)) return false;
        
        // Filtro de Fluxo (FCO/FCI/FCF)
        if (selectedFluxos.length > 0) {
            const categoria = row['categoriesRatio.category'];
            const codigoMatch = categoria?.match(/(\d+\.\d+)/);
            if (codigoMatch) {
                const codigo = codigoMatch[1];
                const fluxoTipo = dreFluxoMapping[codigo] || 'CALCULO';
                if (!selectedFluxos.includes(fluxoTipo)) return false;
            }
        }
        
        return true;
    });
}


function updateRentabilidade() {
    const filteredData = getFilteredDataRentabilidade();
    
    // KPIs
    updateKPIsRentabilidade(filteredData);
    
    // AnÃ¡lise por Centro de Custo
    updateAnaliseCentrosCustosRentabilidade(filteredData);
}

function updateKPIsRentabilidade(data) {
    const receitas = data.filter(r => r['tipo'] === 'Receita');
    const despesas = data.filter(r => r['tipo'] === 'Despesa');
    
    const receitaTotal = receitas.reduce((sum, r) => sum + (r.total || 0), 0);
    const custoTotal = despesas.reduce((sum, r) => sum + (r.total || 0), 0);
    const margemBruta = receitaTotal - custoTotal;
    const percMargemBruta = receitaTotal > 0 ? (margemBruta / receitaTotal * 100) : 0;
    
    // Centros de Custo Ãºnicos
    const centrosCusto = new Set(data.filter(r => r['Centro_de_Custo_Unificado']).map(r => r['Centro_de_Custo_Unificado']));
    const centrosCustosLucrativos = Array.from(centrosCusto).filter(centro => {
        const receitaCentro = receitas.filter(r => r['Centro_de_Custo_Unificado'] === centro)
            .reduce((sum, r) => sum + (r.total || 0), 0);
        const custoCentro = despesas.filter(r => r['Centro_de_Custo_Unificado'] === centro)
            .reduce((sum, r) => sum + (r.total || 0), 0);
        return receitaCentro > custoCentro;
    }).length;
    
    document.getElementById('kpiReceitaTotalRentabilidade').textContent = formatCurrency(receitaTotal);
    document.getElementById('kpiCustoTotalRentabilidade').textContent = formatCurrency(custoTotal);
    document.getElementById('kpiMargemBrutaRentabilidade').textContent = formatCurrency(margemBruta);
    document.getElementById('kpiPercMargemBrutaRentabilidade').textContent = percMargemBruta.toFixed(1) + '%';
    document.getElementById('kpiCentrosCustosLucrativosRentabilidade').textContent = `${centrosCustosLucrativos}/${centrosCusto.size}`;
    
    // Colorir KPIs
    const margemCard = document.getElementById('kpiMargemBrutaRentabilidade').closest('.kpi-card');
    const percMargemCard = document.getElementById('kpiPercMargemBrutaRentabilidade').closest('.kpi-card');
    
    if (margemBruta >= 0) {
        margemCard.classList.add('positive');
        margemCard.classList.remove('negative');
        percMargemCard.classList.add('positive');
        percMargemCard.classList.remove('negative');
    } else {
        margemCard.classList.add('negative');
        margemCard.classList.remove('positive');
        percMargemCard.classList.add('negative');
        percMargemCard.classList.remove('positive');
    }
}

function updateAnaliseCentrosCustosRentabilidade(data) {
    const receitas = data.filter(r => r['tipo'] === 'Receita');
    const despesas = data.filter(r => r['tipo'] === 'Despesa');
    
    // Agrupar por centro de custo
    const centrosCusto = {};
    
    [...receitas, ...despesas].forEach(row => {
        const centro = row['Centro_de_Custo_Unificado'] || 'Sem Centro de Custo';
        if (!centrosCusto[centro]) {
            centrosCusto[centro] = { receita: 0, custo: 0, margem: 0, margemPerc: 0 };
        }
        
        const valor = row.total || 0;
        if (row['tipo'] === 'Receita') {
            centrosCusto[centro].receita += valor;
        } else {
            centrosCusto[centro].custo += valor;
        }
    });
    
    // Calcular margens
    const centrosCustosArray = Object.entries(centrosCusto).map(([nome, dados]) => {
        dados.margem = dados.receita - dados.custo;
        dados.margemPerc = dados.receita > 0 ? (dados.margem / dados.receita * 100) : 0;
        return { nome, ...dados };
    }).sort((a, b) => b.receita - a.receita);
    
    // Tabela
    let tableHTML = `
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Centro de Custo</th>
                        <th>A Receber</th>
                        <th>A Pagar</th>
                        <th>Margem Bruta</th>
                        <th>% Margem</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    centrosCustosArray.forEach(centro => {
        const statusIcon = centro.margem >= 0 ? 'âœ…' : 'âŒ';
        const statusText = centro.margem >= 0 ? 'Lucrativo' : 'PrejuÃ­zo';
        
        tableHTML += `
            <tr>
                <td><strong>${centro.nome}</strong></td>
                <td style="color: #4ade80;">${formatCurrency(centro.receita)}</td>
                <td style="color: #f87171;">${formatCurrency(centro.custo)}</td>
                <td style="color: ${centro.margem >= 0 ? '#4ade80' : '#f87171'};">
                    <strong>${formatCurrency(centro.margem)}</strong>
                </td>
                <td style="color: ${centro.margemPerc >= 0 ? '#4ade80' : '#f87171'};">
                    <strong>${centro.margemPerc.toFixed(1)}%</strong>
                </td>
                <td>${statusIcon} ${statusText}</td>
            </tr>
        `;
    });
    
    tableHTML += `</tbody></table></div>`;
    document.getElementById('tabelaCentrosCustosRentabilidade').innerHTML = tableHTML;
    
    // GrÃ¡ficos Top 10
    const top10Receita = centrosCustosArray.slice(0, 10);
    const top10Margem = [...centrosCustosArray].sort((a, b) => b.margemPerc - a.margemPerc).slice(0, 10);
    
    // GrÃ¡fico Top 10 Receita
    Plotly.newPlot('graficoTop10CentrosCustosReceita', [{
        type: 'bar',
        x: top10Receita.map(p => p.receita),
        y: top10Receita.map(p => p.nome),
        orientation: 'h',
        marker: {
            color: top10Receita.map(p => p.margem >= 0 ? '#4ade80' : '#f87171')
        },
        text: top10Receita.map(p => formatCurrency(p.receita)),
        textposition: 'auto',
        textfont: { color: '#ffffff' }
    }], {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        margin: { l: 250, r: 150, t: 20, b: 50 },
        xaxis: { title: 'Receita', gridcolor: 'rgba(255,255,255,0.1)', color: '#cbd5e1' },
        yaxis: { color: '#cbd5e1', autorange: 'reversed' },
        font: { color: '#cbd5e1' }
    }, {responsive: true});
    
    // GrÃ¡fico Top 10 Margem
    Plotly.newPlot('graficoTop10CentrosCustosMargem', [{
        type: 'bar',
        x: top10Margem.map(p => p.margemPerc),
        y: top10Margem.map(p => p.nome),
        orientation: 'h',
        marker: {
            color: top10Margem.map(p => p.margemPerc >= 0 ? '#4ade80' : '#f87171')
        },
        text: top10Margem.map(p => p.margemPerc.toFixed(1) + '%'),
        textposition: 'auto',
        textfont: { color: '#ffffff' }
    }], {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        margin: { l: 150, r: 50, t: 20, b: 50 },
        xaxis: { title: '% Margem', gridcolor: 'rgba(255,255,255,0.1)', color: '#cbd5e1', ticksuffix: '%' },
        yaxis: { color: '#cbd5e1', autorange: 'reversed' },
        font: { color: '#cbd5e1' }
    }, {responsive: true});
}



// Event Listeners para AnÃ¡lise de Rentabilidade
document.addEventListener('DOMContentLoaded', function() {
    const filterElementsRentabilidade = [
        'filterStatusRentabilidade',
        'filterCentroCustoRentabilidade',
        'filterCategoriaRentabilidade',
        'filterNegotiatorRentabilidade',
        'startDateRentabilidade',
        'endDateRentabilidade'
    ];
    
    filterElementsRentabilidade.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', () => {
                if (document.getElementById('rentabilidade').classList.contains('active')) {
                    updateRentabilidade();
                }
            });
        }
    });
    
    // Listener para mudanÃ§a de tipo de data
    document.querySelectorAll('input[name="dateTypeRentabilidade"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (document.getElementById('rentabilidade').classList.contains('active')) {
                updateRentabilidade();
            }
        });
    });
});






        loadData();
        
       async function exportCurrentPageToPNG(event) {
    event.preventDefault();
    // Encontrar seÃ§Ã£o ativa
    const activeSection = document.querySelector('.page-section.active');
    if (!activeSection) {
        alert('Nenhuma pÃ¡gina ativa para exportar!');
        return;
    }

    // Overlay de carregamento
    const overlay = document.createElement('div');
    overlay.className = 'pdf-loading-overlay';
    overlay.innerHTML = `
        <h2>Exportando Imagem</h2>
        <div class="pdf-loading-spinner"></div>
        <p style="margin-top:20px; font-size:1.1em;">Processando grÃ¡ficos e conteÃºdo...</p>
    `;
    document.body.appendChild(overlay);

    try {
        await new Promise(resolve => setTimeout(resolve, 500));

        // Clona e prepara
        const clone = activeSection.cloneNode(true);
        clone.style.display = 'block';
        clone.style.position = 'absolute';
        clone.style.left = '-9999px';
        clone.style.width = 'auto';
        clone.style.background = 'linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%)';
        clone.style.padding = '30px';
        clone.style.minHeight = '100vh';

        // Remove controles extras
        const controls = clone.querySelector('.controls');
        if (controls) controls.remove();

        // Remove overflow/max-width e expande elementos
        clone.querySelectorAll('*').forEach(el => {
            el.style.overflowX = 'visible';
            el.style.overflowY = 'visible';
            el.style.maxWidth = 'none';
        });
        clone.querySelectorAll('table, .table-container, [class*="scroll"]').forEach(el => {
            el.style.width = '100%';
            el.style.display = 'block';
        });

        // Fundo escuro para tabelas/cÃ©lulas
        clone.querySelectorAll('table').forEach(table => {
            table.style.background = 'rgba(15,15,35,0.8)';
            table.style.color = '#ffffff';
        });
        clone.querySelectorAll('table th').forEach(th => {
            th.style.background = 'rgba(15,15,35,0.8)';
            th.style.color = '#FFD700';
        });
        clone.querySelectorAll('table td').forEach(td => {
            td.style.background = 'rgba(255,255,255,0.02)';
            td.style.color = '#cbd5e1';
            td.style.whiteSpace = 'normal';
        });

        document.body.appendChild(clone);

        // ========== CORREÃ‡ÃƒO PARA GRÃFICOS DE INDICADORES ==========
        // ForÃ§ar visibilidade dos grÃ¡ficos de indicadores (Chart.js)
        const indicadoresGraphs = clone.querySelector('#indicadoresGraphsContent');
        if (indicadoresGraphs) {
            indicadoresGraphs.style.display = 'block';
            indicadoresGraphs.style.opacity = '1';
            indicadoresGraphs.style.visibility = 'visible';
            indicadoresGraphs.style.height = 'auto';
        }

        // Copiar conteÃºdo dos canvas do Chart.js para o clone
        clone.querySelectorAll('canvas').forEach(canvasClone => {
            if (canvasClone.id) {
                const originalCanvas = activeSection.querySelector(`#${canvasClone.id}`);
                if (originalCanvas && originalCanvas.width > 0 && originalCanvas.height > 0) {
                    canvasClone.width = originalCanvas.width;
                    canvasClone.height = originalCanvas.height;
                    const ctx = canvasClone.getContext('2d');
                    ctx.drawImage(originalCanvas, 0, 0);
                }
            }
        });
        // ========== FIM DA CORREÃ‡ÃƒO ==========

        // Calcula largura mÃ¡xima real do conteÃºdo
        let maxWidth = 1200;
        clone.querySelectorAll('*').forEach(el => {
            if (el.scrollWidth > maxWidth) maxWidth = el.scrollWidth + 40;
        });
        clone.style.width = maxWidth + 'px';

        // Renderiza imagem PNG usando html2canvas
        const canvas = await html2canvas(clone, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#0f0f23',
            windowWidth: maxWidth,
            allowTaint: true,
            onclone: clonedDoc => {
                clonedDoc.body.style.background = '#0f0f23';
                
                // Ajustes de overflow para evitar corte ou scroll, mas sem afetar alinhamento
                clonedDoc.querySelectorAll('*').forEach(el => {
                    el.style.overflowX = 'visible';
                    el.style.overflowY = 'visible';
                    el.style.maxWidth = 'none';
                });

                // Garantir que grÃ¡ficos fiquem 100% expandidos
                clonedDoc.querySelectorAll('.js-plotly-plot').forEach(chart => {
                    chart.style.width = '100%';
                });
                clonedDoc.querySelectorAll('canvas').forEach(c => {
                    c.style.maxHeight = 'none';
                    c.style.maxWidth = 'none';
                });

                // Remover sticky para nÃ£o cortar nada
                clonedDoc.querySelectorAll("th[position-sticky], td[position-sticky], th.position-sticky, td.position-sticky")
                    .forEach(cell => {
                        cell.style.position = "static";
                        cell.style.left = "auto";
                    });

                // Garantir alinhamento Ã  esquerda nas colunas de texto
                clonedDoc.querySelectorAll(
                    "th, td.col-categoria, td.col-total-receita, td.col-total-despesa, td.col-resultado-liquido"
                ).forEach(cell => {
                    cell.style.textAlign = "left";
                });

                // Garantir alinhamento Ã  direita sÃ³ para valores numÃ©ricos
                clonedDoc.querySelectorAll("td[data-align='right'], td.valor, td.num")
                    .forEach(cell => {
                        cell.style.textAlign = "right";
                    });

                // Garantir que indicadores fiquem visÃ­veis tambÃ©m no onclone
                const indicadoresInClone = clonedDoc.querySelector('#indicadoresGraphsContent');
                if (indicadoresInClone) {
                    indicadoresInClone.style.display = 'block';
                    indicadoresInClone.style.opacity = '1';
                    indicadoresInClone.style.visibility = 'visible';
                }
            }
        });

        document.body.removeChild(clone);

        // Baixa imagem PNG
        const pageHeader = activeSection.querySelector('.page-header h2');
        const pageTitle = pageHeader ? pageHeader.textContent : 'Dashboard';
        const fileName = `${pageTitle.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.png`;
        const imgData = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.href = imgData;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        document.body.removeChild(overlay);
        setTimeout(() => {
            alert(`âœ… PNG "${fileName}" gerado com sucesso!`);
        }, 300);

    } catch (error) {
        console.error('Erro ao exportar PNG:', error);
        document.body.removeChild(overlay);
        alert('âŒ Erro ao exportar PNG. Verifique o console para mais detalhes.');
    }
}


        
    </script>
</body>
</html>
