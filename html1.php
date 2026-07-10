<?php
// Função para carregar variáveis do .env
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Arquivo .env não encontrado");
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

// Carrega as variáveis de ambiente
loadEnv(__DIR__ . '/.env');

$GOOGLE_API_KEY = getenv('GOOGLE_API_KEY');
$GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1_IA = getenv('GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1_IA');
$GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1 = getenv('GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1');
$GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2 = getenv('GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2');
$GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar = getenv('GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar');
$APPS_SCRIPT_URL = getenv('APPS_SCRIPT_URL');

// Inicia a sessão
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Soc.ia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
      color: #e2e8f0;
      overflow-x: hidden;
      min-height: 100vh;
    }
    .background {
      position: fixed;
      top: 0;
      left: 0;
      width: 200%;
      height: 200%;
      background-image: url('formas.png');
      background-size: 80px;
      opacity: 0.03;
      z-index: -1;
      animation: moveBackground 60s linear infinite;
    }
    @keyframes moveBackground {
      0% { transform: translate(0, 0); }
      100% { transform: translate(-50%, -50%); }
    }
    
    /* Debug panel */
    #debugPanel {
      position: fixed;
      top: 80px;
      left: 10px;
      background: rgba(0, 0, 0, 0.8);
      color: #00ff00;
      font-family: monospace;
      font-size: 12px;
      padding: 10px;
      border-radius: 5px;
      max-width: 350px;
      max-height: 250px;
      overflow-y: auto;
      z-index: 1001;
      display: none;
    }
    
    #debugPanel.show {
      display: block;
    }
    
    #debugToggle {
      display: none !important;
      position: fixed;
      top: 80px;
      right: 10px;
      background: #333;
      color: #00ff00;
      border: none;
      padding: 5px 10px;
      border-radius: 3px;
      font-size: 12px;
      cursor: pointer;
      z-index: 1002;
    }
    
    /* Faixa de filtros de período */
    .period-filter {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 32px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .period-filter h3 {
      color: #FFD700;
      font-size: 16px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
    }
    
    .period-filter h3 i {
      margin-right: 8px;
    }
    
    .period-buttons {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: center;
    }
    
    .period-button {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #94a3b8;
      font-size: 14px;
      font-weight: 500;
      padding: 12px 20px;
      cursor: pointer;
      border-radius: 10px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    
    .period-button::before {
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
    
    .period-button:hover {
      color: #ffffff;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    .period-button:hover::before {
      opacity: 0.1;
    }
    
    .period-button.active {
      background: linear-gradient(135deg, #FFD700, #FFA500);
      color: #0f0f23;
      font-weight: 600;
      box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
    }
    
    /* Notificação de atualização */
    .update-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: white;
      padding: 12px 20px;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(34, 197, 94, 0.4);
      z-index: 1000;
      transform: translateX(400px);
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-size: 14px;
      font-weight: 500;
    }
    
    .update-notification.show {
      transform: translateX(0);
    }
    
    .update-notification i {
      margin-right: 8px;
    }
    
    /* Cards editáveis */
    .card-editable {
      cursor: text;
      position: relative;
    }
    
    .card-editable::after {
      content: '✏️ Clique para editar';
      position: absolute;
      top: 8px;
      right: 12px;
      font-size: 12px;
      color: rgba(255, 215, 0, 0.6);
      opacity: 0;
      transition: opacity 0.3s;
      pointer-events: none;
    }
    
    .card-editable:hover::after {
      opacity: 1;
    }
    
    .card-editable.editing::after {
      content: '💾 Clique fora para salvar';
      opacity: 1;
      color: rgba(34, 197, 94, 0.8);
    }
    
    .card-editable.editing {
      border-color: rgba(255, 215, 0, 0.5) !important;
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.3) !important;
    }
    
    .card-updated {
      border-color: rgba(34, 197, 94, 0.6) !important;
      box-shadow: 0 0 15px rgba(34, 197, 94, 0.4) !important;
    }
    
    /* Cards específicos para receita e despesa */
    .card-receita {
      background: rgba(34, 197, 94, 0.08) !important;
      border: 1px solid rgba(34, 197, 94, 0.2) !important;
    }
    
    .pdf-title {
      background: none !important;
      -webkit-background-clip: initial !important;
      -webkit-text-fill-color: initial !important;
      background-clip: initial !important;
      color: #FFD700 !important;
    }
    .hide-in-pdf {
      display: none !important;
    }
    .card-receita::before {
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%) !important;
    }
    .card-receita:hover {
      border-color: rgba(34, 197, 94, 0.4) !important;
      box-shadow: 0 25px 50px rgba(34, 197, 94, 0.2) !important;
    }
    .card-receita .card-title {
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .card-despesa {
      background: rgba(239, 68, 68, 0.08) !important;
      border: 1px solid rgba(239, 68, 68, 0.2) !important;
    }
    .card-despesa::before {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%) !important;
    }
    .card-despesa:hover {
      border-color: rgba(239, 68, 68, 0.4) !important;
      box-shadow: 0 25px 50px rgba(239, 68, 68, 0.2) !important;
    }
    .card-despesa .card-title {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    /* Cards de indicadores com cores específicas */
    #cards-container2 .card-receita {
      background: rgba(34, 197, 94, 0.1) !important;
      border: 1px solid rgba(34, 197, 94, 0.3) !important;
    }
    #cards-container2 .card-receita::before {
      background: linear-gradient(90deg, #22c55e, #16a34a) !important;
    }
    #cards-container2 .card-receita .card-text {
      color: #22c55e !important;
    }
    #cards-container2 .card-despesa {
      background: rgba(239, 68, 68, 0.1) !important;
      border: 1px solid rgba(239, 68, 68, 0.3) !important;
    }
    #cards-container2 .card-despesa::before {
      background: linear-gradient(90deg, #ef4444, #dc2626) !important;
    }
    #cards-container2 .card-despesa .card-text {
      color: #ef4444 !important;
    }
    .topbar {
      background: rgba(15, 15, 35, 0.95);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: 16px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    .logo {
      font-size: 22px;
      font-weight: 700;
      background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: -0.5px;
    }
    .menu-buttons {
      display: flex;
      gap: 8px;
      background: rgba(255, 255, 255, 0.05);
      padding: 6px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .menu-button {
      background: none;
      border: none;
      color: #94a3b8;
      font-size: 14px;
      font-weight: 500;
      padding: 10px 20px;
      cursor: pointer;
      border-radius: 8px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
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
    .user-menu {
      position: relative;
    }
    .user-icon {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #e2e8f0;
      font-size: 18px;
      cursor: pointer;
      padding: 10px;
      border-radius: 10px;
      transition: all 0.3s;
      backdrop-filter: blur(10px);
    }
    .user-icon:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: scale(1.05);
    }
    .dropdown {
      display: none;
      position: absolute;
      right: 0;
      top: 50px;
      background: rgba(30, 30, 46, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      overflow: hidden;
      min-width: 200px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }
    .dropdown a,
    .dropdown span {
      display: block;
      padding: 14px 18px;
      color: #e2e8f0;
      text-decoration: none;
      font-size: 14px;
      transition: all 0.3s;
    }
    .dropdown a:hover {
      background: rgba(255, 215, 0, 0.1);
      color: #FFD700;
    }
    .dropdown span {
      background: rgba(255, 215, 0, 0.1);
      color: #FFD700;
      font-weight: 600;
      font-size: 13px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .content-container {
      display: flex;
      flex-direction: column;
      min-height: calc(100vh - 80px);
    }
    .content {
      flex: 1;
      padding: 32px;
      display: none;
    }
    .content.active {
      display: block;
    }
    /* Cards de indicadores no topo */
    #cards-container2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }
    #cards-container2 .card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 24px;
      border-radius: 16px;
      text-align: center;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    #cards-container2 .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #FFD700, #FFA500);
    }
    #cards-container2 .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      border-color: rgba(255, 215, 0, 0.3);
    }
    #cards-container2 .card-title {
      font-size: 14px;
      font-weight: 500;
      color: #94a3b8;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    #cards-container2 .card-text {
      font-size: 28px;
      font-weight: 700;
      color: #ffffff;
      margin: 0;
    }
    /* Cards principais */
    #cards-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 24px;
      max-width: 1200px;
      margin: 0 auto;
    }
    .card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      width: 100%;
      padding: 32px;
      border-radius: 20px;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
      opacity: 0;
      transition: opacity 0.3s;
    }
    .card:hover {
      transform: translateY(-6px);
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
      border-color: rgba(255, 215, 0, 0.3);
    }
    .card:hover::before {
      opacity: 1;
    }
    .card-title {
      font-size: 24px;
      font-weight: 600;
      display: flex;
      align-items: center;
      background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 16px;
    }
    .card-title img {
      height: 32px;
      margin-right: 16px;
    }
    .card-title i {
      margin-right: 12px;
      font-size: 20px;
    }
    .card-text {
      font-size: 16px;
      color: #cbd5e1;
      line-height: 1.7;
      position: relative;
      z-index: 1;
    }
    .card-text strong,
