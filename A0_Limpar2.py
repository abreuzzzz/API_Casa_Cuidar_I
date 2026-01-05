import os
import json
import gspread
from oauth2client.service_account import ServiceAccountCredentials

# 🔐 Lê o segredo e salva como credentials.json
gdrive_credentials = os.getenv("GDRIVE_SERVICE_ACCOUNT")
with open("credentials.json", "w") as f:
    json.dump(json.loads(gdrive_credentials), f)

# 📌 Autenticação com Google
scope = ["https://spreadsheets.google.com/feeds", "https://www.googleapis.com/auth/drive"]
creds = ServiceAccountCredentials.from_json_keyfile_name("credentials.json", scope)
client = gspread.authorize(creds)

# === IDs das planilhas ===
planilhas_ids = {
    "Financeiro_contas_a_receber_Casa_Cuidar_2": "1wahL6qxX1s5A2VWyBfLpPQw45Qgk1tfz0ygiPxj05Ns",
    "Financeiro_contas_a_pagar_Casa_Cuidar_2": "1HKzzgAFd7QNAvLe-GmcT7HkXbFLREgzjy_joaqXPdDY",
    "Financeiro_Completo_Casa_Cuidar_2": "1Pfw_pg-DsDa4ij5U8s7NG-QvfRkdE5o22wgmOdasOw8"
}

print("🗑️ Iniciando exclusão COMPLETA de todas as linhas das planilhas...")

# 1. Limpa TUDO de Contas a Receber
print("\n📋 Limpando: Financeiro_contas_a_receber_Casa_Cuidar_2")
planilha_receber = client.open_by_key(planilhas_ids["Financeiro_contas_a_receber_Casa_Cuidar_2"])
aba_receber = planilha_receber.sheet1
aba_receber.clear()
print("  ✅ Todas as linhas excluídas (incluindo cabeçalho)")

# 2. Limpa TUDO de Contas a Pagar
print("\n📋 Limpando: Financeiro_contas_a_pagar_Casa_Cuidar_2")
planilha_pagar = client.open_by_key(planilhas_ids["Financeiro_contas_a_pagar_Casa_Cuidar_2"])
aba_pagar = planilha_pagar.sheet1
aba_pagar.clear()
print("  ✅ Todas as linhas excluídas (incluindo cabeçalho)")

# 3. Limpa TUDO de Financeiro Completo - Aba principal (sheet1)
print("\n📋 Limpando: Financeiro_Completo_Casa_Cuidar_2 (sheet1)")
planilha_completo = client.open_by_key(planilhas_ids["Financeiro_Completo_Casa_Cuidar_2"])
aba_completo = planilha_completo.sheet1
aba_completo.clear()
print("  ✅ Todas as linhas excluídas (incluindo cabeçalho)")

# 4. Limpa TUDO de Financeiro Completo - Aba Dados_Pivotados (se existir)
print("\n📋 Limpando: Financeiro_Completo_Casa_Cuidar_2 (Dados_Pivotados)")
try:
    aba_pivotada = planilha_completo.worksheet("Dados_Pivotados")
    aba_pivotada.clear()
    print("  ✅ Todas as linhas excluídas (incluindo cabeçalho)")
except:
    print("  ⚠️ Aba 'Dados_Pivotados' não encontrada")

print("\n🎉 Limpeza completa concluída com sucesso!")
print("⚠️ ATENÇÃO: Todas as linhas foram removidas, incluindo os cabeçalhos")
