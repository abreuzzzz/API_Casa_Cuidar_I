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
    "Financeiro_contas_a_receber_Rede_Cuidar": "1IEE29oRiHRiAor7eVgAdgHGeWvOf4pXIUtvET9LQJOU",
    "Financeiro_contas_a_pagar_Rede_Cuidar": "1sabBCHA_a1bo4jFrx4UMfTKDRdjjMHZFLqeXhR61q0s",
    "Financeiro_Completo_Rede_Cuidar": "1GI4HHXbrk0flezkA8MAespTvEuNA61XkIP2Sql4yPOY"
}

print("🗑️ Iniciando exclusão COMPLETA de todas as linhas das planilhas...")

# 1. Limpa TUDO de Contas a Receber
print("\n📋 Limpando: Financeiro_contas_a_receber_Rede_Cuidar")
planilha_receber = client.open_by_key(planilhas_ids["Financeiro_contas_a_receber_Rede_Cuidar"])
aba_receber = planilha_receber.sheet1
aba_receber.clear()
print("  ✅ Todas as linhas excluídas (incluindo cabeçalho)")

# 2. Limpa TUDO de Contas a Pagar
print("\n📋 Limpando: Financeiro_contas_a_pagar_Rede_Cuidar")
planilha_pagar = client.open_by_key(planilhas_ids["Financeiro_contas_a_pagar_Rede_Cuidar"])
aba_pagar = planilha_pagar.sheet1
aba_pagar.clear()
print("  ✅ Todas as linhas excluídas (incluindo cabeçalho)")

# 3. Limpa TUDO de Financeiro Completo - Aba principal (sheet1)
print("\n📋 Limpando: Financeiro_Completo_Rede_Cuidar (sheet1)")
planilha_completo = client.open_by_key(planilhas_ids["Financeiro_Completo_Rede_Cuidar"])
aba_completo = planilha_completo.sheet1
aba_completo.clear()
print("  ✅ Todas as linhas excluídas (incluindo cabeçalho)")

# 4. Limpa TUDO de Financeiro Completo - Aba Dados_Pivotados (se existir)
print("\n📋 Limpando: Financeiro_Completo_Rede_Cuidar (Dados_Pivotados)")
try:
    aba_pivotada = planilha_completo.worksheet("Dados_Pivotados")
    aba_pivotada.clear()
    print("  ✅ Todas as linhas excluídas (incluindo cabeçalho)")
except:
    print("  ⚠️ Aba 'Dados_Pivotados' não encontrada")

print("\n🎉 Limpeza completa concluída com sucesso!")
print("⚠️ ATENÇÃO: Todas as linhas foram removidas, incluindo os cabeçalhos")