.card-text b {
  font-weight: bold !important;
  color: inherit !important;
}
.card-text br {
  display: block !important;
  content: "" !important;
  margin-bottom: 8px !important;
}

.card-text {
  white-space: normal !important;
  word-wrap: break-word !important;
}


    /* Tabelas */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: rgba(255, 255, 255, 0.02);
      border-radius: 12px;
      overflow: hidden;
    }
    table th {
      background: rgba(255, 215, 0, 0.1);
      color: #FFD700;
      font-weight: 600;
      padding: 16px;
      text-align: left;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    table td {
      padding: 14px 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: background 0.3s;
    }
    table tr:hover td {
      background: rgba(255, 255, 255, 0.05);
    }
    /* Charts */
    canvas {
      max-width: 100% !important;
      height: auto !important;
      display: block;
      margin-top: 24px;
      border-radius: 12px;
    }
    /* iframe */
    iframe {
      width: 100%;
      height: 85vh;
      border: none;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
      margin-top: 20px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    /* Botão PDF */
    #downloadPDF {
      position: fixed;
      bottom: 32px;
      right: 32px;
      width: 64px;
      height: 64px;
      border: none;
      border-radius: 20px;
      background: linear-gradient(135deg, #FFD700, #FFA500);
      color: #0f0f23;
      font-size: 20px;
      box-shadow: 0 8px 32px rgba(255, 215, 0, 0.4);
      cursor: pointer;
      z-index: 999;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    #downloadPDF:hover {
      transform: scale(1.1) translateY(-2px);
      box-shadow: 0 12px 40px rgba(255, 215, 0, 0.6);
    }
    /* Responsivo */
    @media (max-width: 768px) {
      .topbar {
        padding: 16px 20px;
        flex-direction: column;
        gap: 16px;
      }
      .menu-buttons {
        order: 2;
        width: 100%;
        justify-content: center;
      }
      .logo {
        order: 1;
        font-size: 20px;
      }
      .user-menu {
        order: 3;
        position: absolute;
        top: 16px;
        right: 20px;
      }
      .content {
        padding: 20px;
      }
      #cards-container2 {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
      }
      .card {
        padding: 24px;
      }
      .card-title {
        font-size: 20px;
      }

      iframe {
        height: 70vh;
      }
      #downloadPDF {
        bottom: 20px;
        right: 20px;
        width: 56px;
        height: 56px;
        font-size: 18px;
      }
      .period-buttons {
        justify-content: flex-start;
      }
      .period-button {
        font-size: 12px;
        padding: 10px 16px;
      }
      .update-notification {
        right: 10px;
        left: 10px;
        transform: translateY(-100px);
      }
      .update-notification.show {
        transform: translateY(0);
      }
    }
    @media (max-width: 480px) {
      #cards-container2 {
        grid-template-columns: 1fr;
      }
      
      .menu-buttons {
        flex-direction: column;
        gap: 8px;
      }
      
      .period-buttons {
        flex-direction: column;
        gap: 8px;
      }
    }

    /* Animações para notificações de salvamento */
    @keyframes slideInRight {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }

    .save-notification {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    /* Animações de entrada */
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
    .card {
      animation: fadeInUp 0.6s ease-out forwards;
    }
    .card:nth-child(2) { animation-delay: 0.1s; }
    .card:nth-child(3) { animation-delay: 0.2s; }
    .card:nth-child(4) { animation-delay: 0.3s; }
    .card:nth-child(5) { animation-delay: 0.4s; }
  
    /* Tela de Loading */
    #loadingScreen {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
    }

    #loadingScreen.hidden {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }

    .loading-content {
      text-align: center;
      animation: fadeInUp 0.8s ease-out;
    }

    .loading-logo {
      font-size: 48px;
      font-weight: 700;
      background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 30px;
      letter-spacing: -1px;
    }

    .loading-spinner {
      width: 60px;
      height: 60px;
      margin: 0 auto 30px;
      position: relative;
    }

    .loading-spinner::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      border: 4px solid rgba(255, 215, 0, 0.1);
      border-radius: 50%;
      border-top-color: #FFD700;
      animation: spin 1s linear infinite;
    }

    .loading-spinner::after {
      content: '';
      position: absolute;
      width: 80%;
      height: 80%;
      top: 10%;
      left: 10%;
      border: 4px solid rgba(255, 165, 0, 0.1);
      border-radius: 50%;
      border-top-color: #FFA500;
      animation: spin 0.7s linear infinite reverse;
    }

    .loading-text {
      font-size: 18px;
      color: #94a3b8;
      font-weight: 500;
      margin-bottom: 15px;
    }

    .loading-status {
      font-size: 14px;
      color: #64748b;
      min-height: 20px;
      transition: all 0.3s ease;
    }

    .loading-progress {
      width: 300px;
      height: 4px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 2px;
      margin: 20px auto 0;
      overflow: hidden;
      position: relative;
    }

    .loading-progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #FFD700, #FFA500);
      border-radius: 2px;
      width: 0%;
      transition: width 0.3s ease;
      box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
    }

    .loading-items {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 30px;
      flex-wrap: wrap;
      max-width: 600px;
    }

    .loading-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #64748b;
      font-size: 13px;
      transition: all 0.3s ease;
    }

    .loading-item.completed {
      color: #22c55e;
    }

    .loading-item.loading {
      color: #FFD700;
    }

    .loading-item i {
      font-size: 16px;
    }

    .loading-item .fa-check-circle {
      display: none;
    }

    .loading-item.completed .fa-check-circle {
      display: inline;
    }

    .loading-item.completed .fa-spinner {
      display: none;
    }

    .loading-item .fa-spinner {
      animation: spin 1s linear infinite;
    }

  </style>
</head>
<body>
  <!-- Tela de Loading -->
  <div id="loadingScreen">
    <div class="loading-content">
      <div class="loading-logo">Soc.ia</div>
      
      <div class="loading-text">Carregando dados...</div>
      <div class="loading-status" id="loadingStatus">Inicializando</div>
      <div class="loading-progress">
       <div class="loading-progress-bar" id="loadingProgressBar"></div>
      </div>
      <div class="loading-items">
        <div class="loading-item" id="loadingIndicadores">
          <i class="fas fa-spinner"></i>
          <i class="fas fa-check-circle"></i>
          <span>Indicadores</span>
        </div>
        <div class="loading-item" id="loadingCards">
          <i class="fas fa-spinner"></i>
          <i class="fas fa-check-circle"></i>
          <span>Cards</span>
        </div>
        <div class="loading-item" id="loadingTabelas">
          <i class="fas fa-spinner"></i>
          <i class="fas fa-check-circle"></i>
          <span>Tabelas</span>
        </div>
        <div class="loading-item" id="loadingGraficos">
          <i class="fas fa-spinner"></i>
          <i class="fas fa-check-circle"></i>
          <span>Gráficos</span>
        </div>
      </div>
    </div>
  </div>

  <script src="https://kit.fontawesome.com/7e7eda2fa6.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
  <div class="background"></div>
  
  <!-- Debug Panel -->
  <button id="debugToggle" onclick="toggleDebug()">DEBUG</button>
  <div id="debugPanel">
    <div id="debugContent"></div>
  </div>
  
  <!-- Notificação de atualização -->
  <div id="updateNotification" class="update-notification">
    <i class="fas fa-sync-alt"></i>
    Conteúdo atualizado da planilha!
  </div>
  
  <div class="topbar">
    <div class="logo" style="font-size: 16px;">Mais Dois Financeiro | Soc.ia</div>
    <div class="menu-buttons">
      <button class="menu-button" data-content="soc-ia" onclick="showContent('soc-ia')">
        <img src="Socia2.png" alt="Soc.ia" style="height: 16px; margin-right: 4px;" />
        Soc.ia
      </button>
      <a href="Casa_Cuidar_1_BI.php" class="menu-button">
        <i class="fas fa-chart-bar"></i>
        Indicadores Financeiros
      </a>
    </div>
    <div class="user-menu">
      <button class="user-icon" onclick="toggleDropdown()">
        <i class="fas fa-user-circle"></i>
      </button>
      <div class="dropdown" id="dropdownMenu">
        <span><i class="fas fa-user"></i> <?= $_SESSION['email'] ?></span>
        <a href="change_password.php"><i class="fas fa-key"></i> Alterar senha</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
      </div>
    </div>
  </div>
  <div class="content-container">
    <div class="content active" id="soc-ia">
      <!-- Faixa de filtros de período -->
      <div class="period-filter">
        <h3><i class="fas fa-calendar-alt"></i>Filtros de Período</h3>
        <div class="period-buttons">
          <button class="period-button active" onclick="setPeriodFilter('ultimo-mes')" data-period="ultimo-mes">
            <i class="fas fa-calendar-check"></i> Último Mês Fechado
          </button>
          <button class="period-button" onclick="setPeriodFilter('ultimo-quarter')" data-period="ultimo-quarter">
            <i class="fas fa-calendar-week"></i> Último Trimestre Fechado
          </button>
          <button class="period-button" onclick="setPeriodFilter('ano-atual')" data-period="ano-atual">
            <i class="fas fa-calendar"></i> Ano Atual
          </button>
        </div>
      </div>
      
      <div id="cards-container2" class="cards-flex"></div>
      <div id="cards-container"></div>
    </div>
  </div>
  <button id="downloadPDF" title="Baixar em PDF">
    <i class="fas fa-file-download"></i>
  </button>
  <script>
  const GOOGLE_API_KEY = '<?php echo $GOOGLE_API_KEY; ?>';
  const GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1_IA = '<?php echo $GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1_IA; ?>';
  const GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1 = '<?php echo $GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1; ?>';
  const GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2 = '<?php echo $GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2; ?>';
  const GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar = '<?php echo $GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar; ?>';
  const APPS_SCRIPT_URL = '<?php echo $APPS_SCRIPT_URL; ?>';

  const DASHBOARD_SHEET_IDS = [
    GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1,
    GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_2,
    GOOGLE_SHEETS_FILE_ID_Rede_Casa_Cuidar
  ].filter(Boolean);

  async function fetchSheetValues(spreadsheetId, range) {
    const response = await fetch(`https://sheets.googleapis.com/v4/spreadsheets/${spreadsheetId}/values/${range}?key=${GOOGLE_API_KEY}`);
    if (!response.ok) {
      throw new Error(`Erro HTTP ${response.status}`);
    }

    const data = await response.json();
    if (!data.values || data.values.length === 0) {
      return { header: null, rowsData: [] };
    }

    return {
      header: data.values[0],
      rowsData: data.values.slice(1)
    };
  }

  async function loadCombinedPivotData() {
    const range = 'Dados_Pivotados!A:BA';

    if (DASHBOARD_SHEET_IDS.length === 0) {
      throw new Error('Nenhuma planilha de dashboard foi configurada.');
    }

    const responses = await Promise.allSettled(
      DASHBOARD_SHEET_IDS.map(sheetId => fetchSheetValues(sheetId, range))
    );

    let header = null;
    const rowsData = [];
    const failedSheets = [];

    responses.forEach((result, index) => {
      const sheetId = DASHBOARD_SHEET_IDS[index];

      if (result.status === 'fulfilled') {
        if (!header && result.value.header) {
          header = result.value.header;
        }
        rowsData.push(...result.value.rowsData);
        return;
      }

      failedSheets.push(sheetId);
    });

    if (!header) {
      throw new Error('Não foi possível carregar dados de nenhuma planilha do dashboard.');
    }

    if (failedSheets.length > 0) {
      debugLog(`⚠️ Falha ao carregar algumas planilhas: ${failedSheets.join(', ')}`);
    }

    return { header, rowsData };
  }
  
    // Variável global para controle do período atual
    let currentPeriod = 'ultimo-mes';
    
    // Sistema de detecção de mudanças via Google Drive API
    let isUpdating = false;
    let currentEditingElement = null;
    let forceSheetData = false;
    let debugEnabled = false;


    // Sistema de controle de loading
    const loadingManager = {
      totalItems: 0,
      loadedItems: 0,
      items: {
        indicadores: false,
        cards: false,
        tabelas: false,
        graficos: false
      },

      init() {
        this.totalItems = Object.keys(this.items).length;
        this.loadedItems = 0;
        this.updateProgress();
      },

      setStatus(message) {
        const statusEl = document.getElementById('loadingStatus');
        if (statusEl) {
          statusEl.textContent = message;
          debugLog(`📊 Loading: ${message}`);
        }
      },

      markComplete(itemName) {
        if (this.items.hasOwnProperty(itemName) && !this.items[itemName]) {
          this.items[itemName] = true;
          this.loadedItems++;

          // Atualizar UI do item
          const itemEl = document.getElementById(`loading${itemName.charAt(0).toUpperCase() + itemName.slice(1)}`);
          if (itemEl) {
            itemEl.classList.remove('loading');
            itemEl.classList.add('completed');
          }

          this.updateProgress();
          debugLog(`✅ ${itemName} carregado (${this.loadedItems}/${this.totalItems})`);

          // Verificar se tudo foi carregado
          if (this.loadedItems === this.totalItems) {
            this.hideLoadingScreen();
          }
        }
      },

      markLoading(itemName) {
        const itemEl = document.getElementById(`loading${itemName.charAt(0).toUpperCase() + itemName.slice(1)}`);
        if (itemEl) {
          itemEl.classList.add('loading');
        }
      },

      updateProgress() {
        const percentage = (this.loadedItems / this.totalItems) * 100;
        const progressBar = document.getElementById('loadingProgressBar');
        if (progressBar) {
          progressBar.style.width = `${percentage}%`;
        }
        this.setStatus(`${this.loadedItems} de ${this.totalItems} componentes carregados`);
      },

      hideLoadingScreen() {
        this.setStatus('Carregamento concluído!');
        setTimeout(() => {
          const loadingScreen = document.getElementById('loadingScreen');
          if (loadingScreen) {
            loadingScreen.classList.add('hidden');
            debugLog('🎉 Tela de loading ocultada');
          }
        }, 500);
      },

      reset() {
        this.loadedItems = 0;
        Object.keys(this.items).forEach(key => {
          this.items[key] = false;
          const itemEl = document.getElementById(`loading${key.charAt(0).toUpperCase() + key.slice(1)}`);
          if (itemEl) {
            itemEl.classList.remove('completed', 'loading');
          }
        });
        this.updateProgress();

        // Mostrar a tela de loading novamente
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
          loadingScreen.classList.remove('hidden');
        }
      }
    };
    
    // Sistema de debug
    function debugLog(message) {
      const timestamp = new Date().toLocaleTimeString();
      const debugContent = document.getElementById('debugContent');
      debugContent.innerHTML += `[${timestamp}] ${message}<br>`;
      debugContent.scrollTop = debugContent.scrollHeight;
      console.log(`[DEBUG] ${message}`);
    }
    
    function toggleDebug() {
      debugEnabled = !debugEnabled;
      const panel = document.getElementById('debugPanel');
      if (debugEnabled) {
        panel.classList.add('show');
        debugLog('Debug ativado');
      } else {
        panel.classList.remove('show');
      }
    }
    
    function showContent(contentId) {
      const allContents = document.querySelectorAll('.content');
      allContents.forEach(c => c.classList.remove('active'));
      const allButtons = document.querySelectorAll('.menu-button');
      allButtons.forEach(b => b.classList.remove('active'));
      document.getElementById(contentId).classList.add('active');
      document.querySelector(`[data-content="${contentId}"]`).classList.add('active');
      const downloadButton = document.getElementById('downloadPDF');
      downloadButton.style.display = 'block';
    }
    
    // Função para mostrar notificação de atualização
    function showUpdateNotification() {
      const notification = document.getElementById('updateNotification');
      notification.classList.add('show');
      setTimeout(() => {
        notification.classList.remove('show');
      }, 3000);
      debugLog('Notificação de atualização exibida');
    }
    
    // Função para salvar timestamp da última edição do usuário
    function saveUserEditTime(cardId) {
      const currentTime = new Date().toISOString();
      localStorage.setItem(`lastUserEdit-${cardId}`, currentTime);
      debugLog(`Timestamp de edição salvo para ${cardId}: ${currentTime}`);
    }
    
    // Função para obter timestamp da última edição do usuário
    function getUserEditTime(cardId) {
      const timestamp = localStorage.getItem(`lastUserEdit-${cardId}`);
      debugLog(`Timestamp recuperado para ${cardId}: ${timestamp || 'não existe'}`);
      return timestamp;
    }
    
    // Função para verificar mudanças via Google Drive API v3
    function checkFileModification() {
      debugLog('🔍 Verificando modifiedTime via Google Drive API...');
      const driveApiUrl = `https://www.googleapis.com/drive/v3/files/${GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1_IA}?fields=id,name,modifiedTime&key=${GOOGLE_API_KEY}`;
      
      fetch(driveApiUrl)
        .then(response => response.json())
        .then(fileMetadata => {
          if (fileMetadata.error) {
            debugLog(`❌ Erro na API: ${fileMetadata.error.message}`);
            return;
          }
          
          const fileModifiedTime = fileMetadata.modifiedTime; // Ex: "2025-09-18T18:54:59.523Z"
          const fileName = fileMetadata.name;
          
          debugLog(`📁 Arquivo: ${fileName}`);
          debugLog(`📅 ModifiedTime do arquivo: ${fileModifiedTime}`);
          
          // Salvar o modifiedTime do arquivo
          const storedFileTime = localStorage.getItem('fileModifiedTime');
          debugLog(`💾 ModifiedTime armazenado: ${storedFileTime || 'não existe'}`);
          
          if (storedFileTime && storedFileTime !== fileModifiedTime) {
            debugLog('🔥 ARQUIVO FOI MODIFICADO NO GOOGLE DRIVE!');
            
            // Verificar se o arquivo foi modificado após as últimas edições do usuário
            const shouldUpdateFromFile = checkIfShouldUpdateFromFile(fileModifiedTime);
            
            if (shouldUpdateFromFile) {
              debugLog('✅ Arquivo é mais recente que edições do usuário - usando dados do arquivo');
              forceSheetData = true;
              clearEditableCardsFromStorage();
              showUpdateNotification();
              
              // Atualizar os cards
              if (document.getElementById('soc-ia').classList.contains('active')) {
                isUpdating = true;
                document.getElementById('cards-container').innerHTML = '';
                loadCards();
              }
            } else {
              debugLog('⚠️ Edições do usuário são mais recentes - mantendo dados locais');
            }
          } else if (!storedFileTime) {
            debugLog('🆕 Primeira verificação - salvando modifiedTime');
          } else {
            debugLog('✅ Arquivo não foi modificado');
          }
          
          // Salvar o novo modifiedTime
          localStorage.setItem('fileModifiedTime', fileModifiedTime);
        })
        .catch(error => {
          debugLog(`❌ Erro na verificação: ${error.message}`);
        });
    }
    
    // Função para verificar se deve usar dados do arquivo
    function checkIfShouldUpdateFromFile(fileModifiedTime) {
      // Converter o timestamp do arquivo para Date
      const fileDate = new Date(fileModifiedTime);
      debugLog(`📊 Data do arquivo: ${fileDate.toISOString()}`);
      
      // Verificar todas as edições do usuário
      let latestUserEdit = null;
      const keys = Object.keys(localStorage);
      
      keys.forEach(key => {
        if (key.startsWith('lastUserEdit-')) {
          const editTime = localStorage.getItem(key);
          const editDate = new Date(editTime);
          
          if (!latestUserEdit || editDate > latestUserEdit) {
            latestUserEdit = editDate;
          }
        }
      });
      
      if (latestUserEdit) {
        debugLog(`👤 Última edição do usuário: ${latestUserEdit.toISOString()}`);
        debugLog(`🔍 Arquivo modificado em: ${fileDate.toISOString()}`);
        debugLog(`⏰ Arquivo é mais recente? ${fileDate > latestUserEdit}`);
        return fileDate > latestUserEdit;
      } else {
        debugLog('📝 Nenhuma edição do usuário encontrada - usando arquivo');
        return true;
      }
    }
    
    // Função para limpar dados de cards editáveis do localStorage
    function clearEditableCardsFromStorage() {
      const keys = Object.keys(localStorage);
      let removed = 0;
      keys.forEach(key => {
        if (key.startsWith('cardContent-card-editable-')) {
          localStorage.removeItem(key);
          removed++;
          debugLog(`🗑️ Removido: ${key}`);
        }
      });
      debugLog(`🧹 Total de ${removed} itens removidos do localStorage`);
    }
    
    // Função para salvar conteúdo do card NA PLANILHA (via Apps Script proxy)
    async function saveCardContent(cardText, card) {
      if (!cardText || !cardText.id) return;

      const cardIndex = cardText.id.replace('card-editable-', '');
      const rowNumber = parseInt(cardIndex) + 1; // +1 porque índice começa em 0 (sem linha de header)

      // Pegar o conteúdo editado
      const contentHTML = cardText.innerHTML;

      // Preparar dados para enviar ao proxy
      const range = `Página1!B${rowNumber}`; // Coluna B contém o texto dos cards

      const payload = {
        spreadsheetId: GOOGLE_SHEETS_FILE_ID_Casa_Cuidar_1_IA,
        range: range,
        values: [[contentHTML]]
      };

      try {
        debugLog(`📤 Enviando para Apps Script: ${range}`);
        debugLog(`📦 Payload: ${JSON.stringify(payload)}`);

        // Fazer request para o Apps Script proxy
        const response = await fetch(APPS_SCRIPT_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'text/plain',
          },
          body: JSON.stringify(payload),
          redirect: 'follow'
        });

        debugLog(`✅ Dados enviados para planilha: ${cardText.id} - Linha ${rowNumber}`);

        // Feedback visual de salvamento
        card.classList.add('card-updated');
        card.style.borderColor = 'rgba(34, 197, 94, 0.8)';
        card.style.boxShadow = '0 0 20px rgba(34, 197, 94, 0.4)';

        showSaveNotification('Salvo com sucesso na planilha!');

        setTimeout(() => {
          card.classList.remove('card-updated');
          card.style.borderColor = '';
          card.style.boxShadow = '';
        }, 1500);

        // Atualizar timestamp da última edição
        saveUserEditTime(cardText.id);

        // NÃO salvar no localStorage - usar apenas a planilha como fonte
        localStorage.removeItem(`cardContent-${cardText.id}`);

      } catch (error) {
        debugLog(`❌ Erro ao salvar: ${error.message}`);
        showSaveNotification('Erro ao salvar na planilha!', 'error');
      }
    }

    // Função para mostrar notificação de salvamento
    function showSaveNotification(message, type = 'success') {
      const notification = document.createElement('div');
      notification.className = 'save-notification';
      notification.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;

      notification.style.cssText = 'position: fixed; bottom: 100px; right: 32px; background: ' + 
        (type === 'success' ? 'linear-gradient(135deg, #22c55e, #16a34a)' : 'linear-gradient(135deg, #ef4444, #dc2626)') +
        '; color: white; padding: 12px 20px; border-radius: 12px; box-shadow: 0 8px 32px ' +
        (type === 'success' ? 'rgba(34, 197, 94, 0.4)' : 'rgba(239, 68, 68, 0.4)') +
        '; z-index: 1000; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px;';

      document.body.appendChild(notification);

      // Animar entrada
      setTimeout(() => {
        notification.style.transform = 'translateX(0)';
      }, 10);

      // Remover após 2 segundos
      setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 300);
      }, 2000);
    }

    // Função para sair do modo de edição
    function exitEditMode(cardText, card) {
      if (!cardText || !card) return;
      
      cardText.setAttribute('contenteditable', 'false');
      card.classList.remove('editing');
      currentEditingElement = null;
      
      // Salvar o conteúdo
      saveCardContent(cardText, card);
    }
    
    // Event listener global para cliques fora dos cards
    document.addEventListener('click', function(e) {
      if (currentEditingElement) {
        const clickedCard = e.target.closest('.card-editable');
        
        // Se clicou fora do card em edição ou em outro card
        if (!clickedCard || clickedCard !== currentEditingElement.card) {
          exitEditMode(currentEditingElement.cardText, currentEditingElement.card);
        }
      }
    });
    
    // Função para definir o filtro de período
    function setPeriodFilter(period) {
      currentPeriod = period;


      // Resetar loading manager
      loadingManager.reset();
      loadingManager.init();
      
      // Atualizar botões ativos
      document.querySelectorAll('.period-button').forEach(btn => {
        btn.classList.remove('active');
      });
      document.querySelector(`[data-period="${period}"]`).classList.add('active');
      
      // Limpar containers
      document.getElementById('cards-container').innerHTML = '';
      document.getElementById('cards-container2').innerHTML = '';
      
      // Recarregar dados com o novo período
      loadCards();
    }
    
    // Função para obter range de datas baseado no período selecionado
    function getDateRange(period) {
      const hoje = new Date();
      let startDate, endDate, label;
      
      switch(period) {
        case 'ultimo-mes':
          // Último mês fechado
          const ultimoMes = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
          startDate = new Date(ultimoMes.getFullYear(), ultimoMes.getMonth(), 1);
          endDate = new Date(ultimoMes.getFullYear(), ultimoMes.getMonth() + 1, 0);
          const mesesPt = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                          'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
          label = `${mesesPt[ultimoMes.getMonth()]}/${ultimoMes.getFullYear()}`;
          break;
          
        case 'ultimo-quarter':
          // Último quarter fechado
          const quarterAtual = Math.floor((hoje.getMonth() + 3) / 3);
          const quarterAnterior = quarterAtual === 1 ? 4 : quarterAtual - 1;
          const anoQuarter = quarterAtual === 1 ? hoje.getFullYear() - 1 : hoje.getFullYear();
          
          const startMonthQuarter = (quarterAnterior - 1) * 3;
          startDate = new Date(anoQuarter, startMonthQuarter, 1);
          endDate = new Date(anoQuarter, startMonthQuarter + 3, 0);
          label = `Q${quarterAnterior}/${anoQuarter}`;
          break;
          
        case 'ano-atual':
          // Ano atual completo
          startDate = new Date(hoje.getFullYear(), 0, 1);
          endDate = new Date(hoje.getFullYear(), 11, 31);
          label = `${hoje.getFullYear()}`;
          break;
          
        default:
          return { startDate: null, endDate: null, label: 'Último Mês Fechado' };
      }
      
      return { startDate, endDate, label };
    }
    
    // Função para verificar se uma data está no range
    function isDateInRange(dateStr, startDate, endDate) {
      if (!startDate || !endDate || !dateStr || !dateStr.includes('-')) return true;
      
      const [year, month, day] = dateStr.split('-').map(Number);
      if (!year || !month || isNaN(year) || isNaN(month)) return true;
      
      const date = new Date(year, month - 1, day || 1);
      return date >= startDate && date <= endDate;
    }
    
    // **NOVA FUNÇÃO**: Função para verificar se o status está nos valores permitidos
    function isStatusValid(status) {
      if (!status) return false;
      const statusUpper = status.toUpperCase();
      return statusUpper === 'ACQUITTED' || statusUpper === 'PARTIAL';
    }
    
    // Função para configurar edição inline nos cards
    function setupInlineEditing() {
      const editableCards = document.querySelectorAll('.card-editable');
      debugLog(`🔧 Configurando ${editableCards.length} cards editáveis`);
      
      editableCards.forEach((card, index) => {
        const cardText = card.querySelector('.card-text');
        if (!cardText) return;
        
        debugLog(`🎯 Setup card: ${cardText.id} - forceSheetData: ${forceSheetData}, isUpdating: ${isUpdating}`);
        
        // SEMPRE usar dados da planilha - não usar localStorage
        debugLog(`📄 Usando dados da planilha para ${cardText.id}`);
        // Limpar qualquer dado antigo do localStorage
        localStorage.removeItem(`cardContent-${cardText.id}`);

        
        // Destacar cards que foram atualizados
        if (isUpdating || forceSheetData) {
          card.classList.add('card-updated');
          debugLog(`✨ Card ${cardText.id} marcado como atualizado`);
          setTimeout(() => {
            card.classList.remove('card-updated');
          }, 2000);
        }
        
        // Evento de clique para ativar edição
        cardText.addEventListener('click', function(e) {
          e.stopPropagation();
          
          if (this.getAttribute('contenteditable') === 'true') {
            return;
          }
          
          if (currentEditingElement) {
            exitEditMode(currentEditingElement.cardText, currentEditingElement.card);
          }
          
          this.setAttribute('contenteditable', 'true');
          card.classList.add('editing');
          this.focus();
          
          currentEditingElement = {
            cardText: this,
            card: card
          };
          
          const range = document.createRange();
          range.selectNodeContents(this);
          const selection = window.getSelection();
          selection.removeAllRanges();
          selection.addRange(range);
        });
        
        // Evento keydown para salvar com Enter
        cardText.addEventListener('keydown', function(e) {
          if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            exitEditMode(this, card);
          }
          
          if (e.key === 'Escape') {
            e.preventDefault();
            const savedContent = localStorage.getItem(`cardContent-${this.id}`);
            if (savedContent) {
              this.innerHTML = savedContent;
            }
            exitEditMode(this, card);
          }
        });
      });
    }
    
    // Nova função para carregar o Top 10 Centro de Custos - Saldos Negativos
function loadTopCentroCustosNegativosCard() {
  const cardsContainer = document.getElementById('cards-container');
  const tableCard = document.createElement('div');
  tableCard.classList.add('card');
  
  const { label } = getDateRange(currentPeriod);
  
  tableCard.innerHTML = `
    <div class="card-title">
      <i class="fa-solid fa-exclamation-triangle" style="margin-right: 8px; color: #ef4444;"></i>Top 10 Centros de Custos - Saldos Negativos (${label})
    </div>
    <div class="card-text" id="topCentroCustosNegativosTable" style="overflow-x:auto;">
      <table id="centroCustosNegativosTable" style="width:100%; color: #e2e8f0; border-collapse: collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:16px; font-weight: 600; background: rgba(239, 68, 68, 0.1); color: #ef4444;">Centro de Custo</th>
            <th style="text-align:right; padding:16px; font-weight: 600; background: rgba(239, 68, 68, 0.1); color: #ef4444;">Contas a Receber (R$)</th>
            <th style="text-align:right; padding:16px; font-weight: 600; background: rgba(239, 68, 68, 0.1); color: #ef4444;">Contas a Pagar (R$)</th>
            <th style="text-align:right; padding:16px; font-weight: 600; background: rgba(239, 68, 68, 0.1); color: #ef4444;">Saldo (R$)</th>
          </tr>
        </thead>
        <tbody id="centroCustosNegativosBody">
          <tr><td colspan="4" style="text-align: center; padding: 20px;">Carregando dados...</td></tr>
        </tbody>
      </table>
    </div>
  `;
  cardsContainer.appendChild(tableCard);
  
  loadCombinedPivotData()
    .then(({ header, rowsData }) => {
      
      // **MODIFICAÇÃO**: Encontrar índice da coluna status
      const tipoIndex = header.indexOf('tipo');
      const centroCustoIndex = header.indexOf('Centro_de_Custo_Unificado');
      const valorIndex = header.indexOf('paid_new');
      const dataIndex = header.indexOf('lastAcquittanceDate');
      const statusIndex = header.indexOf('status');
      
      debugLog(`📊 Índices encontrados (negativos): tipo=${tipoIndex}, centroCusto=${centroCustoIndex}, valor=${valorIndex}, data=${dataIndex}, status=${statusIndex}`);
      
      const centrosCustos = {};
      const { startDate, endDate } = getDateRange(currentPeriod);
      
      // Processar dados da planilha
      rowsData.forEach(row => {
        const tipo = row[tipoIndex]?.toLowerCase();
        const centroCusto = row[centroCustoIndex] || 'Não informado';
        const valorStr = row[valorIndex]?.replace('.', '').replace(',', '.');
        const valor = parseFloat(valorStr);
        const dataStr = row[dataIndex];
        const status = row[statusIndex]; // **NOVO**
        
        // **MODIFICAÇÃO**: Validar dados incluindo status
        if (!tipo || isNaN(valor) || !dataStr || !dataStr.includes('-')) return;
        if (!isDateInRange(dataStr, startDate, endDate)) return;
        if (!centroCusto || centroCusto === 'Não informado') return;
        if (!isStatusValid(status)) return; // **NOVO FILTRO**
        
        // Inicializar centro de custo se não existir
        if (!centrosCustos[centroCusto]) {
          centrosCustos[centroCusto] = { receita: 0, despesa: 0, saldo: 0 };
        }
        
        // Acumular valores por tipo
        if (tipo === 'receita') {
          centrosCustos[centroCusto].receita += valor;
        } else if (tipo === 'despesa') {
          centrosCustos[centroCusto].despesa += valor;
        }
      });
      
      // Calcular saldos e filtrar apenas saldos negativos
      Object.keys(centrosCustos).forEach(centroCusto => {
        centrosCustos[centroCusto].saldo = 
          centrosCustos[centroCusto].receita - centrosCustos[centroCusto].despesa;
      });
      
      // Filtrar apenas centros com saldo negativo e ordenar pelos piores (mais negativos primeiro)
      const centrosNegativos = Object.entries(centrosCustos)
        .filter(([centroCusto, dados]) => dados.saldo < 0)
        .sort((a, b) => a[1].saldo - b[1].saldo)
        .slice(0, 10);
      
      debugLog(`📉 ${centrosNegativos.length} centros de custo com saldo negativo encontrados`);
      
      // Função para formatar valores monetários
      const formatValor = (valor) => {
        return valor.toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL'
        });
      };
      
      // Construir tabela HTML
      let tableHTML = '';
      
      if (centrosNegativos.length === 0) {
        tableHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #94a3b8;">Nenhum centro de custo com saldo negativo encontrado no período selecionado</td></tr>';
      } else {
        centrosNegativos.forEach(([centroCusto, dados], index) => {
          const rowClass = index % 2 === 0 ? 'background: rgba(239, 68, 68, 0.05);' : '';
          tableHTML += `
            <tr style="${rowClass}">
              <td style="padding:14px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">${centroCusto}</td>
              <td style="padding:14px 16px; text-align:right; font-weight: 600; color: #22c55e; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">${formatValor(dados.receita)}</td>
              <td style="padding:14px 16px; text-align:right; font-weight: 600; color: #ef4444; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">${formatValor(dados.despesa)}</td>
              <td style="padding:14px 16px; text-align:right; font-weight: 700; color: #ef4444; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">${formatValor(dados.saldo)}</td>
            </tr>
          `;
        });
      }
      
      // Atualizar tabela
      document.getElementById('centroCustosNegativosBody').innerHTML = tableHTML;
      
      debugLog(`✅ Top 10 Centros de Custos Negativos carregado com sucesso`);
    })
    .catch(error => {
      console.error('Erro ao carregar dados dos centros de custos negativos:', error);
      document.getElementById('centroCustosNegativosBody').innerHTML = 
        '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #ef4444;">Erro ao carregar dados</td></tr>';
    });
}

    function loadCards() {
      debugLog('🔄 Iniciando loadCards()');
      const cardsContainer = document.getElementById('cards-container');
      const introCard = document.createElement('div');
      introCard.classList.add('card');
      
      const { label } = getDateRange(currentPeriod);
      
      introCard.innerHTML = `
        <div class="card-title">
          <img src="Socia.png" alt="Logo Soc.ia" />
          Soc.ia - IA para Gestão Financeira (${label})
        </div>
        <div class="card-text">
          Soc.ia é uma solução de inteligência artificial desenvolvida para otimizar a gestão financeira e apoiar decisões estratégicas. 
        Esta plataforma permite uma visualização clara dos principais dados e métricas que impulsionam o desempenho da sua organização. Abaixo você terá alguns insights gerados a partir dos seus dados financeiros filtrados por: <strong>${label}</strong>.
        </div>
      `;
      cardsContainer.appendChild(introCard);
      
      debugLog('ℹ️ Card de texto da planilha IA desabilitado');
      loadingManager.markComplete('cards');
      
      // Reset das flags de atualização
      debugLog('🔄 Resetando flags...');
      isUpdating = false;
      forceSheetData = false;
      
      loadCombinedChartCard();
      loadCategoryChartsCard();
      loadIndicadoresFinanceirosCard();
      loadTopCentroCustosCard();
      loadTopCentroCustosNegativosCard();
    }
    
    // NOVA FUNÇÃO: Gráfico combinado que unifica linha e barras
    function loadCombinedChartCard() {
      const cardsContainer = document.getElementById('cards-container');
      const chartCard = document.createElement('div');
      chartCard.classList.add('card');
      
      // Usar sempre o ano vigente como padrão para este gráfico
      const currentYear = new Date().getFullYear();
      
      chartCard.innerHTML = `
        <div class="card-title"><i class="fa-solid fa-chart-mixed" style="margin-right: 8px;"></i>Fluxo de Caixa Completo - ${currentYear}</div>
        <canvas id="combinedChart" style="margin-top: 20px;"></canvas>
      `;
      cardsContainer.appendChild(chartCard);
      
      loadCombinedPivotData()
        .then(({ header, rowsData }) => {
          const dateIndex = header.indexOf('lastAcquittanceDate');
          const valueIndex = header.indexOf('paid_new');
          const tipoIndex = header.indexOf('tipo');
          const statusIndex = header.indexOf('status'); // **NOVO**
          
          // Objetos para armazenar dados mensais
          const receitaPorMes = {};
          const despesaPorMes = {};
          const saldoPorMes = {};
          
          // Processar dados - SEMPRE usar o ano vigente
          rowsData.forEach(row => {
            const rawDate = row[dateIndex];
            const tipo = row[tipoIndex]?.toLowerCase();
            const valorStr = row[valueIndex]?.replace('.', '').replace(',', '.');
            const status = row[statusIndex]; // **NOVO**
            
            if (!rawDate || !tipo || !valorStr || !rawDate.includes('-')) return;
            if (!isStatusValid(status)) return; // **NOVO FILTRO**
            
            const [year, month, day] = rawDate.split('-').map(Number);
            if (!year || !month || year !== currentYear) return;
            
            const mesAno = `${year}-${String(month).padStart(2, '0')}`;
            const valor = parseFloat(valorStr);
            
            if (tipo === 'receita') {
              receitaPorMes[mesAno] = (receitaPorMes[mesAno] || 0) + valor;
            } else if (tipo === 'despesa') {
              despesaPorMes[mesAno] = (despesaPorMes[mesAno] || 0) + valor;
            }
          });
          
          // Criar labels ordenados e calcular saldo
          const allMonths = Array.from(new Set([...Object.keys(receitaPorMes), ...Object.keys(despesaPorMes)])).sort();
          const receitas = allMonths.map(month => receitaPorMes[month] || 0);
          const despesas = allMonths.map(month => despesaPorMes[month] || 0);
          const saldos = allMonths.map(month => (receitaPorMes[month] || 0) - (despesaPorMes[month] || 0));
          
          // Formatação dos labels para exibição (MMM/YYYY)
          const labelsFormatados = allMonths.map(mes => {
            const [year, month] = mes.split('-');
            const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            return `${meses[parseInt(month) - 1]}/${year}`;
          });
          
          const ctx = document.getElementById('combinedChart').getContext('2d');
          Chart.register(ChartDataLabels);
          
          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: labelsFormatados,
              datasets: [
                {
                  label: 'Contas a Receber (R$)',
                  type: 'bar',
                  data: receitas,
                  backgroundColor: 'rgba(34, 197, 94, 0.8)',
                  borderColor: 'rgba(34, 197, 94, 1)',
                  borderWidth: 1,
                  borderRadius: 6,
                  order: 2,
                  yAxisID: 'y',
                  datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: '#ffffff',
                    font: { 
                      weight: 'bold',
                      family: 'Inter',
                      size: 11
                    },
                    formatter: value => {
                      if (value >= 1_000_000)
                        return (value / 1_000_000).toFixed(1).replace('.', ',') + 'M';
                      if (value >= 1_000)
                        return (value / 1_000).toFixed(0) + 'k';
                      else
                        return value.toFixed(0);
                    }
                  }
                },
                {
                  label: 'Contas a Pagar (R$)',
                  type: 'bar',
                  data: despesas,
                  backgroundColor: 'rgba(239, 68, 68, 0.8)',
                  borderColor: 'rgba(239, 68, 68, 1)',
                  borderWidth: 1,
                  borderRadius: 6,
                  order: 3,
                  yAxisID: 'y',
                  datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: '#ffffff',
                    font: { 
                      weight: 'bold',
                      family: 'Inter',
                      size: 11
                    },
                    formatter: value => {
                      if (value >= 1_000_000)
                        return (value / 1_000_000).toFixed(1).replace('.', ',') + 'M';
                      if (value >= 1_000)
                        return (value / 1_000).toFixed(0) + 'k';
                      else
                        return value.toFixed(0);
                    }
                  }
                },
                {
                  label: 'Saldo Líquido (R$)',
                  type: 'line',
                  data: saldos,
                  borderColor: '#FFD700',
                  backgroundColor: 'rgba(255, 215, 0, 0.1)',
                  borderWidth: 3,
                  tension: 0.4,
                  fill: false,
                  pointBackgroundColor: '#FFD700',
                  pointBorderColor: '#FFA500',
                  pointBorderWidth: 2,
                  pointRadius: 8,
                  order: 1,
                  yAxisID: 'y1',
                  datalabels: {
                    backgroundColor: 'rgba(255, 215, 0, 0.9)',
                    borderColor: '#FFA500',
                    borderRadius: 8,
                    borderWidth: 1,
                    color: '#0f0f23',
                    padding: 6,
                    anchor: 'center',
                    align: 'top',
                    offset: 10,
                    font: { 
                      weight: 'bold',
                      family: 'Inter',
                      size: 12
                    },
                    formatter: value => {
                      const absValue = Math.abs(value);
                      let formatted;
                      if (absValue >= 1_000_000)
                        formatted = (absValue / 1_000_000).toFixed(1).replace('.', ',') + 'M';
                      else if (absValue >= 1_000)
                        formatted = (absValue / 1_000).toFixed(0) + 'k';
                      else
                        formatted = absValue.toFixed(0);
                      return value < 0 ? `-${formatted}` : formatted;
                    }
                  }
                }
              ]
            },
            options: {
              responsive: true,
              interaction: {
                mode: 'index',
                intersect: false,
              },
              plugins: {
                legend: {
                  labels: {
                    color: '#e2e8f0',
                    font: {
                      family: 'Inter',
                      weight: '500',
                      size: 14
                    },
                    usePointStyle: true,
                    pointStyle: 'rectRounded'
                  }
                },
                tooltip: {
                  backgroundColor: 'rgba(15, 15, 35, 0.95)',
                  titleColor: '#FFD700',
                  bodyColor: '#e2e8f0',
                  borderColor: 'rgba(255, 215, 0, 0.3)',
                  borderWidth: 1,
                  cornerRadius: 8,
                  displayColors: true,
                  callbacks: {
                    label: function(context) {
                      const value = context.parsed.y;
                      const formattedValue = value.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                      });
                      return `${context.dataset.label}: ${formattedValue}`;
                    }
                  }
                }
              },
              scales: {
                x: {
                  ticks: { 
                    color: '#cbd5e1',
                    font: {
                      family: 'Inter',
                      size: 12
                    }
                  },
                  grid: { 
                    color: 'rgba(255, 255, 255, 0.1)',
                    lineWidth: 1
                  }
                },
                y: {
                  type: 'linear',
                  display: true,
                  position: 'left',
                  ticks: { 
                    color: '#cbd5e1',
                    font: {
                      family: 'Inter',
                      size: 11
                    },
                    callback: function(value) {
                      if (value >= 1_000_000)
                        return (value / 1_000_000).toFixed(1) + 'M';
                      if (value >= 1_000)
                        return (value / 1_000).toFixed(0) + 'k';
                      return value.toString();
                    }
                  },
                  grid: { 
                    color: 'rgba(255, 255, 255, 0.1)',
                    lineWidth: 1
                  },
                  beginAtZero: true
                },
                y1: {
                  type: 'linear',
                  display: false,
                  position: 'right',
                  grid: {
                    drawOnChartArea: false,
                  }
                }
              }
            },
            plugins: [ChartDataLabels]
          });

          debugLog('✅ Gráfico combinado carregado');
          loadingManager.markComplete('graficos');

        })
        .catch(error => {
          console.error('Erro ao carregar dados do gráfico combinado:', error);
        });
    }
    
    // Nova função para carregar o Top 10 Centro de Custos
function loadTopCentroCustosCard() {
  const cardsContainer = document.getElementById('cards-container');
  const tableCard = document.createElement('div');
  tableCard.classList.add('card');
  
  const { label } = getDateRange(currentPeriod);
  
  tableCard.innerHTML = `
    <div class="card-title">
      <i class="fa-solid fa-building" style="margin-right: 8px;"></i>Top 10 Centros de Custos - Saldos Positivos (${label})
    </div>
    <div class="card-text" id="topCentroCustosTable" style="overflow-x:auto;">
      <table id="centroCustosTable" style="width:100%; color: #e2e8f0; border-collapse: collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:16px; font-weight: 600; background: rgba(255, 215, 0, 0.1); color: #FFD700;">Centro de Custo</th>
            <th style="text-align:right; padding:16px; font-weight: 600; background: rgba(255, 215, 0, 0.1); color: #FFD700;">Contas a Receber (R$)</th>
            <th style="text-align:right; padding:16px; font-weight: 600; background: rgba(255, 215, 0, 0.1); color: #FFD700;">Contas a Pagar (R$)</th>
            <th style="text-align:right; padding:16px; font-weight: 600; background: rgba(255, 215, 0, 0.1); color: #FFD700;">Saldo (R$)</th>
          </tr>
        </thead>
        <tbody id="centroCustosBody">
          <tr><td colspan="4" style="text-align: center; padding: 20px;">Carregando dados...</td></tr>
        </tbody>
      </table>
    </div>
  `;
  cardsContainer.appendChild(tableCard);
  
  loadCombinedPivotData()
    .then(({ header, rowsData }) => {
      
      // **MODIFICAÇÃO**: Encontrar índice da coluna status
      const tipoIndex = header.indexOf('tipo');
      const centroCustoIndex = header.indexOf('Centro_de_Custo_Unificado');
      const valorIndex = header.indexOf('paid_new');
      const dataIndex = header.indexOf('lastAcquittanceDate');
      const statusIndex = header.indexOf('status');
      
      debugLog(`📊 Índices encontrados: tipo=${tipoIndex}, centroCusto=${centroCustoIndex}, valor=${valorIndex}, data=${dataIndex}, status=${statusIndex}`);
      
      const centrosCustos = {};
      const { startDate, endDate } = getDateRange(currentPeriod);
      
      // Processar dados da planilha
      rowsData.forEach(row => {
        const tipo = row[tipoIndex]?.toLowerCase();
        const centroCusto = row[centroCustoIndex] || 'Não informado';
        const valorStr = row[valorIndex]?.replace('.', '').replace(',', '.');
        const valor = parseFloat(valorStr);
        const dataStr = row[dataIndex];
        const status = row[statusIndex]; // **NOVO**
        
        // **MODIFICAÇÃO**: Validar dados incluindo status
        if (!tipo || isNaN(valor) || !dataStr || !dataStr.includes('-')) return;
        if (!isDateInRange(dataStr, startDate, endDate)) return;
        if (!centroCusto || centroCusto === 'Não informado') return;
        if (!isStatusValid(status)) return; // **NOVO FILTRO**
        
        // Inicializar centro de custo se não existir
        if (!centrosCustos[centroCusto]) {
          centrosCustos[centroCusto] = { receita: 0, despesa: 0, saldo: 0 };
        }
        
        // Acumular valores por tipo
        if (tipo === 'receita') {
          centrosCustos[centroCusto].receita += valor;
        } else if (tipo === 'despesa') {
          centrosCustos[centroCusto].despesa += valor;
        }
      });
      
      // Calcular saldos e filtrar apenas saldos positivos
      Object.keys(centrosCustos).forEach(centroCusto => {
        centrosCustos[centroCusto].saldo = 
          centrosCustos[centroCusto].receita - centrosCustos[centroCusto].despesa;
      });
      
      // Filtrar apenas centros com saldo positivo e ordenar
      const centrosPositivos = Object.entries(centrosCustos)
        .filter(([centroCusto, dados]) => dados.saldo > 0)
        .sort((a, b) => b[1].saldo - a[1].saldo)
        .slice(0, 10);
      
      debugLog(`📈 ${centrosPositivos.length} centros de custo com saldo positivo encontrados`);
      
      // Função para formatar valores monetários
      const formatValor = (valor) => {
        return valor.toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL'
        });
      };
      
      // Construir tabela HTML
      let tableHTML = '';
      
      if (centrosPositivos.length === 0) {
        tableHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #94a3b8;">Nenhum centro de custo com saldo positivo encontrado no período selecionado</td></tr>';
      } else {
        centrosPositivos.forEach(([centroCusto, dados], index) => {
          const rowClass = index % 2 === 0 ? 'background: rgba(255, 255, 255, 0.02);' : '';
          tableHTML += `
            <tr style="${rowClass}">
              <td style="padding:14px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">${centroCusto}</td>
              <td style="padding:14px 16px; text-align:right; font-weight: 600; color: #22c55e; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">${formatValor(dados.receita)}</td>
              <td style="padding:14px 16px; text-align:right; font-weight: 600; color: #ef4444; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">${formatValor(dados.despesa)}</td>
              <td style="padding:14px 16px; text-align:right; font-weight: 700; color: #FFD700; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">${formatValor(dados.saldo)}</td>
            </tr>
          `;
        });
      }
      
      // Atualizar tabela
      document.getElementById('centroCustosBody').innerHTML = tableHTML;
      
      debugLog(`✅ Top 10 Centros de Custos carregado com sucesso`);
    })
    .catch(error => {
      console.error('Erro ao carregar dados dos centros de custos:', error);
      document.getElementById('centroCustosBody').innerHTML = 
        '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #ef4444;">Erro ao carregar dados</td></tr>';
    });
}

    function loadCategoryChartsCard() {
      const cardsContainer = document.getElementById('cards-container');
      const tableCard = document.createElement('div');
      tableCard.classList.add('card');
      
      const { label } = getDateRange(currentPeriod);
      
      tableCard.innerHTML = `
        <div class="card-title">
          <i class="fa-solid fa-table" style="margin-right: 8px;"></i>Top 10 Categorias de Receitas e Despesas (${label}) 
        </div>
        <div class="card-text" id="topTables" style="overflow-x:auto;">
          <table id="topReceita" style="width:100%; margin-bottom: 24px; color: #e2e8f0; border-collapse: collapse;"></table>
          <table id="topDespesa" style="width:100%; color: #e2e8f0; border-collapse: collapse;"></table>
        </div>
      `;
      cardsContainer.appendChild(tableCard);
      
      loadCombinedPivotData()
        .then(({ header, rowsData }) => {
          const tipoIndex = header.indexOf('tipo');
          const categoriaIndex = header.indexOf('categoriesRatio.category');
          const valorIndex = header.indexOf('paid_new');
          const dataIndex = header.indexOf('lastAcquittanceDate');
          const statusIndex = header.indexOf('status'); // **NOVO**
          const receitas = {};
          const despesas = {};
          
          const { startDate, endDate } = getDateRange(currentPeriod);
          
          rowsData.forEach(row => {
            const tipo = row[tipoIndex]?.toLowerCase();
            const categoria = row[categoriaIndex] || 'Outros';
            const valorStr = row[valorIndex]?.replace('.', '').replace(',', '.');
            const valor = parseFloat(valorStr);
            const dataStr = row[dataIndex];
            const status = row[statusIndex]; // **NOVO**
            
            if (!tipo || isNaN(valor) || !dataStr || !dataStr.includes('-')) return;
            if (!isDateInRange(dataStr, startDate, endDate)) return;
            if (!isStatusValid(status)) return; // **NOVO FILTRO**
            
            if (tipo === 'receita') {
              receitas[categoria] = (receitas[categoria] || 0) + valor;
            } else if (tipo === 'despesa') {
              despesas[categoria] = (despesas[categoria] || 0) + valor;
            }
          });
          
          const formatValor = v => {
            return v.toLocaleString('pt-BR', {
              style: 'currency',
              currency: 'BRL'
            });
          };
          
          function buildTable(obj, title) {
            const sorted = Object.entries(obj)
              .sort((a, b) => b[1] - a[1])
              .slice(0, 10);
            let html = `<thead><tr><th style="text-align:left; padding:16px; font-weight: 600;">${title === 'Contas a Receber' ? '🟢' : '🔴'} Categoria</th><th style="text-align:right; padding:16px; font-weight: 600;">${title}</th></tr></thead><tbody>`;
            sorted.forEach(([categoria, valor]) => {
              html += `<tr><td style="padding:14px 16px;">${categoria}</td><td style="padding:14px 16px; text-align:right; font-weight: 600;">${formatValor(valor)}</td></tr>`;
            });
            html += '</tbody>';
            return html;
          }
          
          document.getElementById('topReceita').innerHTML = buildTable(receitas, 'Contas a Receber');
          document.getElementById('topDespesa').innerHTML = buildTable(despesas, 'Contas a Pagar');


          debugLog('✅ Tabelas de categorias carregadas');
          loadingManager.markComplete('tabelas');
        });
    }
    
    function loadIndicadoresFinanceirosCard() {
      const cardsContainer = document.getElementById('cards-container2');

      // SOLUÇÃO: Verificar se já está carregado para evitar duplicação
      if (cardsContainer.dataset.loading === 'true') {
        debugLog('⚠️ loadIndicadoresFinanceirosCard já está carregando, pulando...');
        return;
      }

      // Marcar como carregando
      cardsContainer.dataset.loading = 'true';
      cardsContainer.innerHTML = '';

      loadingManager.markLoading('indicadores');

      loadCombinedPivotData()
        .then(({ header, rowsData }) => {
          const dateIndex = header.indexOf('lastAcquittanceDate');
          const valueIndex = header.indexOf('paid_new');
          const tipoIndex = header.indexOf('tipo');
          const statusIndex = header.indexOf('status');

          let totalReceita = 0, totalDespesa = 0;
          let totalReceitaAnoVigente = 0, totalDespesaAnoVigente = 0;

          const { startDate, endDate, label } = getDateRange(currentPeriod);
          const currentYear = new Date().getFullYear();

          rowsData.forEach(row => {
            const rawDate = row[dateIndex];
            const tipo = row[tipoIndex]?.toLowerCase();
            const valorStr = row[valueIndex]?.replace('.', '').replace(',', '.');
            const status = row[statusIndex];

            if (!rawDate || !tipo || !valorStr || !rawDate.includes('-')) return;
            if (!isStatusValid(status)) return;

            const [year, month, day] = rawDate.split('-').map(Number);
            const valor = parseFloat(valorStr);

            if (isDateInRange(rawDate, startDate, endDate)) {
              if (tipo === 'receita') totalReceita += valor;
              else if (tipo === 'despesa') totalDespesa += valor;
            }

            if (year === currentYear) {
              if (tipo === 'receita') totalReceitaAnoVigente += valor;
              else if (tipo === 'despesa') totalDespesaAnoVigente += valor;
            }
          });

          const posicaoAtual = totalReceita - totalDespesa;
          const posicaoAnoVigente = totalReceitaAnoVigente - totalDespesaAnoVigente;

          let indicadores = [];

          if (currentPeriod === 'ultimo-mes') {
            indicadores = [
              { titulo: `Posição de Fluxo de Caixa (${currentYear})`, valor: posicaoAnoVigente, icon: 'fa-solid fa-sack-dollar', color: '#FFD700', classe: '' },
              { titulo: `Contas a Receber (${label})`, valor: totalReceita, icon: 'fa-solid fa-money-bill-trend-up', color: '#22c55e', classe: 'card-receita' },
              { titulo: `Contas a Pagar (${label})`, valor: totalDespesa, icon: 'fa-solid fa-arrow-down', color: '#ef4444', classe: 'card-despesa' },
              { titulo: `Margem (${label})`, valor: posicaoAtual, icon: 'fa-solid fa-money-bill', color: '#FFD700', classe: '' }
            ];
          } 
          else if (currentPeriod === 'ano-atual') {
            indicadores = [
              { titulo: `Posição de Fluxo de Caixa (${label})`, valor: posicaoAtual, icon: 'fa-solid fa-sack-dollar', color: '#FFD700', classe: '' },
              { titulo: `Contas a Receber (${label})`, valor: totalReceita, icon: 'fa-solid fa-money-bill-trend-up', color: '#22c55e', classe: 'card-receita' },
              { titulo: `Contas a Pagar (${label})`, valor: totalDespesa, icon: 'fa-solid fa-arrow-down', color: '#ef4444', classe: 'card-despesa' }
            ];
          }
          else if (currentPeriod === 'ultimo-quarter') {
            indicadores = [
              { titulo: `Posição de Fluxo de Caixa (${currentYear})`, valor: posicaoAnoVigente, icon: 'fa-solid fa-sack-dollar', color: '#FFD700', classe: '' },
              { titulo: `Contas a Receber (${label})`, valor: totalReceita, icon: 'fa-solid fa-money-bill-trend-up', color: '#22c55e', classe: 'card-receita' },
              { titulo: `Contas a Pagar (${label})`, valor: totalDespesa, icon: 'fa-solid fa-arrow-down', color: '#ef4444', classe: 'card-despesa' },
              { titulo: `Margem (${label})`, valor: posicaoAtual, icon: 'fa-solid fa-money-bill', color: '#FFD700', classe: '' }
            ];
          }
          else {
            indicadores = [
              { titulo: `Posição de Fluxo de Caixa (${label})`, valor: posicaoAtual, icon: 'fa-solid fa-sack-dollar', color: '#FFD700', classe: '' },
              { titulo: `Contas a Receber (${label})`, valor: totalReceita, icon: 'fa-solid fa-money-bill-trend-up', color: '#22c55e', classe: 'card-receita' },
              { titulo: `Contas a Pagar (${label})`, valor: totalDespesa, icon: 'fa-solid fa-arrow-down', color: '#ef4444', classe: 'card-despesa' },
              { titulo: `Margem (${label})`, valor: posicaoAtual, icon: 'fa-solid fa-money-bill', color: '#FFD700', classe: '' }
            ];
          }

          // SOLUÇÃO: Limpar novamente antes de adicionar cards  
          cardsContainer.innerHTML = '';

          indicadores.forEach(indicador => {
            const card = document.createElement('div');
            card.classList.add('card');
            if (indicador.classe) {
              card.classList.add(indicador.classe);
            }
            card.innerHTML = `
              <div class="card-title" style="font-size: 14px; justify-content: center;">
                <i class="${indicador.icon}" style="color: ${indicador.color}; margin-right: 8px;"></i>
                ${indicador.titulo}
              </div>
              <div class="card-text">R$ ${indicador.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</div>
            `;
            cardsContainer.appendChild(card);
          });

          debugLog(`✅ ${indicadores.length} indicadores financeiros carregados com sucesso`);
          loadingManager.markComplete('indicadores');
        })
        .catch(error => {
          console.error('Erro ao carregar dados:', error);
          debugLog(`❌ Erro ao carregar indicadores: ${error.message}`);
          loadingManager.markComplete('indicadores');
        })
        .finally(() => {
          // SOLUÇÃO: Desmarcar loading após conclusão
          cardsContainer.dataset.loading = 'false';
        });
    }
    
    function toggleDropdown() {
      const dropdown = document.getElementById('dropdownMenu');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
    
    window.onclick = function(event) {
      if (!event.target.closest('.user-menu')) {
        document.getElementById('dropdownMenu').style.display = 'none';
      }
    }
    
    window.onload = () => {
      debugLog('🚀 Aplicação iniciada');

      loadingManager.init();
      loadingManager.setStatus('Carregando aplicação...');
      showContent('soc-ia');
      loadCards();
      
      // Verificar mudanças no arquivo via Google Drive API a cada 15 segundos
      setInterval(checkFileModification, 15000);
      
      // Primeira verificação após 2 segundos
      setTimeout(checkFileModification, 2000);
    };
    
    document.getElementById('downloadPDF').addEventListener('click', () => {
      const btn = document.getElementById('downloadPDF');
      btn.classList.add('hide-in-pdf');
      document.querySelectorAll('.card-title').forEach(el => el.classList.add('pdf-title'));
      const element = document.body;
      const opt = {
        margin: 0,
        filename: 'Socia.pdf',
        image: { type: 'jpeg', quality: 1 },
        html2canvas: {
          scale: 1.5,
          useCORS: true,
          allowTaint: true,
          backgroundColor: '#0f0f23',
          scrollY: 0,
          windowWidth: document.documentElement.scrollWidth,
          windowHeight: document.documentElement.scrollHeight
        },
        jsPDF: {
          unit: 'pt',
          format: [element.scrollWidth, element.scrollHeight],
          orientation: 'portrait'
        },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
      };
      html2pdf().set(opt).from(element).toPdf().get('pdf').then(function (pdf) {
        const scale = 0.8;
        const pageCount = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
          pdf.setPage(i);
          pdf.internal.scaleFactor = 72 / 96;
          const currentPage = pdf.internal.pages[i];
          const scaled = currentPage.map(line => line.replace(/q\\s*([\\d\\.]+)\\s+0\\s+0\\s+([\\d\\.]+)\\s/, `q ${scale} 0 0 ${scale} `));
          pdf.internal.pages[i] = scaled;
        }
      }).save().then(() => {
        document.querySelectorAll('.card-title').forEach(el => el.classList.remove('pdf-title'));
        btn.classList.remove('hide-in-pdf');
      });
    });
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>
</html>
